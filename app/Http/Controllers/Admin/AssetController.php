<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    /** Allowed file extensions per logical type */
    private const ALLOWED = [
        'image'    => ['jpg','jpeg','png','gif','svg','webp','bmp'],
        'video'    => ['mp4','mov','m4v','avi','mkv','webm','3gp','wmv'],
        'document' => ['pdf','doc','docx','txt','rtf','ppt','pptx','xls','xlsx','csv'],
        'archive'  => ['zip','rar','7z','tar','gz'],
        'audio'    => ['mp3','wav','ogg','aac','flac'],
    ];

    /** Top-level roots (disk => public_path()) we’ll scan */
    private const ROOTS = [
        ['disk' => 'webroot', 'path' => 'images'],
        ['disk' => 'webroot', 'path' => 'videos'],
        ['disk' => 'webroot', 'path' => 'animations'],
        ['disk' => 'webroot', 'path' => 'css'],
        ['disk' => 'webroot', 'path' => 'js'],
        ['disk' => 'public',  'path' => 'assets'], // storage/app/public/assets -> /storage/assets
    ];

    private int $maxFileSizeMb = 50;

    public function index()
    {
        return view('admin.assets.index');
    }

    /**
     * GET /admin/assets/list
     * Query: page, per_page, q, type, context (comma), sort[name|date|size|type|path], order[asc|desc]
     */
    public function listAssets(Request $request)
    {
        try {
            // ---- query
            $page     = max(1, (int) $request->query('page', 1));
            $perPage  = max(1, min(200, (int) $request->query('per_page', 60)));
            $q        = strtolower(trim((string) $request->query('q', '')));
            $typeReq  = strtolower(trim((string) $request->query('type', 'all')));
            $sort     = strtolower(trim((string) $request->query('sort', 'name')));
            $order    = strtolower(trim((string) $request->query('order', 'asc'))) === 'desc' ? 'desc' : 'asc';
            $contexts = array_values(array_filter(array_map('trim', explode(',', (string) $request->query('context', '')))));

            // Scan only relevant roots (big speed-up on large public/)
            $roots = $this->scanRoots($typeReq, $contexts);

            $assets  = [];
            $counts  = ['image'=>0,'video'=>0,'document'=>0,'audio'=>0,'archive'=>0,'animation'=>0];
            $finfo   = \finfo_open(FILEINFO_MIME_TYPE);

            foreach ($roots as $root) {
                $disk = Storage::disk($root['disk']);
                foreach ($disk->allFiles($root['path']) as $rel) {
                    $lower = strtolower($rel);

                    // skip junk
                    if (str_starts_with($lower, 'vendor/') || str_starts_with($lower, 'node_modules/') || str_starts_with($lower, 'build/')) {
                        continue;
                    }

                    $abs = $this->absolutePath($root['disk'], $rel);
                    if (!is_file($abs)) continue;

                    $name = basename($rel);
                    $ext  = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                    $type = $this->inferType($ext, $lower);

                    if ($type === 'other') continue; // ignore unknowns

                    // search on name or path
                    if ($q && !str_contains(strtolower($name), $q) && !str_contains($lower, $q)) {
                        continue;
                    }

                    // derive tags and apply context (OR)
                    $tags = $this->deriveTags($lower, $name, $ext, $type);
                    if ($contexts && ! $this->matchesAnyContext($tags, $contexts)) {
                        continue;
                    }

                    // count by type BEFORE type filter (used for tab badges)
                    if (isset($counts[$type])) $counts[$type]++;

                    // type filter
                    if ($typeReq !== 'all' && !($typeReq === $type || ($typeReq === 'animation' && in_array('animation', $tags, true)))) {
                        continue;
                    }

                    $assets[] = [
                        'path'       => str_replace('\\','/',$rel),
                        'name'       => $name,
                        'url'        => $this->publicUrl($root['disk'], $rel),
                        'type'       => $type,
                        'extension'  => $ext,
                        'mime'       => $finfo ? @\finfo_file($finfo, $abs) : null,
                        'size'       => @filesize($abs) ?: 0,
                        'modified'   => @filemtime($abs) ?: null,
                        'dimensions' => $type === 'image' ? $this->getImageDimensionsAbsolute($abs, $ext) : null,
                        'tags'       => $tags,
                    ];
                }
            }
            if ($finfo) \finfo_close($finfo);

            $counts['all'] = array_sum($counts);

            // sort
            $cmp = match ($sort) {
                'date' => fn($a,$b) => ($a['modified'] ?? 0) <=> ($b['modified'] ?? 0),
                'size' => fn($a,$b) => ($a['size'] ?? 0)     <=> ($b['size'] ?? 0),
                'type' => fn($a,$b) => ($a['type'] ?? '')     <=> ($b['type'] ?? ''),
                'path' => fn($a,$b) => ($a['path'] ?? '')     <=> ($b['path'] ?? ''),
                default => fn($a,$b)=> ($a['name'] ?? '')     <=> ($b['name'] ?? ''),
            };
            usort($assets, function ($a, $b) {
                return ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0);
            });
            // paginate
            $total  = count($assets);
            $offset = ($page - 1) * $perPage;
            $items  = array_slice($assets, $offset, $perPage);
            $hasMore = $offset + $perPage < $total;

            return response()->json([
                'success'    => true,
                'assets'     => $items,
                'pagination' => [
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'total'     => $total,
                    'has_more'  => $hasMore,
                    'next_page' => $hasMore ? $page + 1 : null,
                ],
                'counts' => $counts,
            ]);
        } catch (\Throwable $e) {
            Log::error('Assets list failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to load assets'], 500);
        }
    }

    /** Upload multiple files -> storage/app/public/assets */
    public function upload(Request $request)
    {
        $v = Validator::make($request->all(), [
            'files'   => 'required|array',
            'files.*' => 'required|file|max:'.($this->maxFileSizeMb * 1024),
            'folder'  => 'nullable|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$v->errors()],422);
        }

        try {
            $folder = ltrim($request->input('folder', ''), '/');
            $path   = 'assets/'.($folder ? $folder.'/' : '');

            $uploaded = [];
            $errors   = [];

            foreach ($request->file('files') as $file) {
                $res = $this->processUpload($file, $path);
                $res['success'] ? $uploaded[] = $res['file'] : $errors[] = $res['error'];
            }

            return response()->json([
                'success'  => count($uploaded) > 0,
                'message'  => count($uploaded).' file(s) uploaded',
                'uploaded' => $uploaded,
                'errors'   => $errors,
            ]);
        } catch (\Throwable $e) {
            Log::error('Upload failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Upload failed'],500);
        }
    }

    /** Upload single file */
    public function uploadSingle(Request $request)
    {
        $v = Validator::make($request->all(), [
            'file'   => 'required|file|max:'.($this->maxFileSizeMb * 1024),
            'folder' => 'nullable|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'message'=>'Validation failed','errors'=>$v->errors()],422);
        }

        try {
            $folder = ltrim($request->input('folder', ''), '/');
            $path   = 'assets/'.($folder ? $folder.'/' : '');
            $res    = $this->processUpload($request->file('file'), $path);

            return $res['success']
            ? response()->json(['success'=>true,'message'=>'File uploaded','file'=>$res['file']])
            : response()->json(['success'=>false,'message'=>$res['error']],400);
        } catch (\Throwable $e) {
            Log::error('Single upload failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Upload failed'],500);
        }
    }

    /** Create a folder under storage/app/public/assets */
    public function createFolder(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name'   => 'required|string|max:255|regex:/^[a-zA-Z0-9\-_\s]+$/',
            'parent' => 'nullable|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['success'=>false,'message'=>'Invalid folder name','errors'=>$v->errors()],422);
        }

        try {
            $folder = Str::slug($request->input('name'), '_');
            $parent = ltrim($request->input('parent', ''), '/');
            $full   = 'assets/'.($parent ? $parent.'/' : '').$folder;

            if (Storage::disk('public')->exists($full)) {
                return response()->json(['success'=>false,'message'=>'Folder already exists'],409);
            }
            Storage::disk('public')->makeDirectory($full);

            return response()->json(['success'=>true,'message'=>'Folder created','path'=>$full,'name'=>$folder]);
        } catch (\Throwable $e) {
            Log::error('Create folder failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to create folder'],500);
        }
    }

    /** Delete by id = base64("{disk}|{path}") or base64(path) (defaults to public) */
    public function delete(Request $request, string $id)
    {
        try {
            $decoded = base64_decode($id) ?: $id;
            $disk = 'public'; $path = $decoded;

            if (str_contains($decoded, '|')) {
                [$disk, $path] = explode('|', $decoded, 2);
            }
            $path = ltrim($path, '/');
            if ($this->isUnsafePath($path)) {
                return response()->json(['success'=>false,'message'=>'Invalid path'],422);
            }

            if (!Storage::disk($disk)->exists($path)) {
                return response()->json(['success'=>false,'message'=>'File or folder not found'],404);
            }

            if (is_dir($this->absolutePath($disk, $path))) {
                Storage::disk($disk)->deleteDirectory($path);
                return response()->json(['success'=>true,'message'=>'Folder deleted']);
            }

            Storage::disk($disk)->delete($path);
            return response()->json(['success'=>true,'message'=>'File deleted']);
        } catch (\Throwable $e) {
            Log::error('Delete failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to delete item'],500);
        }
    }

    /** File info by id */
    public function getFileInfo(Request $request, string $id)
    {
        try {
            $decoded = base64_decode($id) ?: $id;
            $disk = 'public'; $path = $decoded;

            if (str_contains($decoded, '|')) {
                [$disk, $path] = explode('|', $decoded, 2);
            }
            $path = ltrim($path, '/');

            if (!Storage::disk($disk)->exists($path)) {
                return response()->json(['success'=>false,'message'=>'File not found'],404);
            }

            $abs       = $this->absolutePath($disk, $path);
            $fileName  = basename($path);
            $ext       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $info = [
                'id'         => $disk.'|'.$path,
                'name'       => $fileName,
                'path'       => $path,
                'size'       => @filesize($abs) ?: 0,
                'modified'   => @filemtime($abs) ?: null,
                'url'        => $this->publicUrl($disk, $path),
                'type'       => $this->inferType($ext, $path),
                'extension'  => $ext,
                'dimensions' => $this->getImageDimensionsAbsolute($abs, $ext),
                'mime_type'  => $this->safeMimeType($disk, $path),
            ];

            return response()->json(['success'=>true,'file'=>$info]);
        } catch (\Throwable $e) {
            Log::error('Get file info failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to get file information'],500);
        }
    }

    /** Optional: folder tree across configured roots */
    public function getFolderTree()
    {
        try {
            $tree = [];
            foreach (self::ROOTS as $root) {
                $tree[] = [
                    'disk'       => $root['disk'],
                    'path'       => $root['path'],
                    'children'   => $this->buildFolderTree($root['disk'], $root['path']),
                    'file_count' => count(Storage::disk($root['disk'])->allFiles($root['path'])),
                ];
            }
            return response()->json(['success'=>true,'tree'=>$tree]);
        } catch (\Throwable $e) {
            Log::error('Folder tree failed: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Failed to load folder structure'],500);
        }
    }

    /* =========================
       Helpers
       ========================= */

