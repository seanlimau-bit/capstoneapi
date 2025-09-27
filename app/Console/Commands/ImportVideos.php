<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportVideos extends Command
{
    protected $signature = 'videos:import
                            {path : Folder to scan under the disk (e.g. videos)}
                            {--disk=public : Filesystem disk to read from}
                            {--ext=mp4,webm,mov,m4v : Comma list of allowed extensions}
                            {--status_id=1 : Default status_id for created/updated rows}
                            {--user_id=1 : Default user_id for created/updated rows}
                            {--field_id= : Optional global field_id for ALL rows}
                            {--infer-field : Derive field_id from ".../fields/{id}/..." in each file path}
                            {--exclude= : Comma list of folder/file segments to exclude (default: skills)}
                            {--dry-run : Show what would be imported without writing}';

    protected $description = 'Import video files from storage into the videos table (no skill linking)';

    public function handle()
    {
        $disk     = (string) $this->option('disk');
        $basePath = trim($this->argument('path'), '/');
        $dry      = (bool) $this->option('dry-run');

        $statusId   = (int) $this->option('status_id');
        $userId     = (int) $this->option('user_id');
        $globalFld  = $this->option('field_id') !== null ? (int) $this->option('field_id') : null;
        $inferField = (bool) $this->option('infer-field');

        // extensions
        $exts = collect(explode(',', (string) $this->option('ext')))
            ->map(fn($e) => strtolower(trim($e)))
            ->filter()
            ->values()
            ->all();

        // exclusions, default to ['skills'] unless user specifies something
        $excludeOpt = $this->option('exclude');
        $excludes = $excludeOpt === null || $excludeOpt === ''
            ? ['skills']
            : collect(explode(',', $excludeOpt))
                ->map(fn($e) => trim($e))
                ->filter()
                ->values()
                ->all();

        if (!Storage::disk($disk)->exists($basePath)) {
            $this->error("Path not found on disk [$disk]: {$basePath}");
            return 1;
        }

        // Gather files under base path
        $files = collect(Storage::disk($disk)->allFiles($basePath))
            ->map(fn($p) => str_replace('\\', '/', $p)) // normalize
            ->filter(function ($path) use ($exts) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                return in_array($ext, $exts, true);
            })
            ->reject(function ($path) use ($excludes) {
                // reject if the path contains any excluded segment as a directory/file segment
                foreach ($excludes as $seg) {
                    if ($seg === '') continue;
                    // ensure we match path segments, not substrings inside names
                    if (preg_match('#/(?:'.preg_quote($seg, '#').')(/|$)#i', '/'.$path)) {
                        return true;
                    }
                }
                return false;
            })
            ->values();

        if ($files->isEmpty()) {
            $this->info('No video files found to import (after filtering).');
            return 0;
        }

        $this->info("Found {$files->count()} file(s) on disk [$disk] under /{$basePath}");
        if (!empty($excludes)) {
            $this->info('Excluding segments: [' . implode(', ', $excludes) . ']');
        }
        $bar = $this->output->createProgressBar($files->count());
        $bar->start();

        $created = 0;
        $updated = 0;

        foreach ($files as $relPath) {
            // Title from filename
            $filename = pathinfo($relPath, PATHINFO_FILENAME);
            $title = Str::of($filename)
                ->replace(['_', '-'], ' ')
                ->replaceMatches('/\s+/', ' ')
                ->trim()
                ->title();

            // field_id for THIS file
            $fieldIdForThis = $globalFld;
            if ($inferField) {
                $inferred = $this->inferFieldIdFromPath($relPath);
                if ($inferred !== null) {
                    $fieldIdForThis = $inferred;
                }
            }

            $attrs = ['video_link' => $relPath];
            $vals  = [
                'video_title' => (string) $title,
                'description' => (string) $title,
                'status_id'   => $statusId,
                'user_id'     => $userId,
                'field_id'    => $fieldIdForThis, // may be null
            ];

            if ($dry) {
                $this->line("\n[DRY] would upsert: {$relPath}"
                    ." → title=\"{$title}\" status_id={$statusId} user_id={$userId}"
                    .' field_id=' . ($fieldIdForThis ?? 'NULL'));
            } else {
                /** @var \App\Models\Video $video */
                $video = Video::updateOrCreate($attrs, $vals);
                $video->wasRecentlyCreated ? $created++ : $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dry) {
            $this->info('Dry run complete. No database writes were made.');
        } else {
            $this->info("Import complete. Created: {$created}, Updated: {$updated}");
        }

        $this->line('Tip: use "--disk=webroot" if your files are under public/, and pass "videos" as the path.');
        return 0;
    }

    /**
     * Find a segment like ".../fields/{id}/..." and return {id} as int.
     */
    private function inferFieldIdFromPath(string $relPath): ?int
    {
        $parts = explode('/', trim($relPath, '/'));
        $n = count($parts);
        for ($i = 0; $i < $n - 1; $i++) {
            if (strtolower($parts[$i]) === 'fields'
                && isset($parts[$i + 1])
                && ctype_digit($parts[$i + 1])) {
                return (int) $parts[$i + 1];
            }
        }
        return null;
    }
}
