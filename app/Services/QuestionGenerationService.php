<?php
namespace App\Services;

use App\Models\Skill;
use App\Models\Question;
use App\Models\Difficulty;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QuestionGenerationService
{
    protected string $openaiApiKey;

    public function __construct()
    {
        $this->openaiApiKey = (string) config('services.openai.api_key', '');
    }

    /**
     * Generate NEW questions for a skill (no images).
     * Guarantees explanation, exactly 3 hints, and ≥1 solution are present and saved.
     */
    public function generateForSkill(Skill $skill, int $count, array $options = []): array
    {
        $this->assertPrereqs($skill, $count, 1, 50);

        $ageBand    = $options['age_band']    ?? $this->inferAgeBandFromSkill($skill);
        $readingLvl = $options['reading_lvl'] ?? 'CEFR A2–B1';
        $focus      = $options['focus_areas'] ?? '';

        $samples = $this->getSampleQuestions($skill);
        $prompt  = $this->buildAuthorPrompt($skill, $count, $ageBand, $readingLvl, $focus, $samples);

        $json      = $this->callOpenAIJson($prompt, $this->systemJsonInstruction());
        $questions = $this->parseQuestionsOrFail($json);

        $normalized = $this->normalizeAndValidateQuestions(
            $questions,
            requireHints: true,
            requireSolutions: true
        );

        return $this->saveQuestionsToDatabase($normalized);
    }

    /**
     * Generate VARIATIONS of an existing question (no images).
     * Guarantees explanation, exactly 3 hints, and ≥1 solution are present and saved.
     */
    public function generateVariations(Question $question, int $count, array $options = []): array
    {
        if (!$this->openaiApiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
        if ($count < 1 || $count > 20) {
            throw new \Exception('Number of variations must be between 1 and 20');
        }

        $question->loadMissing(['skill', 'difficulty', 'type']);

        $ageBand    = $options['age_band']    ?? $this->inferAgeBandFromSkill($question->skill);
        $readingLvl = $options['reading_lvl'] ?? 'CEFR A2–B1';
        $focus      = $options['focus_areas'] ?? '';

        $prompt = $this->buildVariationPrompt($question, $count, $ageBand, $readingLvl, $focus);

        $json       = $this->callOpenAIJson($prompt, $this->systemJsonInstruction());
        $variations = $this->parseVariationsOrFail($json);

        $normalized = $this->normalizeAndValidateQuestions(
            $variations,
            requireHints: true,
            requireSolutions: true,
            isVariation: true,
            base: $question
        );

        return $this->saveVariationsToDatabase($question, $normalized);
    }

    /* ----------------- OpenAI (strict JSON) ----------------- */

    protected function systemJsonInstruction(): string
    {
        return implode("\n", [
            "You are a master K–12 math item writer and educator.",
            "Write age-appropriate, culturally neutral items with plain, kind language.",
            "Avoid trick wording. Use notation only when it helps.",
            "Return STRICT JSON only, no markdown fences.",
        ]);
    }

    protected function callOpenAIJson(string $userPrompt, string $systemPrompt): array
    {
        $verify = app()->environment('production');

        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type'  => 'application/json',
        ])
        ->withOptions(['verify' => $verify])
        ->timeout(180)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userPrompt],
            ],
            'temperature' => 0.4,
            'max_tokens'  => 4000,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$resp->successful()) {
            throw new \Exception('OpenAI API request failed: ' . $resp->status() . ' - ' . $resp->body());
        }

        $content = data_get($resp->json(), 'choices.0.message.content');
        if (!is_string($content) || $content === '') {
            throw new \Exception('Invalid response structure from OpenAI');
        }

        $json = json_decode($content, true);
        if (!is_array($json)) {
            throw new \Exception('OpenAI did not return valid JSON');
        }
        return $json;
    }

    /* ----------------- Prompt builders (educator quality) ----------------- */

    protected function buildAuthorPrompt(Skill $skill, int $count, string $ageBand, string $readingLvl, ?string $focusAreas, array $samples): string
    {
        if (!$skill->relationLoaded('tracks')) {
            $skill->load('tracks.level');
        }
        $trackInfo = $skill->tracks->map(function ($t) {
            $level = optional($t->level)->description;
            return $t->track . ($level ? " ({$level})" : '');
        })->implode(', ');

        $difficultyMapping = $this->getDifficultyMapping();
        $typeMapping = "1 = Multiple Choice (exactly 4 options)\n2 = Fill-in-the-Blank (numbers only)";
        $samplesText = $this->formatSampleQuestions($samples);
        $halfMcq = max(1, (int) floor($count / 2));
        $halfFib = max(0, $count - $halfMcq);
        $focus = $focusAreas ? "Focus Areas: {$focusAreas}\n" : "";

        $prompt = "Create EXACTLY {$count} math questions for the skill below.\n";
        $prompt .= "Audience: {$ageBand}; reading level: {$readingLvl}. Write like a top-tier textbook or assessment book author, concise and supportive. Avoid trick questions. Use plain, kind language. Ensure every item teaches.\n\n";

        $prompt .= "**SKILL CONTEXT**\n";
        $prompt .= "- Skill: {$skill->skill}\n";
        $prompt .= "- Description: {$skill->description}\n";
        $prompt .= "- Learning Tracks: {$trackInfo}\n";
        $prompt .= "{$focus}\n";

        $prompt .= "**DIFFICULTY MAPPING (use these ids):**\n{$difficultyMapping}\n\n";
        $prompt .= "**TYPE MAPPING (use these ids):**\n{$typeMapping}\n\n";

        $prompt .= "**TYPE SPLIT**\n";
        $prompt .= "- ~{$halfMcq} items with type_id=1 (MCQ)\n";
        $prompt .= "- ~{$halfFib} items with type_id=2 (FIB numbers)\n\n";

        $prompt .= "**PEDAGOGY RULES**\n";
        $prompt .= "- Each item must include: 'explanation' (student-friendly), exactly 3 'hints' (levels 1, 2, 3), and 'solutions' (at least one method, steps, and final_answer).\n";
        $prompt .= "- Use KaTeX $$...$$ sparingly where it clarifies notation.\n";
        $prompt .= "- Difficulty ids in 1..3 only.\n";
        $prompt .= "- MCQ: exactly 4 options in 'answers', 'correct_answer' is the index 0..3.\n";
        $prompt .= "**PEDAGOGY RULES - HINTS & SOLUTIONS**\n";
        $prompt .= "Each question must include:\n\n";

        $prompt .= "**HINTS (exactly 3 levels):**\n";
        $prompt .= "- Level 1: Strategic nudge that activates prior knowledge or suggests an approach. Does NOT give steps or numbers. Use phrases like 'Think about...', 'Imagine you are...', 'What strategy...'\n";
        $prompt .= "- Level 2: Guided steps that break down the first major step or show partial work. Provides concrete starting point. Student still completes remaining work.\n";
        $prompt .= "- Level 3: Nearly complete solution showing most steps with clear reasoning. Student only needs final step or connection.\n";
        $prompt .= "- Use age-appropriate language for {$ageBand}\n";
        $prompt .= "- Encourage students to 'be in' the problem (e.g., 'Imagine YOU are...' for word problems)\n";
        $prompt .= "- Build confidence through scaffolding, not cheerleading\n\n";

        $prompt .= "**SOLUTIONS (at least 1 method):**\n";
        $prompt .= "- Must include: 'method' (clear strategy name), 'steps' (array of clear actions), 'final_answer' (must match correct_answer field)\n";
        $prompt .= "- Write steps as a teacher explaining to the student\n";
        $prompt .= "- Show mathematical thinking, not just calculations\n";
        $prompt .= "- For K-2: use concrete language, reference visual models\n";
        $prompt .= "- For 3-6: introduce mathematical vocabulary appropriately\n";
        $prompt .= "- Each step should be one clear, actionable instruction\n";
        $prompt .= "- Final answer must exactly match the correct_answer value\n\n";

        $prompt .= "**OTHER REQUIREMENTS:**\n";
        $prompt .= "- Include clear 'explanation' (why this answer is correct, student-friendly)\n";
        $prompt .= "- Use KaTeX $$...$$ sparingly, only where it clarifies notation\n";
        $prompt .= "- Difficulty ids must be 1, 2, or 3 only\n";
        $prompt .= "- MCQ: exactly 4 options in 'answers', 'correct_answer' is index 0..3\n";
        $prompt .= "- FIB: 'answers' array of numeric strings, 'correct_answer' must be numeric\n";
        $prompt .= "- Keep contexts culturally neutral and age-appropriate for {$ageBand}\n\n";
        $prompt .= "- FIB numbers: 'answers' must be an array of numeric strings for the blanks, 'correct_answer' must be numeric. Provide solutions with steps.\n";
        $prompt .= "- Keep contexts culturally neutral and age appropriate.\n\n";

        if ($samplesText !== "No sample questions available - will generate based on skill description.") {
            $prompt .= "**SAMPLE STYLE ONLY (do not copy content):**\n{$samplesText}\n";
        }

        $prompt .= "**STRICT OUTPUT JSON**\n";
        $prompt .= <<<JSON
        {
          "questions": [
            {
              "skill_id": {$skill->id},
              "source": "AI by skill",
              "qa_status": "ai_generated",
              "status_id": 3,
              "type_id": 1,
              "difficulty_id": 2,
              "question": "Question text",
              "answers": ["A","B","C","D"],
              "correct_answer": 0,
              "explanation": "Student-friendly explanation.",
              "hints": [
                {"hint_level":1,"hint_text":"gentle nudge"},
                {"hint_level":2,"hint_text":"more specific clue"},
                {"hint_level":3,"hint_text":"nearly the steps"}
            ],
              "solutions": [
                {"method":"standard","steps":["step 1","step 2"],"final_answer":"A"}
            ]
          }
      ]
      }
      JSON;
      $prompt .= "\nReturn exactly {$count} items in \"questions\". JSON only.";

      return $prompt;
  }

