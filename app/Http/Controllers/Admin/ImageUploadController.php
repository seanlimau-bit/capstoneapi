<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function __construct(
        protected ImageOptimizationService $imageOptimizer
    ) {}

    /**
     * Universal image upload handler
     */
    public function upload(Request $request)
    {
        $request->validate([
        'image' => 'required|image|mimes:jpeg,jpg,png,webp,svg|max:10240', // 10MB max
        'type' => 'required|in:logo,favicon,login_background,question_image,profile_picture,skill_image,track_image',
        'question_id' => 'nullable|integer',
        'user_id' => 'nullable|integer',
        'skill_id' => 'nullable|integer|exists:skills,id',
        'track_id' => 'nullable|integer|exists:tracks,id',
    ]);

        try {
            $type = $request->input('type');

        // Optimize the image
            $result = $this->imageOptimizer->optimize(
                $request->file('image'),
                $type
            );

        // Handle different types
            switch ($type) {
                case 'logo':
                $this->handleLogoUpload($result);
                break;
                case 'favicon':
                $this->handleFaviconUpload($result);
                break;
                case 'login_background':
                $this->handleBackgroundUpload($result);
                break;
                case 'question_image':
                $this->handleQuestionImageUpload($result, $request);
                break;
                case 'profile_picture':
                $this->handleProfilePictureUpload($result, $request);
                break;
                case 'skill_image': // ADD THIS CASE
                $this->handleSkillImageUpload($result, $request);
                break;
                case 'track_image':
                $this->handleTrackImageUpload($result, $request);
                break;
        }
        
        return response()->json([
            'success' => true,
            'url' => $result['url'] . '?v=' . time(),
            'path' => $result['path'],
            'message' => 'Image uploaded successfully!',
            'stats' => [
                'original_size' => $this->formatBytes($result['original_size']),
                'optimized_size' => $this->formatBytes($result['size']),
                'saved' => $this->formatBytes($result['saved_bytes']),
                'dimensions' => $result['dimensions']['width'] . '×' . $result['dimensions']['height'],
            ],
            'css_version' => in_array($type, ['logo', 'favicon', 'login_background']) ? time() : null,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}

protected function handleTrackImageUpload($result, $request)
{
    $trackId = $request->input('track_id');
    
    if (!$trackId) {
        throw new \Exception('Track ID is required for track image upload');
    }
    
    $track = \App\Models\Track::findOrFail($trackId);
    
    // Delete old image if exists
    if ($track->image) {
        $oldPath = storage_path('app/public/' . $track->image);
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }
    
    $track->image = $result['path'];
    $track->save();
}

protected function handleSkillImageUpload($result, $request)
{
    $skillId = $request->input('skill_id');
    
    if (!$skillId) {
        throw new \Exception('Skill ID is required for skill image upload');
    }
    
    $skill = \App\Models\Skill::findOrFail($skillId);
    
    // Delete old image if exists
    if ($skill->image) {
        $oldPath = storage_path('app/public/' . $skill->image);
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }
    
    // Update skill with new image path
    $skill->image = $result['path'];
    $skill->save();
}
    /**
     * Handle logo upload
     */
    protected function handleLogoUpload(array $result): void
    {
        // Delete old logo if exists
        $oldLogo = DB::table('configs')->where('key', 'site_logo')->value('value');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Save new path
        DB::table('configs')->updateOrInsert(
            ['key' => 'site_logo'],
            ['value' => $result['path'], 'updated_at' => now()]
        );

        // Regenerate theme CSS if you have that functionality
        $this->regenerateThemeCSS();
    }

    /**
     * Handle favicon upload
     */
    protected function handleFaviconUpload(array $result): void
    {
        $oldFavicon = DB::table('configs')->where('key', 'favicon')->value('value');
        if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
            Storage::disk('public')->delete($oldFavicon);
        }

        DB::table('configs')->updateOrInsert(
            ['key' => 'favicon'],
            ['value' => $result['path'], 'updated_at' => now()]
        );

        $this->regenerateThemeCSS();
    }

    /**
     * Handle login background upload
     */
    protected function handleBackgroundUpload(array $result): void
    {
        $oldBg = DB::table('configs')->where('key', 'login_background')->value('value');
        if ($oldBg && Storage::disk('public')->exists($oldBg)) {
            Storage::disk('public')->delete($oldBg);
        }

        DB::table('configs')->updateOrInsert(
            ['key' => 'login_background'],
            ['value' => $result['path'], 'updated_at' => now()]
        );

        $this->regenerateThemeCSS();
    }

    /**
     * Handle question image upload
     */
    protected function handleQuestionImageUpload(array $result, Request $request): void
    {
        // If you have a question_images table, save there
        // Otherwise, just return the URL for the question form to use
        if ($request->has('question_id')) {
            DB::table('question_images')->insert([
                'question_id' => $request->input('question_id'),
                'path' => $result['path'],
                'url' => $result['url'],
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Handle profile picture upload
     */
    protected function handleProfilePictureUpload(array $result, Request $request): void
    {
        $userId = $request->input('user_id') ?? auth()->id();
        
        if ($userId) {
            // Delete old avatar
            $oldAvatar = DB::table('users')->where('id', $userId)->value('avatar');
            if ($oldAvatar && Storage::disk('public')->exists($oldAvatar)) {
                Storage::disk('public')->delete($oldAvatar);
            }

            // Update user avatar
            DB::table('users')
            ->where('id', $userId)
            ->update([
                'avatar' => $result['path'],
                'updated_at' => now()
            ]);
        }
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        }
        return $bytes . 'B';
    }

    /**
     * Regenerate theme CSS (if you have this functionality)
     */
    protected function regenerateThemeCSS(): void
    {
        // Your theme CSS regeneration logic here
        // This is optional - only if you generate CSS from config values
    }
}