private function scanRoots(string $type, array $contexts): array
{
    $disk = Storage::disk('public');

    $roots = [['disk' => 'public', 'path' => '']]; // always include the root

    // Optionally also add each top-level subdirectory as a root
    foreach ($disk->directories('') as $dir) {
        $roots[] = ['disk' => 'public', 'path' => $dir];
    }

    // If you still want your type/context filtering, apply it here,
    // otherwise just return $roots.
    return $roots;
}


private function processUpload($file, string $toPath): array
{
    try {
        $orig = $file->getClientOriginalName();
        $ext  = strtolower($file->getClientOriginalExtension());
        if (!$this->isAllowedExt($ext)) {
            return ['success'=>false,'error'=>"File type '{$ext}' is not allowed"];
        }
        $base   = Str::slug(pathinfo($orig, PATHINFO_FILENAME));
        $final  = $base.'_'.time().'.'.$ext;
        $stored = $file->storeAs($toPath, $final, 'public');

        return [
            'success' => true,
            'file'    => [
                'id'            => 'public|'.ltrim($stored, '/'),
                'name'          => $final,
                'original_name' => $orig,
                'path'          => $stored,
                'size'          => $file->getSize(),
                'type'          => $this->inferType($ext, $stored),
                'url'           => Storage::disk('public')->url($stored),
                'extension'     => $ext,
            ],
        ];
    } catch (\Throwable $e) {
        return ['success'=>false,'error'=>'Failed to upload file: '.$e->getMessage()];
    }
}

