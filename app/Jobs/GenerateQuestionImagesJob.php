<?php

namespace App\Jobs;

use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class GenerateQuestionImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 300; // 5 minutes

    protected $questionId;
    protected $imagePrompts;

    /**
     * Create a new job instance.
     */
    public function __construct($questionId, array $imagePrompts)
    {
        $this->questionId = $questionId;
        $this->imagePrompts = $imagePrompts;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $question = Question::findOrFail($this->questionId);
            
            Log::info('Starting image generation for question', [
                'question_id' => $this->questionId,
                'has_question_prompt' => isset($this->imagePrompts['question_image_prompt']),
                'answer_prompts_count' => count($this->imagePrompts['answer_image_prompts'] ?? [])
            ]);

            // Generate main question image if prompt exists
            if (!empty($this->imagePrompts['question_image_prompt'])) {
                $this->generateQuestionImage($question, $this->imagePrompts['question_image_prompt']);
            }

            // Generate answer images if prompts exist
            if (!empty($this->imagePrompts['answer_image_prompts']) && is_array($this->imagePrompts['answer_image_prompts'])) {
                foreach ($this->imagePrompts['answer_image_prompts'] as $index => $prompt) {
                    if (!empty($prompt)) {
                        $this->generateAnswerImage($question, $index, $prompt);
                    }
                }
            }

            Log::info('Completed image generation for question', [
                'question_id' => $this->questionId
            ]);

        } catch (\Exception $e) {
            Log::error('Image generation job failed', [
                'question_id' => $this->questionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Generate the main question image
     */
    protected function generateQuestionImage(Question $question, string $prompt)
    {
        try {
            // Generate image using DALL-E API
            $imageUrl = $this->generateImageWithDallE($prompt, 'question');
            
            if ($imageUrl) {
                // Define paths clearly
                $filename = "{$question->id}.png";
                $relativePath = "images/questions/{$filename}";
                $fullPath = "public/{$relativePath}";
                
                // Download image content
                $imageContent = Http::withOptions(['verify' => false])
                    ->timeout(30)
                    ->get($imageUrl)
                    ->body();
                
                // Ensure directory exists and save
                Storage::makeDirectory(dirname($fullPath));
                Storage::put($fullPath, $imageContent);
                
                // Update question record with web-accessible path
                $question->update([
                    'question_image' => "/storage/{$relativePath}"
                ]);
                
                Log::info('Question image generated and saved', [
                    'question_id' => $question->id,
                    'saved_to' => storage_path("app/{$fullPath}"),
                    'web_path' => "/storage/{$relativePath}"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate question image', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate an answer option image
     */
    protected function generateAnswerImage(Question $question, int $index, string $prompt)
    {
        try {
            // Generate image using DALL-E API
            $imageUrl = $this->generateImageWithDallE($prompt, "answer_{$index}");
            
            if ($imageUrl) {
                // Define paths clearly
                $filename = "{$question->id}_answer{$index}.png";
                $relativePath = "images/answers/{$filename}";
                $fullPath = "public/{$relativePath}";
                
                // Download image content
                $imageContent = Http::withOptions(['verify' => false])
                    ->timeout(30)
                    ->get($imageUrl)
                    ->body();
                
                // Ensure directory exists and save
                Storage::makeDirectory(dirname($fullPath));
                Storage::put($fullPath, $imageContent);
                
                // Update question record with web-accessible path
                $fieldName = "answer{$index}_image";
                $question->update([
                    $fieldName => "/storage/{$relativePath}"
                ]);
                
                Log::info('Answer image generated and saved', [
                    'question_id' => $question->id,
                    'answer_index' => $index,
                    'saved_to' => storage_path("app/{$fullPath}"),
                    'web_path' => "/storage/{$relativePath}"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to generate answer image', [
                'question_id' => $question->id,
                'answer_index' => $index,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate an image using OpenAI's DALL-E API
     */
    protected function generateImageWithDallE(string $prompt, string $type = 'question'): ?string
    {
        try {
            // Apply styling based on type
            $styledPrompt = ($type === 'question') 
                ? $this->applyQuestionImageStyle($prompt)
                : $this->applyAnswerImageStyle($prompt);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->withOptions([
                'verify' => false, // For development only
            ])
            ->timeout(60)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-2',  // Cheaper and sufficient for mobile
                'prompt' => $styledPrompt,
                'n' => 1,
                'size' => '512x512',  // Perfect for mobile screens
                'response_format' => 'url'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['url'] ?? null;
            } else {
                Log::error('DALL-E API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Error calling DALL-E API', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Apply style for question images (main images)
     */
    protected function applyQuestionImageStyle(string $basePrompt): string
    {
        // Get color configuration from database
        $config = \DB::table('configs')->first();
        
        if (!$config) {
            // Fallback if no config exists
            return $basePrompt . ", educational diagram style, clean and simple, transparent background (or pure white #FFFFFF if transparency not possible)";
        }
        
        // Apply the hand-drawn doodle style with configured colors
        return $basePrompt . ", **hand-drawn doodle sticker style** with **thick black outlines**, " .
               "**flat colors** and a **limited palette** ({$config->main_color} as the main color, {$config->secondary_color} Accent color " .
               "for highlights and warnings Tertiary Color {$config->tertiary_color}). **Slightly wobbly, sketchy lines**, " .
               "**minimal shading**, simple **flat tabletop shape**, small soft shadow under objects only, " .
               "**transparent background preferred (or pure white #FFFFFF background if transparency not possible)**, no background textures or patterns.";
    }

    /**
     * Apply style for answer images (option images)
     */
    protected function applyAnswerImageStyle(string $basePrompt): string
    {
        return "Create a playful cartoon illustration of " . $basePrompt . ". **Hand-drawn doodle/sticker style**; " .
               "**bold, uneven black outlines**; **flat cel colors** (yellow, red, black, white, tiny light-blue highlight); " .
               "**no gradients**, **no realistic textures**. Lines should look **loose and imperfect**, like a quick " .
               "marker sketch. Object only with **tiny drop shadow** under it. " .
               "Composition: **three-quarter view**, centered. " .
               "**IMPORTANT: Use transparent background if possible, otherwise pure white background (#FFFFFF)**, no background elements, no background textures. " .
               "Overall feel: **fun, naïve, icon-like**.";
    }

    /**
     * Handle job failure after all retries
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Image generation job failed after retries', [
            'question_id' => $this->questionId,
            'error' => $exception->getMessage()
        ]);
        
        // Optionally notify admin or update question status
        try {
            $question = Question::find($this->questionId);
            if ($question) {
                // You could add a status field to track this
                // $question->update(['image_generation_status' => 'failed']);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update question after job failure', [
                'question_id' => $this->questionId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine the time to wait before retrying
     */
    public function backoff(): array
    {
        return [60, 120, 300]; // Wait 1 min, 2 min, 5 min between retries
    }
}