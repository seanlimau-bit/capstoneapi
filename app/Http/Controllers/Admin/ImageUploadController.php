<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Question;
use Illuminate\Validation\Rule;

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
        $validated = $request->validate([
            'image' => ['required','file','mimes:jpeg,jpg,png,webp,svg','max:10240'],
            'type'  => ['required', Rule::in([
                'logo','favicon','login_background','question_image','answer_image','profile_picture','skill_image','track_image'
            ])],

            'question_id' => ['required_if:type,question_image,answer_image','integer','exists:questions,id'],

            'option' => ['required_if:type,answer_image','in:0,1,2,3'],

            'user_id'  => ['nullable','integer'],
            'skill_id' => ['nullable','integer','exists:skills,id'],
            'track_id' => ['nullable','integer','exists:tracks,id'],
        ]);

        try {
            $type = $request->input('type');

        // Optimize the image
            $result = $this->imageOptimizer->optimize(
                $request->file('image'),
                $type
            );

        // Extra guards for specific types
            if (in_array($type, ['question_image', 'answer_image']) && !$request->filled('question_id')) {
                throw new \Exception('question_id is required for question/answer image uploads');
            }
            if ($type === 'answer_image' && !$request->filled('option')) {
                throw new \Exception('option is required for answer image uploads');
            }

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
                $this->handleQuestionImageUpload($result, Question::find($request->question_id));
                break;

                case 'answer_image':
                $this->handleAnswerImageUpload($result, $request);
                break;
                case 'profile_picture':
                $this->handleProfilePictureUpload($result, $request);
                break;
                case 'skill_image':
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
                    'saved' => $this->formatBytes($result['saved_bytes'] ?? 0),
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
    protected function handleQuestionImageUpload(array $result, Question $question): void
    {
    // delete old file if exists
        if (!empty($question->question_image) && Storage::disk('public')->exists($question->question_image)) {
            Storage::disk('public')->delete($question->question_image);
        }

        $question->question_image = $result['path'];
        $question->save();
    }

    protected function handleAnswerImageUpload(array $result, Request $request): void
    {
        $questionId = (int) $request->input('question_id');
    $option = (string) $request->input('option'); // '0' | '1' | '2' | '3'

    $question = \App\Models\Question::findOrFail($questionId);

    $fieldName = "answer{$option}_image";

    // Delete old image if exists
    $old = $question->$fieldName;
    if (!empty($old) && Storage::disk('public')->exists($old)) {
        Storage::disk('public')->delete($old);
    }

    // Save the new storage path
    $question->$fieldName = $result['path'];  // storage-relative path
    $question->save();
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