private function inferType(string $ext, string $lowerPath): string
{
    foreach (self::ALLOWED as $t => $exts) {
        if (in_array($ext, $exts, true)) return $t;
    }
        // Treat lottie/animations JSON as animation
    if ($ext === 'json' && str_contains($lowerPath, '/animations/')) return 'animation';
        // Heuristics by folder if still unknown
    if (preg_match('~/videos?/~i', $lowerPath)) return 'video';
    if (preg_match('~/images?|img/|/pictures?/~i', $lowerPath)) return 'image';
    if (preg_match('~/audio|music/~i', $lowerPath)) return 'audio';
    if (preg_match('~/docs?|documents?/~i', $lowerPath)) return 'document';
    return 'other';
}

private function deriveTags(string $lowerPath, string $name, string $ext, string $type): array
{
    $tags = [$type];

    if (preg_match('#/images/questions/.*/answers/#', $lowerPath) || str_contains($lowerPath, '/images/questions/answers/')) $tags[] = 'answer_image';
    if (preg_match('#/images/questions/.*/question_?image/#', $lowerPath) || str_contains($lowerPath, '/images/questions/question_image/')) $tags[] = 'question_image';

    if (str_contains($lowerPath, '/images/courses/'))  $tags[] = 'course_image';
    if (str_contains($lowerPath, '/images/profiles/')) $tags[] = 'profile_image';
    if (str_contains($lowerPath, '/images/houses/'))   $tags[] = 'house_image';

    if (str_contains($lowerPath, '/videos/fields/'))   $tags[] = 'field_video';
    if (str_contains($lowerPath, '/videos/skills/'))   $tags[] = 'skill_video';

    if (str_contains($lowerPath, '/animations/') || $ext === 'json') $tags[] = 'animation';

    $lname = strtolower($name);
    if (str_contains($lname, 'logo') || str_contains($lname, 'favicon')) $tags[] = 'logo';
    if (str_contains($lname, 'background') || str_contains($lname, 'login_bg') || str_contains($lname, 'login-background')) $tags[] = 'background';

    return array_values(array_unique($tags));
}

