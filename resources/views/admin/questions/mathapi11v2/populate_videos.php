<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$videosPath = public_path('videos');

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($videosPath)
);

$inserted = 0;

foreach ($files as $file) {
    if ($file->isFile() && preg_match('/\.(mp4|webm|mov|avi)$/i', $file->getFilename())) {
        // Get relative path from "public/" onward
        $relativePath = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relativePath = str_replace('\\', '/', $relativePath); // normalize Windows
        $relativePath = ltrim($relativePath, '/'); // remove leading slash if any

        // Strip spaces and normalize title
        $title = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $title = Str::of($title)->replace(' ', '_');

        DB::table('videos')->insert([
            'storage_disk' => 'public',
            'video_link'   => $relativePath, // e.g. "videos/skills/123.mp4"
            'video_title'  => $title,
            'description'  => '',
            'status_id'    => 3,
            'user_id'      => 323,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $inserted++;
        echo "Inserted: {$relativePath}\n";
    }
}

echo "\nTotal videos inserted: {$inserted}\n";