protected function buildVariationPrompt(
    Question $original,
    int $n,
    string $ageBand,
    string $readingLvl,
    string $focusAreas
): string {
    $skill       = $original->skill;
    $skillId     = (int) $original->skill_id;
    $diffLabel   = optional($original->difficulty)->difficulty ?? 'Medium';
    $typeLabel   = optional($original->type)->type ?? 'MCQ';
    $diffId      = (int) ($original->difficulty_id ?? 2);
    $typeId      = (int) ($original->type_id ?? 1);

    // Safely serialize any text we embed into the prompt
    $skillName   = json_encode((string) ($skill->skill ?? ''), JSON_UNESCAPED_UNICODE);
    $skillDesc   = json_encode((string) ($skill->description ?? ''), JSON_UNESCAPED_UNICODE);
    $origQ       = json_encode((string) $original->question, JSON_UNESCAPED_UNICODE);

    $focusLine   = $focusAreas ? "Focus areas: {$focusAreas}." : "";

    // Original MCQ bits (used only when type_id === 1)
    $answersArr  = [$original->answer0, $original->answer1, $original->answer2, $original->answer3];
    $answersJson = json_encode($answersArr, JSON_UNESCAPED_UNICODE);
    $correctIdx  = is_numeric($original->correct_answer) ? (int) $original->correct_answer : null;

    // ------- Header (no heredocs) -------
    $headerLines = [
        "Create EXACTLY {$n} VARIATIONS of the question below.",
        "",
        "Hard rules",
        "- Keep SAME skill_id={$skillId}.",
        "- Keep SAME type_id={$typeId} ({$typeLabel}).",
        "- Keep SAME difficulty_id={$diffId} ({$diffLabel}).",
        "- Stay strictly within the skill description. Only change surface details such as numbers, names, or light contexts.",
        "- Audience: {$ageBand}. Reading level: {$readingLvl}.",
    ];
    if ($focusLine) $headerLines[] = $focusLine;

    $headerLines[] = "";
    $headerLines[] = "Context";
    $headerLines[] = "- Skill Name: {$skillName}";
    $headerLines[] = "- Skill Description (authoritative scope): {$skillDesc}";
    $headerLines[] = "- Original Question: {$origQ}";
    if ($typeId === 1) {
        $headerLines[] = "- Original Answers: {$answersJson}";
        $headerLines[] = "- Original Correct Index: " . ($correctIdx ?? 'unknown');
    }
    $header = implode("\n", $headerLines);

    // ------- Requirements -------
    $reqLines = [
        "",
        "REQUIRED FIELDS for every variation",
        "- 'skill_id', 'type_id', 'difficulty_id'  - must match the original values above",
        "- 'question'                               - reworded prompt that assesses the same skill",
        "- 'explanation'                            - age appropriate explanation of why the answer is correct",
        "- 'hints'                                  - exactly 3 hints with hint_level 1, 2, 3 and hint_text",
        "- 'solutions'                              - at least 1 method with steps array and final_answer",
        "",
        "Type specific shape",
        "- For MCQ (type_id = 1)",
        "  - 'answers' is an array of exactly 4 strings",
        "  - 'correct_answer' is an integer index 0..3",
        "- For FIB numeric (type_id != 1)",
        "  - 'question' uses [?] where numeric answers go",
        "  - 'answers' is an array of numeric strings, one per blank",
        "  - 'correct_answer' is a numeric string for a single blank, or an array of numeric strings for multiple blanks",
        "",
        "Quality and scope constraints",
        "- Use {$ageBand} appropriate language at reading level {$readingLvl}",
        "- If unsure whether a concept is in scope of the skill description, treat it as out of scope and avoid it",
        "",
        "STRICT OUTPUT JSON",
        "- Return a single top level object with key \"variations\"",
        "- The \"variations\" value is an array with exactly {$n} items",
        "- Output JSON only. No prose before or after",
    ];
    $requirements = implode("\n", $reqLines);

    // ------- Example schema built via json_encode (no heredocs, valid JSON) -------
    if ($typeId === 1) {
        // MCQ skeleton
        $example = [
            'variations' => [[
                'skill_id'       => $skillId,
                'type_id'        => 1,
                'difficulty_id'  => $diffId,
                'question'       => 'Reworded MCQ that stays within the skill description',
                'answers'        => ['opt1','opt2','opt3','opt4'],
                'correct_answer' => 0,
                'explanation'    => 'Student friendly explanation.',
                'hints'          => [
                    ['hint_level' => 1, 'hint_text' => 'strategic nudge'],
                    ['hint_level' => 2, 'hint_text' => 'guided first step'],
                    ['hint_level' => 3, 'hint_text' => 'nearly complete reasoning'],
                ],
                'solutions'      => [
                    ['method' => 'standard', 'steps' => ['s1','s2'], 'final_answer' => 'opt1'],
                ],
            ]]
        ];
    } else {
        // FIB numeric skeleton (single blank example)
        $example = [
            'variations' => [[
                'skill_id'       => $skillId,
                'type_id'        => $typeId,
                'difficulty_id'  => $diffId,
                'question'       => 'Prompt with [?] where numeric answers go, still within the described skill',
                'answers'        => ['12'],
                'correct_answer' => '12',
                'explanation'    => 'Student friendly explanation.',
                'hints'          => [
                    ['hint_level' => 1, 'hint_text' => 'strategic nudge'],
                    ['hint_level' => 2, 'hint_text' => 'guided first step'],
                    ['hint_level' => 3, 'hint_text' => 'nearly complete reasoning'],
                ],
                'solutions'      => [
                    ['method' => 'standard', 'steps' => ['s1','s2'], 'final_answer' => '12'],
                ],
            ]]
        ];
    }
    $schema = json_encode($example, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // ------- Final prompt -------
    return $header . "\n\n" . $requirements . "\n\nExample JSON schema (structure only):\n" . $schema;
}



/* ----------------- Parsing + Strong Validation ----------------- */

protected function parseQuestionsOrFail(array $json): array
{
    if (!isset($json['questions']) || !is_array($json['questions']) || empty($json['questions'])) {
        throw new \Exception('AI response missing non-empty "questions" array');
    }
    return $json['questions'];
}

protected function parseVariationsOrFail(array $json): array
{
    if (!isset($json['variations']) || !is_array($json['variations']) || empty($json['variations'])) {
        throw new \Exception('AI response missing non-empty "variations" array');
    }
    return $json['variations'];
}

    /**
     * Normalize keys and require explanation, exactly 3 hints, and ≥1 solution.
     * Also enforces MCQ/FIB answer shapes and basic pedagogy constraints.
     */
    protected function normalizeAndValidateQuestions(
        array $items,
        bool $requireHints,
        bool $requireSolutions,
        bool $isVariation = false,
        ?Question $base = null
    ): array {
        $out = [];
        foreach ($items as $i => $q) {
            $typeId = (int) ($q['type_id'] ?? ($base ? $base->type_id : 1));
            $difficultyId = (int) ($q['difficulty_id'] ?? ($base ? $base->difficulty_id : 2));
            $questionText = trim((string) ($q['question'] ?? ''));
            $explanation  = trim((string) ($q['explanation'] ?? ''));

            if ($questionText === '') {
                throw new \Exception("Item {$i} missing question text");
            }
            if ($explanation === '') {
                throw new \Exception("Item {$i} missing explanation");
            }
            if (!in_array($difficultyId, [1,2,3], true)) {
                throw new \Exception("Item {$i} has invalid difficulty_id {$difficultyId}");
            }
            if (!in_array($typeId, [1,2], true)) {
                throw new \Exception("Item {$i} has invalid type_id {$typeId}");
            }

            // Hints: exactly 3
            $hints = $q['hints'] ?? [];
            if ($requireHints) {
                if (!is_array($hints) || count($hints) !== 3) {
                    throw new \Exception("Item {$i} must include exactly 3 hints");
                }
                $hints = array_map(function ($h, $k) use ($i) {
                    $level = $h['level'] ?? $h['hint_level'] ?? null;
                    $text  = $h['text']  ?? $h['hint_text']  ?? null;
                    if (!in_array($level, [1,2,3], true)) {
                        throw new \Exception("Item {$i} hint {$k} has invalid hint_level");
                    }
                    $t = trim((string) $text);
                    if ($t === '') {
                        throw new \Exception("Item {$i} hint {$k} has empty text");
                    }
                    return ['hint_level' => $level, 'hint_text' => $t];
                }, $hints, array_keys($hints));
            }

            // Solutions: at least 1 with method, steps≥1, final_answer present
            $solutions = $q['solutions'] ?? [];
            if ($requireSolutions) {
                if (!is_array($solutions) || count($solutions) < 1) {
                    throw new \Exception("Item {$i} must include at least one solution");
                }
                $solutions = array_values(array_map(function ($s) use ($i) {
                    if (!is_array($s)) {
                        throw new \Exception("Item {$i} solution must be object");
                    }
                    $method = trim((string) ($s['method'] ?? ''));
                    $steps  = $s['steps'] ?? [];
                    $final  = $s['final_answer'] ?? null;
                    if ($method === '' || !is_array($steps) || count($steps) < 1 || $final === null) {
                        throw new \Exception("Item {$i} solution must include method, steps, and final_answer");
                    }
                    return [
                        'method'        => $method,
                        'steps'         => array_values(array_map(fn($st) => trim((string)$st), $steps)),
                        'final_answer'  => is_array($final) ? json_encode($final) : (string)$final,
                    ];
                }, $solutions));
            }

            // Answers + correct_answer by type
            $answers = $q['answers'] ?? [];
            $correct = $q['correct_answer'] ?? null;

            if ($typeId === 1) { // MCQ
                if (!is_array($answers) || count($answers) !== 4) {
                    throw new \Exception("Item {$i} (MCQ) must have exactly 4 answers");
                }
                $answers = array_values(array_map(fn($a) => (string) $a, $answers));
                if (!in_array($correct, [0,1,2,3], true)) {
                    throw new \Exception("Item {$i} (MCQ) correct_answer must be index 0..3");
                }
            } else { // FIB numeric
                if (!is_array($answers) || count($answers) < 1) {
                    throw new \Exception("Item {$i} (FIB) answers must be non-empty array of numeric strings");
                }
                foreach ($answers as $k => $ans) {
                    if (!is_numeric($ans)) {
                        throw new \Exception("Item {$i} (FIB) answers[{$k}] must be numeric string");
                    }
                }
                if (!is_numeric($correct)) {
                    throw new \Exception("Item {$i} (FIB) correct_answer must be numeric");
                }
            }

            $out[] = [
                'skill_id'       => $q['skill_id'] ?? ($base ? $base->skill_id : null),
                'source'         => $q['source'] ?? ($isVariation ? "AI by question" : "AI by skill"),
                'qa_status'      => $q['qa_status'] ?? 'ai_generated',
                'status_id'      => $q['status_id'] ?? 3,
                'type_id'        => $typeId,
                'difficulty_id'  => $difficultyId,
                'question'       => $questionText,
                'answers'        => $answers,
                'correct_answer' => $typeId === 1 ? (int) $correct : $correct,
                'explanation'    => $explanation,
                'hints'          => $hints,
                'solutions'      => $solutions,
            ];
        }
        return $out;
    }

    /* ----------------- Persistence (guaranteed save) ----------------- */

    protected function formatSolutionText(array $solution): string
    {
        $text = '';
        if (!empty($solution['method'])) {
            $text .= "Method: {$solution['method']}\n";
        }
        if (!empty($solution['steps']) && is_array($solution['steps'])) {
            $text .= "Steps:\n";
            foreach ($solution['steps'] as $i => $step) {
                $text .= ($i + 1) . ". " . $step . "\n";
            }
        }
        if (!empty($solution['final_answer'])) {
            $text .= "\nFinal Answer: " . $solution['final_answer'];
        }
        return $text;
    }

    protected function saveQuestionsToDatabase(array $questionsData): array
    {
        $saved     = [];
        $hintsBulk = [];
        $solBulk   = [];

        DB::beginTransaction();
        try {
            foreach ($questionsData as $i => $data) {
                if (empty($data['skill_id'])) {
                    throw new \Exception("Item {$i} missing skill_id");
                }

                $answers = array_pad($data['answers'] ?? [], 4, '');
                $typeId  = (int) $data['type_id'];

                $q = Question::create([
                    'skill_id'       => $data['skill_id'],
                    'source'         => $data['source'] ?? 'AI',
                    'question'       => $data['question'],
                    'answer0'        => $answers[0] ?? '',
                    'answer1'        => $answers[1] ?? '',
                    'answer2'        => $answers[2] ?? '',
                    'answer3'        => $answers[3] ?? '',
                    'correct_answer' => $typeId === 1 ? (int) $data['correct_answer'] : null,
                    'explanation'    => $data['explanation'] ?? null,
                    'type_id'        => $typeId,
                    'difficulty_id'  => $data['difficulty_id'] ?? 2,
                    'status_id'      => $data['status_id'] ?? 3,
                    'qa_status'      => $data['qa_status'] ?? 'ai_generated',
                    'user_id'        => auth()->id() ?? 1,
                ]);

                foreach ($data['hints'] as $h) {
                    $hintsBulk[] = [
                        'question_id' => $q->id,
                        'hint_level'  => $h['hint_level'],
                        'hint_text'   => $h['hint_text'],
                        'user_id'     => auth()->id() ?? null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                foreach ($data['solutions'] as $sol) {
                    $txt = $this->formatSolutionText($sol);
                    $solBulk[] = [
                        'question_id' => $q->id,
                        'user_id'     => auth()->id() ?? 1,
                        'solution'    => $txt,
                        'status_id'   => 3,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                $saved[] = $q;
            }

            if (!empty($hintsBulk)) {
                \App\Models\Hint::insert($hintsBulk);
            }
            if (!empty($solBulk)) {
                \App\Models\Solution::insert($solBulk);
            }

            DB::commit();
            return $saved;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception('Failed to save questions: ' . $e->getMessage());
        }
    }

    protected function saveVariationsToDatabase(Question $original, array $vars): array
    {
        $saved     = [];
        $hintsBulk = [];
        $solBulk   = [];

        DB::beginTransaction();
        try {
            foreach ($vars as $idx => $data) {
                $answers = array_pad($data['answers'] ?? [], 4, '');
                $typeId  = (int) ($data['type_id'] ?? $original->type_id);
                $diffId  = (int) ($data['difficulty_id'] ?? $original->difficulty_id);

                $q = Question::create([
                    'skill_id'       => $original->skill_id,
                    'difficulty_id'  => $diffId,
                    'type_id'        => $typeId,
                    'user_id'        => auth()->id() ?? $original->user_id ?? 1,
                    'question'       => $data['question'],
                    'answer0'        => $answers[0] ?? '',
                    'answer1'        => $answers[1] ?? '',
                    'answer2'        => $answers[2] ?? '',
                    'answer3'        => $answers[3] ?? '',
                    'correct_answer' => $typeId === 1 ? (int) $data['correct_answer'] : null,
                    'explanation'    => $data['explanation'] ?? null,
                    'status_id'      => 3,
                    'qa_status'      => 'ai_generated',
                    'source'         => "AI generated from question {$original->id}",
                ]);

                foreach ($data['hints'] as $h) {
                    $hintsBulk[] = [
                        'question_id' => $q->id,
                        'hint_level'  => $h['hint_level'],
                        'hint_text'   => $h['hint_text'],
                        'user_id'     => auth()->id() ?? null,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                foreach ($data['solutions'] as $sol) {
                    $txt = $this->formatSolutionText($sol);
                    $solBulk[] = [
                        'question_id' => $q->id,
                        'user_id'     => auth()->id() ?? 1,
                        'solution'    => $txt,
                        'status_id'   => 3,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ];
                }

                $saved[] = $q;
            }

            if (!empty($hintsBulk)) {
                \App\Models\Hint::insert($hintsBulk);
            }
            if (!empty($solBulk)) {
                \App\Models\Solution::insert($solBulk);
            }

            DB::commit();
            return $saved;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception('Failed to save variations: ' . $e->getMessage());
        }
    }

    /* ----------------- Helpers (all used) ----------------- */

    protected function assertPrereqs(Skill $skill, int $count, int $min, int $max): void
    {
        if (!$this->openaiApiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
        if ($count < $min || $count > $max) {
            throw new \Exception("Number of questions must be between {$min} and {$max}");
        }
        if (empty($skill->description) || strlen($skill->description) < 10) {
            throw new \Exception('Skill description is too short. Please provide a detailed description.');
        }
    }

    protected function inferAgeBandFromSkill(Skill $skill): string
    {
        $text = strtolower(($skill->description ?? '') . ' ' . $skill->skill);
        if (str_contains($text, 'grade 1') || str_contains($text, 'primary 1') || str_contains($text, 'year 1')) return 'Ages 6–7';
        if (str_contains($text, 'grade 2') || str_contains($text, 'primary 2') || str_contains($text, 'year 2')) return 'Ages 7–8';
        if (str_contains($text, 'grade 3') || str_contains($text, 'primary 3') || str_contains($text, 'year 3')) return 'Ages 8–9';
        if (str_contains($text, 'grade 4') || str_contains($text, 'primary 4') || str_contains($text, 'year 4')) return 'Ages 9–10';
        if (str_contains($text, 'grade 5') || str_contains($text, 'primary 5') || str_contains($text, 'year 5')) return 'Ages 10–11';
        if (str_contains($text, 'grade 6') || str_contains($text, 'primary 6') || str_contains($text, 'year 6')) return 'Ages 11–12';
        return 'Ages 8–10';
    }

    protected function getSampleQuestions(Skill $skill): array
    {
        $skill->loadMissing('tracks');

        $q = Question::where('skill_id', $skill->id)
        ->whereIn('qa_status', ['approved', 'unreviewed'])
        ->with(['skill', 'difficulty', 'type'])
        ->limit(3)
        ->get();

        if ($q->isEmpty() && $skill->tracks->isNotEmpty()) {
            $trackIds = $skill->tracks->pluck('id');
            $q = Question::whereHas('skill', function ($query) use ($trackIds) {
                $query->whereHas('tracks', fn($qq) => $qq->whereIn('id', $trackIds));
            })
            ->where('skill_id', '!=', $skill->id)
            ->whereIn('qa_status', ['approved', 'unreviewed'])
            ->with(['skill', 'difficulty', 'type'])
            ->limit(3)
            ->get();
        }

        if ($q->isEmpty()) {
            $q = Question::whereIn('qa_status', ['approved', 'unreviewed'])
            ->with(['skill', 'difficulty', 'type'])
            ->latest()
            ->limit(3)
            ->get();
        }

        return $q->map(fn($qq) => [
            'question'       => $qq->question,
            'answer0'        => $qq->answer0,
            'answer1'        => $qq->answer1,
            'answer2'        => $qq->answer2,
            'answer3'        => $qq->answer3,
            'correct_answer' => $qq->correct_answer,
            'explanation'    => $qq->explanation,
            'difficulty_id'  => $qq->difficulty_id,
            'type_id'        => $qq->type_id,
            'skill_name'     => optional($qq->skill)->skill ?? 'Unknown',
        ])->toArray();
    }

    protected function formatSampleQuestions(array $samples): string
    {
        if (empty($samples)) {
            return "No sample questions available - will generate based on skill description.";
        }
        $out = '';
        foreach ($samples as $i => $s) {
            $out .= "**Sample " . ($i + 1) . ":**\n";
            $out .= "Question: {$s['question']}\n";
            $out .= "Answers: [{$s['answer0']}, {$s['answer1']}, {$s['answer2']}, {$s['answer3']}]\n";
            $out .= "Correct Answer: {$s['correct_answer']}\n";
            $out .= "Explanation: " . ($s['explanation'] ?: 'None') . "\n";
            $out .= "Difficulty ID: {$s['difficulty_id']}\n";
            $out .= "Type ID: {$s['type_id']}\n";
            $out .= "From Skill: {$s['skill_name']}\n\n";
        }
        return $out;
    }

    protected function getDifficultyMapping(): string
    {
        $rows = Difficulty::orderBy('id')->get(['id', 'difficulty']);
        if ($rows->isEmpty()) {
            return "1 = Easy\n2 = Medium\n3 = Hard";
        }
        return $rows->map(fn($d) => "{$d->id} = {$d->difficulty}")->implode("\n");
    }
}
