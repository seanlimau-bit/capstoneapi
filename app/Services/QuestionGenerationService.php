<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\Question;
use App\Models\Difficulty;
use App\Models\Type;
use App\Models\Hint;
use App\Models\Solution;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\GenerateQuestionImagesJob;
//use App\Services\ImagePromptService;

class QuestionGenerationService
{
    protected $openaiApiKey;
    
  //  protected ImagePromptService $imageService;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
//        $this->imageService = $imageService;
    }

   /**
 * Generate questions by skill ID (for backward compatibility)
 */
/**
 * Generate questions by skill ID (for backward compatibility)
 */
    public function generateQuestionsBySkillId($skillId, $numberOfQuestions = 10, array $options = [])
    {
        $skill = Skill::findOrFail($skillId);
        return $this->generateForSkill($skill, $numberOfQuestions, $options);
    }
    /**
     * Generate variations by question ID (for backward compatibility)
     */
    public function generateQuestionVariationsById($questionId, $numberOfVariations = 5, array $options = [])
    {
        $question = Question::findOrFail($questionId);
        return $this->generateVariations($question, $numberOfVariations, $options);
    }
/**
     * Generate questions and return actual Question model instances
     */
    public function generateForSkill(Skill $skill, int $count, array $options = []): array
    {

        try {
                // Validate inputs
            if (!$this->openaiApiKey) {
                throw new \Exception('OpenAI API key not configured');
            }

            if ($count < 1 || $count > 50) {
                throw new \Exception('Number of questions must be between 1 and 50');
            }

            $difficulty = $options['difficulty'] ?? 'auto';
            $focusAreas = $options['focus_areas'] ?? '';
                // Check if skill has adequate description
            if (empty($skill->description) || strlen($skill->description) < 10) {
                Log::warning('Skill has inadequate description', [
                    'skill_id' => $skill->id,
                    'description_length' => strlen($skill->description ?? '')
                ]);
                throw new \Exception('Skill description is too short. Please provide a detailed description.');
            }

                // Get sample questions for context
            $sampleQuestions = $this->getSampleQuestions($skill);

                // Build the prompt
            $prompt = $this->buildPrompt($skill, $count, $difficulty, ['mixed'], $focusAreas, $sampleQuestions);

            Log::info('Sending request to OpenAI', [
                'skill_id' => $skill->id,
                'prompt_length' => strlen($prompt)
            ]);

                // Call OpenAI API
            $aiResponse = $this->callOpenAI($prompt);

                // Parse the response into structured data
            $questionData = $this->parseAIResponse($aiResponse);

            if (empty($questionData)) {
                Log::warning('AI returned no valid question data', [
                    'skill_id' => $skill->id,
                    'ai_response_preview' => substr($aiResponse, 0, 200)
                ]);
                return [];
            }

                // Create Question objects and save to database
            $questions = $this->saveQuestionsToDatabase($questionData, $skill, "AI by skill");

            Log::info('Questions successfully generated and saved', [
                'skill_id' => $skill->id,
                'requested' => $count,
                'generated' => count($questions)
            ]);

            return $questions;

        } catch (\Exception $e) {
            Log::error('Question generation failed in service', [
                'skill_id' => $skill->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate variations of an existing question using AI
     */
    public function generateVariations(Question $question, int $count, array $options = []): array
    {
        try {
            // Validate inputs
            if (!$this->openaiApiKey) {
                throw new \Exception('OpenAI API key not configured');
            }

            if ($count < 1 || $count > 20) {
                throw new \Exception('Number of variations must be between 1 and 20');
            }

            // Load necessary relationships
            $question->load(['skill', 'difficulty', 'type']);

            // Extract options with defaults
            $difficulty = $options['difficulty'] ?? 'same'; // 'same' means use original difficulty
            $focusAreas = $options['focus_areas'] ?? '';
            $questionTypes = $options['question_types'] ?? 'same'; // 'same' means use original type

            // Build the AI prompt for generating question variations
            $prompt = $this->buildVariationPrompt(
                $question, 
                $count, 
                $questionTypes, 
                $focusAreas
            );

            // Call OpenAI API
            $aiResponse = $this->callOpenAI($prompt);
            
            // Parse the AI response
            $parsedVariations = $this->parseVariationsFromAI($aiResponse);

            if (empty($parsedVariations)) {
                return [];
            }

            // Create and save the variation questions
            $savedVariations = $this->saveVariationsToDatabase($question, $parsedVariations);
            return $savedVariations;

        } catch (\Exception $e) {
            Log::error('Variation generation failed', [
                'question_id' => $question->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Build AI prompt for generating question variations
     */
    protected function buildVariationPrompt(
        Question $originalQuestion, 
        int $numberOfVariations, 
        string $questionTypes, 
        string $focusAreas
    ): string {
        $skill = $originalQuestion->skill;
        $originalDifficulty = $originalQuestion->difficulty->difficulty ?? 'Medium';
        $originalType = $originalQuestion->type->type ?? 'MCQ';

        $difficultyMapping = $this->getDifficultyMapping();
        $typeMapping = $this->getTypeMapping();

        $prompt = "You are an expert educator creating variations of an existing question.\n\n";

        $prompt .= "**ORIGINAL QUESTION CONTEXT:**\n";
        $prompt .= "Skill: {$skill->skill}\n";
        $prompt .= "Skill Description: {$skill->description}\n";
        $prompt .= "Original Question: {$originalQuestion->question}\n";

    // Handle answer format based on question type
        if ($originalType === 'MCQ') {
            $prompt .= "Original Answer Options:\n";
            $prompt .= "A) {$originalQuestion->answer0}\n";
            $prompt .= "B) {$originalQuestion->answer1}\n";
            $prompt .= "C) {$originalQuestion->answer2}\n";
            $prompt .= "D) {$originalQuestion->answer3}\n";

            if ($originalQuestion->correct_answer >= 0 && $originalQuestion->correct_answer <= 3) {
                $correctLabels = ['A', 'B', 'C', 'D'];
                $correctLabel = $correctLabels[$originalQuestion->correct_answer];
                $prompt .= "Correct Answer: {$correctLabel}\n";
            }
        } else {
            $prompt .= "Original Question Type: {$originalType}\n";
            $prompt .= "Expected Answer: {$originalQuestion->answer0}\n";
            $prompt .= "Correct Answer Value: {$originalQuestion->correct_answer}\n";
        }

        if ($originalQuestion->explanation) {
            $prompt .= "Original Explanation: {$originalQuestion->explanation}\n";
        }

        $prompt .= "Original Difficulty: {$originalDifficulty}\n";
        $prompt .= "Original Type: {$originalType}\n\n";

        $prompt .= "**VARIATION REQUIREMENTS:**\n";
        $prompt .= "- Create {$numberOfVariations} variations that test the SAME SKILL/CONCEPT as the original question\n";
        $prompt .= "- Use DIFFERENT examples, words, numbers, or scenarios - do NOT repeat the same content\n";
        $prompt .= "- If the original asks about counting letters in 'baby', create questions about counting letters in OTHER words\n";
        $prompt .= "- If the original asks about a math problem with specific numbers, use DIFFERENT numbers\n";
        $prompt .= "- Keep the SAME question type: {$originalType}\n";
        $prompt .= "- Target difficulty: {$difficulty}\n";

        if ($focusAreas) {
            $prompt .= "- Focus areas: {$focusAreas}\n";
        }

        $prompt .= "- Each variation should test the same underlying skill but with completely different content\n";
        $prompt .= "- Maintain the educational value while providing fresh examples\n";
        $prompt .= "- ALWAYS provide a clear explanation for the answer\n\n";

        $prompt .= "**DIFFICULTY MAPPING:**\n";
        $prompt .= "{$difficultyMapping}\n\n";

        $prompt .= "**QUESTION TYPE MAPPING:**\n";
        $prompt .= "{$typeMapping}\n\n";

    // Different JSON format based on question type
        if ($originalType === 'MCQ') {
            $prompt .= "**REQUIRED JSON FORMAT (MCQ):**\n";
            $prompt .= "```json\n";
            $prompt .= "{\n";
            $prompt .= "  \"variations\": [\n";
            $prompt .= "    {\n";
            $prompt .= "      \"question\": \"Your variation question with different content\",\n";
            $prompt .= "      \"answers\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],\n";
            $prompt .= "      \"correct_answer\": 0,\n";
            $prompt .= "      \"explanation\": \"Detailed explanation\",\n";
            $prompt .= "      \"difficulty_id\": {$originalQuestion->difficulty_id},\n";
            $prompt .= "      \"type_id\": 1\n";
            $prompt .= "    }\n";
            $prompt .= "  ]\n";
            $prompt .= "}\n";
            $prompt .= "```\n\n";
        } else {
        // For Number/FIB questions
            $prompt .= "**REQUIRED JSON FORMAT (Number/FIB):**\n";
            $prompt .= "```json\n";
            $prompt .= "{\n";
            $prompt .= "  \"variations\": [\n";
            $prompt .= "    {\n";
            $prompt .= "      \"question\": \"Your variation question with different content\",\n";
            $prompt .= "      \"answers\": [\"{$originalQuestion->answer0}\", \"\", \"\", \"\"],\n";
            $prompt .= "      \"correct_answer\": {$originalQuestion->correct_answer},\n";
            $prompt .= "      \"explanation\": \"Detailed explanation\",\n";
            $prompt .= "      \"difficulty_id\": {$originalQuestion->difficulty_id},\n";
            $prompt .= "      \"type_id\": {$originalQuestion->type_id}\n";
            $prompt .= "    }\n";
            $prompt .= "  ]\n";
            $prompt .= "}\n";
            $prompt .= "```\n\n";
        }

        $prompt .= "**CRITICAL:** Return ONLY the JSON. Generate exactly {$numberOfVariations} variations with DIFFERENT content but the SAME skill. Each question MUST have a thorough explanation.";

        return $prompt;
    }

    /**
     * Parse variations from AI response
     */
    protected function parseVariationsFromAI(string $aiResponse): array
    {
        try {
            Log::info('Parsing AI variation response', [
                'response_length' => strlen($aiResponse),
                'response_preview' => substr($aiResponse, 0, 200)
            ]);

            // Clean up the response - remove any markdown formatting or extra content
            $cleanContent = preg_replace('/```json\s*/', '', $aiResponse);
            $cleanContent = preg_replace('/```\s*$/', '', $cleanContent);
            $cleanContent = trim($cleanContent);

            // Find JSON boundaries
            $jsonStart = strpos($cleanContent, '{');
            $jsonEnd = strrpos($cleanContent, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('No valid JSON found in AI variation response');
            }
            
            $jsonString = substr($cleanContent, $jsonStart, $jsonEnd - $jsonStart + 1);

            $decoded = json_decode($jsonString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error for variations', [
                    'error' => json_last_error_msg(),
                    'json_preview' => substr($jsonString, 0, 500)
                ]);
                throw new \Exception('Invalid JSON from AI: ' . json_last_error_msg());
            }

            if (!$decoded || !isset($decoded['variations']) || !is_array($decoded['variations'])) {
                Log::warning('Invalid JSON structure from AI response', [
                    'decoded' => $decoded
                ]);
                throw new \Exception('AI response missing variations array');
            }

            $validVariations = [];
            foreach ($decoded['variations'] as $index => $variation) {
                if ($this->isValidVariationData($variation, $index)) {
                    $validVariations[] = $variation;
                } else {
                    Log::warning('Invalid variation data from AI', [
                        'index' => $index,
                        'variation' => $variation
                    ]);
                }
            }

            Log::info('Validated variation data', [
                'total_variations' => count($decoded['variations']),
                'valid_variations' => count($validVariations)
            ]);

            return $validVariations;

        } catch (\Exception $e) {
            Log::error('Failed to parse AI response for variations', [
                'error' => $e->getMessage(),
                'ai_content' => substr($aiResponse, 0, 500)
            ]);
            throw $e;
        }
    }

    /**
     * Validate variation data structure
     */
    protected function isValidVariationData(array $data, int $index): bool
    {
        $required = ['question', 'answers', 'correct_answer', 'explanation'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Log::warning("Variation {$index} missing required field: {$field}");
                return false;
            }
        }

    // For non-MCQ questions, answers might not be exactly 4 items
        if (!is_array($data['answers'])) {
            Log::warning("Variation {$index} answers must be an array");
            return false;
        }

    // More flexible validation for different question types
        if (count($data['answers']) !== 4) {
        // For non-MCQ, we might have different answer structures
            Log::info("Variation {$index} has " . count($data['answers']) . " answers instead of 4");
        // Don't fail validation for this, just log it
        }

    // For non-MCQ questions, correct_answer might be > 3
        if (!is_numeric($data['correct_answer'])) {
            Log::warning("Variation {$index} has invalid correct_answer: " . $data['correct_answer']);
            return false;
        }

        if (empty(trim($data['explanation']))) {
            Log::warning("Variation {$index} has empty explanation");
            return false;
        }

        return true;
    }

    /**
     * Save variation questions to database
     */
    protected function saveVariationsToDatabase(Question $originalQuestion, array $variationsData): array
    {
        $savedVariations = [];

    // Check if original question has images (define these variables first)
        $hasQuestionImage = !empty($originalQuestion->question_image);
        $hasAnswerImages = !empty($originalQuestion->answer0_image) || 
        !empty($originalQuestion->answer1_image) || 
        !empty($originalQuestion->answer2_image) || 
        !empty($originalQuestion->answer3_image);

        DB::beginTransaction();

        try {
            foreach ($variationsData as $index => $variationData) {
            // Get or create difficulty
                $difficultyId = $variationData['difficulty_id'] ?? $originalQuestion->difficulty_id;
                if (isset($variationData['difficulty_id'])) {
                    $difficulty = Difficulty::find($difficultyId);
                    if (!$difficulty) {
                        $difficultyId = $originalQuestion->difficulty_id;
                    }
                }

            // Get or create question type  
                $typeId = $variationData['type_id'] ?? $originalQuestion->type_id;
                if (isset($variationData['type_id'])) {
                    $type = Type::find($typeId);
                    if (!$type) {
                        $typeId = $originalQuestion->type_id;
                    }
                }

            // Prepare question fields with image filenames
                $questionFields = [
                    'skill_id' => $originalQuestion->skill_id,
                    'difficulty_id' => $difficultyId,
                    'type_id' => $typeId,
                    'user_id' => auth()->id() ?? $originalQuestion->user_id ?? 1,
                    'question' => $variationData['question'],
                    'answer0' => $variationData['answers'][0],
                    'answer1' => $variationData['answers'][1],
                    'answer2' => $variationData['answers'][2],
                    'answer3' => $variationData['answers'][3],
                    'correct_answer' => (int) $variationData['correct_answer'],
                    'explanation' => $variationData['explanation'],
                    'status_id' => 3,
                    'qa_status' => 'ai_generated',
                    'source' => "AI generated from source Question->{$originalQuestion->id}",
                    'calculator' => null,
                ];

            // Create the variation question first to get ID
                $variation = Question::create($questionFields);

            // Now update with image filenames using the actual question ID
                $imageUpdates = [];
                if ($hasQuestionImage) {
                    $imageUpdates['question_image'] = "{$variation->id}.png";
                }
                if ($hasAnswerImages) {
                    $imageUpdates['answer0_image'] = "{$variation->id}_answer0.png";
                    $imageUpdates['answer1_image'] = "{$variation->id}_answer1.png";
                    $imageUpdates['answer2_image'] = "{$variation->id}_answer2.png";
                    $imageUpdates['answer3_image'] = "{$variation->id}_answer3.png";
                }

            // Update question with image filenames if needed
                if (!empty($imageUpdates)) {
                    $variation->update($imageUpdates);
                }

            // Create solution in solutions table
                if (isset($variationData['solution'])) {
                    Solution::create([
                        'question_id' => $variation->id,
                        'user_id' => auth()->id() ?? $originalQuestion->user_id ?? 1,
                        'solution' => $variationData['solution'],
                        'status_id' => 3,
                    ]);
                }

            // Store hint in hint field (assuming you added the column)
                if (isset($variationData['hint'])) {
                    $variation->update(['hint' => $variationData['hint']]);
                }

            // Dispatch image generation job if needed
                if ($hasQuestionImage || $hasAnswerImages) {
                    $imagePrompts = [];
                    if (isset($variationData['question_image_prompt'])) {
                        $imagePrompts['question_image_prompt'] = $variationData['question_image_prompt'];
                    }
                    if (isset($variationData['answer_image_prompts'])) {
                        $imagePrompts['answer_image_prompts'] = $variationData['answer_image_prompts'];
                    }

                // Only dispatch if we have prompts
                    if (!empty($imagePrompts)) {
                        dispatch(new GenerateQuestionImagesJob($variation->id, $imagePrompts));
                    }
                }

            // Load relationships for return
                $variation->load(['difficulty', 'type', 'skill', 'solutions']);
                $savedVariations[] = $variation;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to save variation questions to database', [
                'original_question_id' => $originalQuestion->id,
                'error' => $e->getMessage(),
                'saved_count' => count($savedVariations)
            ]);
            throw new \Exception('Failed to save variations: ' . $e->getMessage());
        }

        return $savedVariations;
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $prompt): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])
            ->withOptions([
                'verify' => config('app.env') === 'production', // Only verify SSL in production
            ])
            ->timeout(180)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an educational content creator. Generate questions in the exact JSON format requested. Always respond with valid JSON only. Always include detailed explanations for every question.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'response_format' => ['type' => 'json_object'] 
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                Log::error('OpenAI API request failed', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                    'body' => $errorBody
                ]);
                
                if ($response->status() === 429) {
                    throw new \Exception('OpenAI API rate limit exceeded. Please try again in a few minutes.');
                } elseif ($response->status() === 401) {
                    throw new \Exception('OpenAI API authentication failed. Please check your API key.');
                } else {
                    throw new \Exception('OpenAI API request failed: ' . $response->status() . ' - ' . $errorBody);
                }
            }

            $responseData = $response->json();
            
            if (!isset($responseData['choices'][0]['message']['content'])) {
                Log::error('Invalid OpenAI response structure', ['response' => $responseData]);
                throw new \Exception('Invalid response structure from OpenAI');
            }

            return $responseData['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            Log::error('OpenAI API call failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Build prompt for AI
     */

    protected function buildPrompt(
        Skill $skill,
        int $count,
        string $difficulty,
        array $types,
        ?string $focusAreas,
        array $sampleQuestions
    ): string {
        if (!$skill->relationLoaded('tracks')) {
            $skill->load('tracks.level');
        }

        $trackInfo = $skill->tracks->map(function($t){
            $levelInfo = optional($t->level)->description;
            return $t->track . ($levelInfo ? ' (' . $levelInfo . ')' : '');
        })->implode(', ');

        $difficultyMapping = $this->getDifficultyMapping();
        $typeMapping = "1 = Multiple Choice (MCQ)\n2 = Fill-in-the-Blank (Numbers)";
        $sampleText = $this->formatSampleQuestions($sampleQuestions);
        $halfMcq = max(1, (int) floor($count / 2));
        $halfFib = max(0, $count - $halfMcq);

        $focusLine = $focusAreas ? "**Focus Areas:** {$focusAreas}\n" : "";

        // Using concatenation instead of heredoc to avoid potential issues
        $prompt = "You are an expert K–12 math item writer, and know how children work in Math. Generate exactly {$count} high quality questions for the skill below.\n\n";
        
        $prompt .= "**SKILL CONTEXT**\n";
        $prompt .= "- Skill: {$skill->skill}\n";
        $prompt .= "- Description: {$skill->description}\n";
        $prompt .= "- Learning Tracks: {$trackInfo}\n";
        $prompt .= $focusLine . "\n";
        
        $prompt .= "**DIFFICULTY MAPPING (use these ids):**\n";
        $prompt .= "{$difficultyMapping}\n\n";
        
        $prompt .= "**TYPE MAPPING (use these ids):**\n";
        $prompt .= "{$typeMapping}\n\n";
        
        $prompt .= "**SPLIT OF QUESTION TYPES**\n";
        $prompt .= "- Create ~{$halfMcq} MCQs (type_id=1)\n";
        $prompt .= "- Create ~{$halfFib} FIB Numbers (type_id=2)\n\n";
        
        $prompt .= "**GLOBAL REQUIREMENTS (ALL QUESTIONS)**\n";
        $prompt .= "- Math notation must use KaTeX inline/block with double-dollar delimiters: \$\$\\frac{a}{b}\$\$, \$\$x^2\$\$, etc.\n by only where really required" ;
        $prompt .= "- Difficulty must be one of the difficulty_id values above (1..3). If not obvious, pick mixed across 1..3.\n";
        $prompt .= "- Every question must include:\n";
        $prompt .= "  - \"explanation\": short reason why the correct answer is right.\n";
        $prompt .= "  - \"solution\": a detailed, kid-friendly, step-by-step solution.\n";
        $prompt .= "  - \"hints\": exactly 3 items with increasing scaffolding:\n";
        $prompt .= "    [{\"level\":1,\"text\":\"minimal nudge\"}, {\"level\":2,\"text\":\"medium clue\"}, {\"level\":3,\"text\":\"almost shows steps, not the final answer\"}]\n";
        $prompt .= "- Image prompts: ONLY include when the question genuinely benefits from a visual.\n";
        $prompt .= "  - Examples: geometry shapes, bar charts, fraction strips, counting objects, visual patterns.\n";
        $prompt .= "  - If images help: set \"needs_question_image\": true and provide:\n";
        $prompt .= "    - \"question_image_prompt\": \"detailed description of simple worksheet-style diagram (black & white, no photo realism)\"\n";
        $prompt .= "    - For MCQ visual options: \"answer_image_prompts\": [prompt0, prompt1, prompt2, prompt3] (null where not needed)\n";
        $prompt .= "  - If images help: set \"needs_question_image\": true and create image prompt fields\n";
        $prompt .= "  - Image prompts must be self-contained, focused on educational content only\n\n";
        $prompt .= "**IMAGE GENERATION RULES:**\n";
        $prompt .= "- For VISUAL questions (geometry, counting, comparing objects, angles, ratios):\n";
        $prompt .= "  1. Set needs_question_image: true\n";
        $prompt .= "  2. question_image_prompt: Describe ALL objects that answers will reference\n";
        $prompt .= "  3. answer_image_prompts: Each should reference specific objects from the main image\n\n";

        $prompt .= "**Example of coordinated image prompts:**\n";
        $prompt .= "Question: 'Which triangle is the biggest?'\n";
        $prompt .= "needs_question_image: true\n";
        $prompt .= "question_image_prompt: 'Three triangles arranged horizontally: small blue triangle on left (2cm base), medium red triangle in center (4cm base), large yellow triangle on right (6cm base). All triangles clearly labeled with their colors.'\n";
        $prompt .= "answer_image_prompts: [\n";
        $prompt .= "  'Single blue triangle, 2cm base',\n";
        $prompt .= "  'Single red triangle, 4cm base',\n";
        $prompt .= "  'Single yellow triangle, 6cm base',\n";
        $prompt .= "  null\n";
        $prompt .= "]\n\n";
        $prompt .= "**CRITICAL IMAGE RULES:**\n";
        $prompt .= "1. Create images if the question makes no sense without images\n";
        $prompt .= "2. ONLY create images for visual/spatial questions (geometry, counting objects, comparing sizes, patterns)\n";
        $prompt .= "3. When images ARE needed:\n";
        $prompt .= "   - question_image_prompt must describe ALL objects that will be referenced in answers\n";
        $prompt .= "   - answer_image_prompts must match specific objects from the question image\n";
        $prompt .= "   - Include colors, sizes, positions, and labels consistently\n";
        $prompt .= "4. For MCQ with visual answers: each answer_image_prompt corresponds to that answer option\n";
        $prompt .= "5. For non-visual questions: set needs_question_image: false and all image prompts to null\n\n";

        $prompt .= "- For NON-VISUAL questions: needs_question_image: false, all image prompts: null\n";
        $prompt .= "- CRITICAL: Answer images must match objects shown in question image\n";
        $prompt .= "- Include sizes, colors, positions, and labels in prompts\n\n";
        
        $prompt .= "**MCQ-SPECIFIC RULES (type_id=1)**\n";
        $prompt .= "- 2 to 4 answer options.\n";
        $prompt .= "- \"answers\" is an array of option texts; do not include labels like A/B/C.\n";
        $prompt .= "- \"correct_answer\" is the 0-based index of the correct option.\n";
        $prompt .= "- About 20% of MCQs must include an option exactly \"None of the above\".\n";
        $prompt .= "- Options must be short (≤ 100 chars), plausible distractors, and unambiguous.\n\n";
        
        $prompt .= "**FIB NUMBERS-SPECIFIC RULES (type_id=2)**\n";
        $prompt .= "- Question text may contain up to 4 blanks, written as [?] placeholders.\n";
        $prompt .= "- **ANSWERS MUST BE INTEGERS ONLY** - no fractions, decimals, or text.\n";
        $prompt .= "- Provide \"answers\" array with the correct numeric answers for blanks in order.\n";
        $prompt .= "- Set \"correct_answer\" equal to the first blank's numeric value.\n";
        $prompt .= "- Most FIB questions should have 1 blank; but feel free to use up to 4 only when educationally meaningful.\n";
        $prompt .= "- Integer answers should be reasonable for K-12 students (typically -1000 to 1000).\n\n";
        
        if ($sampleText !== "No sample questions available - will generate based on skill description.") {
            $prompt .= "**SAMPLE QUESTIONS FOR TONE (NOT TO BE COPIED)**\n";
            $prompt .= "{$sampleText}\n\n";
        }
        
        $prompt .= "**OUTPUT FORMAT (STRICT JSON ONLY)**\n";
        $prompt .= "Return ONLY valid JSON with this exact schema (no markdown fences):\n\n";
        
         $jsonTemplate = '{
          "questions": [
            {
              "skill_id": ' . $skill->id . ',
              "source": "AI by skill",
              "qa_status": "ai_generated",
              "status_id": 3,
              "type_id": 1,
              "difficulty_id": 2,
              "question": "What is 3/4 + 1/4?",
              "answers": ["1", "1/2", "3/4", "None of the above"],
              "correct_answer": 0,
              "explanation": "Add fractions with the same denominator: 3/4 + 1/4 = 4/4 = 1.",
              "hints": [
                {"hint_level": 1, "hint_text": "Are the denominators the same?"},
                {"hint_level": 2, "hint_text": "Add the numerators: 3 + 1 = 4"},
                {"hint_level": 3, "hint_text": "4/4 = 1. A whole is four quarters."}
              ],
              "solutions": [
                {
                  "method": "standard",
                  "steps": ["Identify same denominators.", "Add numerators: 3 + 1 = 4.", "Result: 4/4 = 1."],
                  "final_answer": "1"
                }
              ],
              "needs_question_image": false,
              "question_image_prompt": null,
              "answer_image_prompts": [null, null, null, null]
            },
            {
              "skill_id": ' . $skill->id . ',
              "source": "AI by skill",
              "qa_status": "ai_generated",
              "status_id": 3,
              "type_id": 1,
              "difficulty_id": 1,
              "question": "Which triangle is the biggest?",
              "answers": ["Blue triangle", "Red triangle", "Yellow triangle", "Green triangle"],
              "correct_answer": 2,
              "explanation": "The yellow triangle has the largest area compared to the other triangles.",
              "hints": [
                {"hint_level": 1, "hint_text": "Look at the size of each triangle"},
                {"hint_level": 2, "hint_text": "Compare the base and height of each triangle"},
                {"hint_level": 3, "hint_text": "The yellow triangle is clearly larger than the others"}
              ],
              "solutions": [
                {
                  "method": "visual comparison",
                  "steps": ["Observe all four triangles", "Compare their sizes", "Yellow triangle is the largest"],
                  "final_answer": "Yellow triangle"
                }
              ],
              "needs_question_image": true,
              "question_image_prompt": "Four triangles arranged in a 2x2 grid on white background: top-left is small blue triangle (2cm base, 2cm height), top-right is medium red triangle (3cm base, 3cm height), bottom-left is large yellow triangle (5cm base, 5cm height), bottom-right is small green triangle (2cm base, 2cm height). Each triangle clearly colored and labeled with its color name below it.",
              "answer_image_prompts": [
                "Single blue triangle, 2cm base, 2cm height, colored blue",
                "Single red triangle, 3cm base, 3cm height, colored red",
                "Single yellow triangle, 5cm base, 5cm height, colored yellow",
                "Single green triangle, 2cm base, 2cm height, colored green"
              ]
            },
            {
              "skill_id": ' . $skill->id . ',
              "source": "AI by skill",
              "qa_status": "ai_generated",
              "status_id": 3,
              "type_id": 1,
              "difficulty_id": 2,
              "question": "How many circles are shown in the picture?",
              "answers": ["5", "6", "7", "8"],
              "correct_answer": 1,
              "explanation": "Count all circles carefully: there are 6 circles total in the image.",
              "hints": [
                {"hint_level": 1, "hint_text": "Count each circle one by one"},
                {"hint_level": 2, "hint_text": "There are 3 circles on top and 3 circles on bottom"},
                {"hint_level": 3, "hint_text": "3 + 3 = 6 circles total"}
              ],
              "solutions": [
                {
                  "method": "counting",
                  "steps": ["Count top row: 3 circles", "Count bottom row: 3 circles", "Add: 3 + 3 = 6"],
                  "final_answer": "6"
                }
              ],
              "needs_question_image": true,
              "question_image_prompt": "Six circles arranged in 2 rows of 3 circles each. Top row has 3 blue circles. Bottom row has 3 red circles. All circles are the same size (2cm diameter) with clear spacing between them on a white background.",
              "answer_image_prompts": [null, null, null, null]
            }
          ]
        }';
        $prompt .= $jsonTemplate . "\n\n";
        
        $prompt .= "**Additional Constraints**\n";
        $prompt .= "- NEVER produce malformed JSON.\n";
        $prompt .= "- Use KaTeX when absolutely necessary.\n";
        $prompt .= "- All content must be age-appropriate and suitable for a school setting.\n";
        $prompt .= "- FIB answers must be INTEGERS ONLY (no decimals, fractions, or text).\n";
        $prompt .= "- Escape quotes properly.\n";
        $prompt .= "- Do NOT include markdown code fences.\n";
        $prompt .= "- EXACTLY {$count} questions.\n\n";
        
        $prompt .= "Generate exactly {$count} questions, mixing difficulties across 1..3. Use \$\$...\$\$ for all math symbols. Output JSON only.\n\n";
        $prompt .= "**Generate now.**";
        
        return $prompt;
    }
    /**
     * Parse AI response into structured question data
     */
    protected function parseAIResponse(string $aiResponse): array
    {
        try {
            Log::info('Raw AI Response', [
                'length' => strlen($aiResponse),
                'first_100_chars' => substr($aiResponse, 0, 100),
                'last_100_chars' => substr($aiResponse, -100)
            ]);
            
            // Clean the response - find JSON boundaries
            $jsonStart = strpos($aiResponse, '{');
            $jsonEnd = strrpos($aiResponse, '}');
            
            if ($jsonStart === false || $jsonEnd === false) {
                Log::error('No JSON boundaries found', [
                    'has_open_brace' => strpos($aiResponse, '{') !== false,
                    'has_close_brace' => strpos($aiResponse, '}') !== false,
                    'response' => $aiResponse
                ]);
                throw new \Exception('No valid JSON found in AI response');
            }
            
            $jsonString = substr($aiResponse, $jsonStart, $jsonEnd - $jsonStart + 1);
            
            Log::info('Extracted JSON', [
                'json_length' => strlen($jsonString),
                'json_preview' => substr($jsonString, 0, 200)
            ]);
            
            // Check if json string is empty
            if (empty(trim($jsonString))) {
                throw new \Exception('Extracted JSON string is empty');
            }
            
            $data = json_decode($jsonString, true);
            
            // Check both the decoded data AND the error
            if ($data === null) {
                $error = json_last_error();
                $errorMsg = json_last_error_msg();
                
                Log::error('JSON decode returned null', [
                    'error_code' => $error,
                    'error_msg' => $errorMsg,
                    'json_string' => substr($jsonString, 0, 1000)
                ]);
                
                // More specific error message
                if ($error === JSON_ERROR_NONE) {
                    throw new \Exception('JSON decode returned null despite no error - possibly empty or invalid JSON string');
                } else {
                    throw new \Exception('Invalid JSON from AI: ' . $errorMsg);
                }
            }
            
            if (!isset($data['questions']) || !is_array($data['questions'])) {
                Log::error('Missing questions array', [
                    'data_keys' => array_keys($data ?? []),
                    'data' => $data
                ]);
                throw new \Exception('AI response missing questions array');
            }

            // Validate each question
            $validQuestions = [];
            foreach ($data['questions'] as $index => $questionData) {
                if ($this->validateQuestionData($questionData, $index)) {
                    $validQuestions[] = $questionData;
                }
            }

            Log::info('Questions validated', [
                'total' => count($data['questions']),
                'valid' => count($validQuestions)
            ]);

            return $validQuestions;

        } catch (\Exception $e) {
            Log::error('Error parsing AI response', [
                'error' => $e->getMessage(),
                'response_preview' => substr($aiResponse, 0, 500)
            ]);
            throw $e;
        }
    }

    /**
     * Validate individual question data
     */
    protected function validateQuestionData(array $questionData, int $index): bool
    {
        $required = ['question', 'answers', 'correct_answer', 'difficulty_id', 'type_id', 'explanation'];
        // Around line 600 in saveQuestionsToDatabase
        Log::info('Question Data being saved:', [
            'has_needs_question_image' => isset($data['needs_question_image']),
            'needs_question_image_value' => $data['needs_question_image'] ?? 'not set',
            'has_prompt' => isset($data['question_image_prompt'])
        ]);
        
        foreach ($required as $field) {
            if (!isset($questionData[$field])) {
                Log::warning("Question {$index} missing required field: {$field}");
                return false;
            }
        }

        if (!is_array($questionData['answers']) || count($questionData['answers']) !== 4) {
            Log::warning("Question {$index} must have exactly 4 answers");
            return false;
        }

        if (!in_array($questionData['correct_answer'], [0, 1, 2, 3])) {
            Log::warning("Question {$index} has invalid correct_answer: " . $questionData['correct_answer']);
            return false;
        }

        // Validate explanation is not empty
        if (empty(trim($questionData['explanation']))) {
            Log::warning("Question {$index} has empty explanation");
            return false;
        }

        return true;
    }

    /**
     * Save questions to database and return Question objects
     */

    public function saveQuestionsToDatabase(array $questionsData): array
    {
        $savedQuestions = [];
        $hints = [];
        $solutions = [];
        $imageJobs = [];

        DB::beginTransaction();

        try {
            foreach ($questionsData as $index => $data) {
                // Log what we're receiving for debugging
                Log::info('Processing question data', [
                    'index' => $index,
                    'has_question_image_prompt' => isset($data['question_image_prompt']),
                    'has_needs_question_image' => isset($data['needs_question_image']),
                    'needs_question_image_value' => $data['needs_question_image'] ?? 'not set'
                ]);

                // Convert answers array to individual fields
                $answers = array_pad($data['answers'] ?? [], 4, '');

                // Determine correct_answer based on type
                $typeId = $data['type_id'] ?? 1;
                $correctAnswer = ($typeId == 1) ? $data['correct_answer'] : null;

                // Create and save the question
                $question = Question::create([
                    'skill_id' => $data['skill_id'],
                    'source' => $data['source'] ?? 'AI',
                    'question' => $data['question'],
                    'answer0' => $answers[0],
                    'answer1' => $answers[1],
                    'answer2' => $answers[2],
                    'answer3' => $answers[3],
                    'correct_answer' => $correctAnswer,
                    'explanation' => $data['explanation'] ?? null,
                    'type_id' => $typeId,
                    'difficulty_id' => $data['difficulty_id'] ?? 2,
                    'status_id' => $data['status_id'] ?? 3,
                    'qa_status' => $data['qa_status'] ?? 'ai_generated',
                    'user_id' => auth()->id() ?? 1,
                    // Don't set image paths here - let the job handle it after successful generation
                    'question_image' => null,
                    'answer0_image' => null,
                    'answer1_image' => null,
                    'answer2_image' => null,
                    'answer3_image' => null,
                ]);

                // Check for image prompts and queue job if needed
                $imagePrompts = [];
                
                // Check for question image prompt
                if (!empty($data['question_image_prompt'])) {
                    $imagePrompts['question_image_prompt'] = $data['question_image_prompt'];
                    
                    Log::info('Question needs image generation', [
                        'question_id' => $question->id,
                        'prompt' => substr($data['question_image_prompt'], 0, 100)
                    ]);
                }
                
                // Check for answer image prompts
                if (!empty($data['answer_image_prompts']) && is_array($data['answer_image_prompts'])) {
                    $answerPrompts = [];
                    foreach ($data['answer_image_prompts'] as $idx => $prompt) {
                        if (!empty($prompt) && $prompt !== null) {
                            $answerPrompts[$idx] = $prompt;
                            
                            Log::info('Answer needs image generation', [
                                'question_id' => $question->id,
                                'answer_index' => $idx,
                                'prompt' => substr($prompt, 0, 100)
                            ]);
                        }
                    }
                    if (!empty($answerPrompts)) {
                        $imagePrompts['answer_image_prompts'] = $answerPrompts;
                    }
                }

                // Queue image generation job if prompts exist
                if (!empty($imagePrompts)) {
                    $imageJobs[] = [
                        'question_id' => $question->id,
                        'prompts' => $imagePrompts
                    ];
                    
                    Log::info('Image generation job will be dispatched', [
                        'question_id' => $question->id,
                        'has_question_prompt' => isset($imagePrompts['question_image_prompt']),
                        'answer_prompts_count' => count($imagePrompts['answer_image_prompts'] ?? [])
                    ]);
                }

                // Prepare hints for bulk insert
                if (!empty($data['hints']) && is_array($data['hints'])) {
                    foreach ($data['hints'] as $hint) {
                        $level = null;
                        $text = null;
                        
                        if (is_array($hint)) {
                            // Handle both field name formats
                            $level = $hint['level'] ?? $hint['hint_level'] ?? null;
                            $text = $hint['text'] ?? $hint['hint_text'] ?? null;
                        }
                        
                        if ($level && $text) {
                            $hints[] = [
                                'question_id' => $question->id,
                                'hint_level' => $level,
                                'hint_text' => $text,
                                'user_id' => auth()->id() ?? null, // hints table has user_id field
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                // Prepare solutions for bulk insert
                if (!empty($data['solutions']) && is_array($data['solutions'])) {
                    foreach ($data['solutions'] as $solutionData) {
                        if (is_array($solutionData)) {
                            $solutionText = $this->formatSolutionText($solutionData);
                            
                            if (!empty(trim($solutionText))) {
                                $solutions[] = [
                                    'question_id' => $question->id,
                                    'user_id' => auth()->id() ?? 1,
                                    'solution' => $solutionText,
                                    'status_id' => 3,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                        }
                    }
                }

                $savedQuestions[] = $question;
            }

            // Bulk insert hints
            if (!empty($hints)) {
                Hint::insert($hints);
                Log::info('Hints inserted', ['count' => count($hints)]);
            }
            
            // Bulk insert solutions
            if (!empty($solutions)) {
                Solution::insert($solutions);
                Log::info('Solutions inserted', ['count' => count($solutions)]);
            }

            // Dispatch all image generation jobs
            foreach ($imageJobs as $job) {
                GenerateQuestionImagesJob::dispatch($question->id, $imagePrompts);
            }

            DB::commit();

            Log::info('Questions saved successfully', [
                'questions_count' => count($savedQuestions),
                'hints_count' => count($hints),
                'solutions_count' => count($solutions),
                'image_jobs_queued' => count($imageJobs),
                'question_ids' => collect($savedQuestions)->pluck('id')->toArray(),
                'timestamp' => now()->toISOString()
            ]);

            return $savedQuestions;

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to save questions to database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString()
            ]);

            throw new \Exception('Failed to save questions: ' . $e->getMessage());
        }
    }

    /**
     * Format solution text from structured data
     */
    protected function formatSolutionText(array $solutionData): string
    {
        $text = '';
        
        if (isset($solutionData['method'])) {
            $text .= "Method: " . $solutionData['method'] . "\n";
        }
        
        if (isset($solutionData['steps']) && is_array($solutionData['steps'])) {
            $text .= "Steps:\n";
            foreach ($solutionData['steps'] as $stepNum => $step) {
                $text .= ($stepNum + 1) . ". " . $step . "\n";
            }
        }
        
        if (isset($solutionData['final_answer'])) {
            $text .= "\nFinal Answer: " . $solutionData['final_answer'];
        }
        
        return $text;
    }

    /**
     * Get sample questions for context
     */
    protected function getSampleQuestions(Skill $skill): array
    {
        try {
            // Load skill with tracks to avoid N+1 queries
            $skill->load('tracks');

            // First, try to get questions from the same skill
            $sampleQuestions = Question::where('skill_id', $skill->id)
            ->whereIn('qa_status', ['approved', 'unreviewed'])
            ->with(['skill', 'difficulty', 'type'])
            ->limit(3)
            ->get();

            // If no questions exist for this skill, get from similar skills (same tracks)
            if ($sampleQuestions->isEmpty() && $skill->tracks->isNotEmpty()) {
                $trackIds = $skill->tracks->pluck('id');
                
                $sampleQuestions = Question::whereHas('skill', function($query) use ($trackIds) {
                    $query->whereHas('tracks', function($subQuery) use ($trackIds) {
                        $subQuery->whereIn('id', $trackIds);
                    });
                })
                ->where('skill_id', '!=', $skill->id)
                ->whereIn('qa_status', ['approved', 'unreviewed'])
                ->with(['skill', 'difficulty', 'type'])
                ->limit(3)
                ->get();
            }

            // If still no questions, get any recent approved questions as examples
            if ($sampleQuestions->isEmpty()) {
                $sampleQuestions = Question::whereIn('qa_status', ['approved', 'unreviewed'])
                ->with(['skill', 'difficulty', 'type'])
                ->latest()
                ->limit(3)
                ->get();
            }

            return $sampleQuestions->map(function($question) {
                return [
                    'question' => $question->question,
                    'answer0' => $question->answer0,
                    'answer1' => $question->answer1,
                    'answer2' => $question->answer2,
                    'answer3' => $question->answer3,
                    'correct_answer' => $question->correct_answer,
                    'explanation' => $question->explanation,
                    'difficulty_id' => $question->difficulty_id,
                    'type_id' => $question->type_id,
                    'skill_name' => optional($question->skill)->skill ?? 'Unknown'
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error getting sample questions', [
                'skill_id' => $skill->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Format sample questions for prompt
     */
    protected function formatSampleQuestions(array $sampleQuestions): string
    {
        if (empty($sampleQuestions)) {
            return "No sample questions available - will generate based on skill description.";
        }

        $formatted = "";
        
        foreach ($sampleQuestions as $index => $question) {
            $formatted .= "**Sample " . ($index + 1) . ":**\n";
            $formatted .= "Question: " . $question['question'] . "\n";
            $formatted .= "Answers: [" . $question['answer0'] . ", " . $question['answer1'] . ", " . $question['answer2'] . ", " . $question['answer3'] . "]\n";
            $formatted .= "Correct Answer: " . $question['correct_answer'] . "\n";
            $formatted .= "Explanation: " . ($question['explanation'] ?: 'None provided') . "\n";
            $formatted .= "Difficulty ID: " . $question['difficulty_id'] . "\n";
            $formatted .= "Type ID: " . $question['type_id'] . "\n";
            $formatted .= "From Skill: " . $question['skill_name'] . "\n\n";
        }

        return $formatted;
    }

    /**
     * Get difficulty mapping
     */
    protected function getDifficultyMapping(): string
    {
        try {
            $difficulties = Difficulty::orderBy('id')->get(['id', 'difficulty']);
            
            if ($difficulties->isEmpty()) {
                return "1 = Easy\n2 = Medium\n3 = Hard";
            }
            
            return $difficulties->map(function($diff) {
                return "{$diff->id} = {$diff->difficulty}";
            })->implode("\n");

        } catch (\Exception $e) {
            Log::warning('Error getting difficulty mapping', ['error' => $e->getMessage()]);
            return "1 = Easy\n2 = Medium\n3 = Hard";
        }
    }

    /**
     * Get type mapping
     */
    protected function getTypeMapping(): string
    {
        try {
            $types = Type::orderBy('id')->get(['id', 'type']);
            
            if ($types->isEmpty()) {
                return "1 = Multiple Choice\n2 = True/False\n3 = Short Answer";
            }
            
            return $types->map(function($type) {
                return "{$type->id} = {$type->type}";
            })->implode("\n");

        } catch (\Exception $e) {
            Log::warning('Error getting type mapping', ['error' => $e->getMessage()]);
            return "1 = Multiple Choice\n2 = True/False\n3 = Short Answer";
        }
    }

    // Legacy methods for backwards compatibility (if needed)
    public function executeSQLQuestions(array $sqlStatements, Skill $skill): int
    {
        Log::warning('executeSQLQuestions called but deprecated', [
            'skill_id' => $skill->id,
            'statement_count' => count($sqlStatements)
        ]);
        return 0;
    }

    public function saveGeneratedQuestions(array $sqlStatements, Skill $skill): int
    {
        Log::warning('saveGeneratedQuestions called but deprecated', [
            'skill_id' => $skill->id,
            'statement_count' => count($sqlStatements)
        ]);
        return 0;
    }
}