private function matchesAnyContext(array $tags, array $contexts): bool
{
    foreach ($contexts as $c) if (in_array($c, $tags, true)) return true;
    return false;
}

private function publicUrl(string $disk, string $path): ?string
{
    $path = ltrim($path, '/');
    return $disk === 'public'
            ? Storage::disk('public')->url($path)   // -> /storage/...
            : url('/'.$path);                       // webroot: /images/.. /videos/..
        }

        private function absolutePath(string $disk, string $path): string
        {
            $path = ltrim($path, '/');
            return $disk === 'public'
            ? Storage::disk('public')->path($path)
            : public_path($path);
        }

        private function safeMimeType(string $disk, string $path): ?string
        {
            try { return Storage::disk($disk)->mimeType($path); }
            catch (\Throwable $e) { return null; }
        }

        private function getImageDimensionsAbsolute(string $absolutePath, string $ext): ?array
        {
            if (!in_array($ext, self::ALLOWED['image'], true)) return null;
            try { if ($s = @getimagesize($absolutePath)) return ['width'=>$s[0],'height'=>$s[1]]; } catch (\Throwable $e) {}
            return null;
        }

        private function isAllowedExt(string $ext): bool
        {
            foreach (self::ALLOWED as $exts) if (in_array($ext, $exts, true)) return true;
        return $ext === 'json'; // allow lottie JSON if needed
    }

    private function isUnsafePath(string $path): bool
    {
        return Str::contains($path, ['..', '\\']) || trim($path) === '';
    }

    private function buildFolderTree(string $disk, string $path, int $level = 0): array
    {
        if ($level > 6) return [];
        $tree = [];
        foreach (Storage::disk($disk)->directories($path) as $dir) {
            $tree[] = [
                'name'       => basename($dir),
                'path'       => ltrim($dir, '/'),
                'children'   => $this->buildFolderTree($disk, $dir, $level + 1),
                'file_count' => count(Storage::disk($disk)->files($dir)),
            ];
        }
        return $tree;
    }
    /**
 * GET /admin/assets/videos - Get only video files for skill linking
 */
    public function getVideos(Request $request)
    {
        try {
            $videos = [];
            $finfo = \finfo_open(FILEINFO_MIME_TYPE);

        // Only scan video-relevant roots
            $videoRoots = [
                ['disk' => 'webroot', 'path' => 'videos'],
                ['disk' => 'public', 'path' => 'assets']
            ];

            foreach ($videoRoots as $root) {
                $disk = Storage::disk($root['disk']);

                if (!$disk->exists($root['path'])) {
                    continue;
                }

                foreach ($disk->allFiles($root['path']) as $rel) {
                    $abs = $this->absolutePath($root['disk'], $rel);
                    if (!is_file($abs)) continue;

                    $name = basename($rel);
                    $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
                    $type = $this->inferType($ext, strtolower($rel));

                // Only include video files
                    if ($type !== 'video') continue;

                    $videos[] = [
                        'name' => $name,
                        'path' => str_replace('\\', '/', $rel),
                        'url' => $this->publicUrl($root['disk'], $rel),
                        'size' => @filesize($abs) ?: 0,
                        'modified' => @filemtime($abs) ?: null,
                        'extension' => $ext,
                        'type' => 'video'
                    ];
                }
            }

            if ($finfo) \finfo_close($finfo);

        // Sort by modification date (newest first)
            usort($videos, function ($a, $b) {
                return ($b['modified'] ?? 0) <=> ($a['modified'] ?? 0);
            });

            return response()->json([
                'success' => true,
                'videos' => $videos
            ]);

        } catch (\Throwable $e) {
            Log::error('Get videos failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load videos'
            ], 500);
        }
    }
}
