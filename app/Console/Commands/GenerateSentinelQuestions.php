<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\McqNotaPolicy;

class GenerateSentinelQuestions extends Command
{
    protected $signature = 'questions:generate-sentinels 
    {--dry-run : Show what would be created/updated without writing}
    {--limit=0 : Max number of skills to process (0 = no limit)}
    {--tracks=* : Only process these track IDs}
    {--skills=* : Only process these skill IDs}
    {--scope=skill : Diagnostic scope: skill or track}';

    protected $description = 'Ensure each skill has at least one MCQ with hints+solution (difficulty=3, user_id=1) and mark one diagnostic per track.';

    
    public function handle(): int
    {
        $dry          = (bool) $this->option('dry-run');
        $limit        = (int)  $this->option('limit');
        $filterTracks = collect($this->option('tracks'))->filter()->map(fn($v) => (int)$v)->all();
        $filterSkills = collect($this->option('skills'))->filter()->map(fn($v) => (int)$v)->all();

        // Canonical Field → Track → Skill graph, all active (status_id = 3).
        $sql = "
            SELECT
                f.id AS field_id,
                t.id AS track_id,
                s.id AS skill_id,
                t.level_id,
                f.field  AS field_name,
                t.track  AS track_name,
                s.skill  AS skill_name
            FROM fields f
            JOIN tracks t      ON t.field_id = f.id
            JOIN skill_track st ON st.track_id = t.id
            JOIN skills s      ON s.id = st.skill_id
            LEFT JOIN levels l ON l.id = t.level_id
            WHERE f.status_id = 3
              AND t.status_id = 3
              AND s.status_id = 3
              AND (l.status_id = 3 OR l.id IS NULL)
        ";

        if (!empty($filterTracks)) {
            $in = implode(',', array_map('intval', $filterTracks));
            $sql .= " AND t.id IN ($in) ";
        }
        if (!empty($filterSkills)) {
            $in = implode(',', array_map('intval', $filterSkills));
            $sql .= " AND s.id IN ($in) ";
        }
        $sql .= " ORDER BY f.id, t.level_id, t.id, s.id";

        $rows = DB::select($sql);
        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $this->info('Loaded ' . count($rows) . ' skill links.');

        $createdQ = 0; $touchedHints = 0; $touchedSolution = 0;

        foreach ($rows as $r) {
            $skillId = (int) $r->skill_id;

            // Pick best existing MCQ for this skill, else create one.
            $q = DB::table('questions')
                ->where('skill_id', $skillId)
                ->where('type_id', 1) // MCQ
                ->orderByDesc('qa_status')        // approved first if you use this
                ->orderByDesc('published_at')     // then published, if column exists
                ->orderByDesc('difficulty_id')
                ->orderBy('id')
                ->first();

            if (!$q) {
                // Generate a fresh MCQ for this skill
                [$stem, $opts, $correctIndex, $hint1, $hint2, $hint3, $solution] =
                    $this->generateMcqForSkill($r->field_name ?? '', $r->track_name ?? '', $r->skill_name ?? '');

                if ($dry) {
                    $this->line("DRY ▸ would create MCQ for skill #{$skillId}: “".Str::limit($this->plain($stem), 80)."”");
                } else {
                    DB::transaction(function () use ($skillId, $stem, $opts, $correctIndex, $hint1, $hint2, $hint3, $solution, &$createdQ, &$touchedHints, &$touchedSolution) {
                        $questionId = DB::table('questions')->insertGetId([
                            'skill_id'             => $skillId,
                            'original_question_id' => null,
                            'difficulty_id'        => 3,
                            'is_diagnostic'        => 0,          // we’ll mark one later
                            'user_id'              => 1,
                            'question'             => $stem,
                            'explanation'          => null,
                            'question_image'       => null,
                            'answer0'              => $opts[0] ?? null,
                            'answer1'              => $opts[1] ?? null,
                            'answer2'              => $opts[2] ?? null,
                            'answer3'              => $opts[3] ?? null,
                            'correct_answer'       => $correctIndex,
                            'status_id'            => 1,          // draft/unreviewed
                            'source'               => 'auto:sentinel',
                            'type_id'              => 1,          // MCQ
                            'calculator'           => null,
                            'qa_status'            => 'unreviewed',
                            'created_at'           => now(),
                            'updated_at'           => now(),
                        ]);
                        $createdQ++;

                        // Hints
                        DB::table('hints')->insert([
                            ['question_id' => $questionId, 'hint_level' => 1, 'hint_text' => $hint1, 'created_at' => now(), 'updated_at' => now()],
                            ['question_id' => $questionId, 'hint_level' => 2, 'hint_text' => $hint2, 'created_at' => now(), 'updated_at' => now()],
                            ['question_id' => $questionId, 'hint_level' => 3, 'hint_text' => $hint3, 'created_at' => now(), 'updated_at' => now()],
                        ]);
                        $touchedHints += 3;

                        // Solution (ensure column name matches your schema)
                        DB::table('solutions')->insert([
                            'question_id' => $questionId,
                            'solution'    => $solution,
                            'status_id'   => 3,
                            'user_id'     => 1,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                        $touchedSolution++;
                    });
                }
            } else {
                // Attach hints+solution to existing MCQ if missing
                if (!$dry) {
                    DB::transaction(function () use ($q, $r, &$touchedHints, &$touchedSolution) {
                        $qid = (int) $q->id;

                        if (!DB::table('hints')->where('question_id', $qid)->exists()) {
                            [$hint1, $hint2, $hint3] = $this->defaultHintsForSkill($r->skill_name ?? '');
                            DB::table('hints')->insert([
                                ['question_id' => $qid, 'hint_level' => 1, 'hint_text' => $hint1, 'created_at' => now(), 'updated_at' => now()],
                                ['question_id' => $qid, 'hint_level' => 2, 'hint_text' => $hint2, 'created_at' => now(), 'updated_at' => now()],
                                ['question_id' => $qid, 'hint_level' => 3, 'hint_text' => $hint3, 'created_at' => now(), 'updated_at' => now()],
                            ]);
                            $touchedHints += 3;
                        }

                        if (!DB::table('solutions')->where('question_id', $qid)->exists()) {
                            DB::table('solutions')->insert([
                                'question_id' => $qid,
                                'solution'    => $this->defaultSolutionForSkill($r->skill_name ?? ''),
                                'status_id'   => 3,
                                'user_id'     => 1,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ]);
                            $touchedSolution++;
                        }
                    });
                }
            }
        }

        $this->info("MCQs created: {$createdQ}, hints added: {$touchedHints}, solutions added: {$touchedSolution}");

        $this->markDiagnostics($dry, (string) $this->option('scope') ?: 'skill');

        $this->info($dry ? 'DRY RUN complete.' : 'Generation + diagnostic selection complete.');
        return Command::SUCCESS;
    }

    /**
     * Pick exactly one diagnostic per skill or per track, ignoring qa_status.
     * Heuristics: prefer auto:sentinel, then full pedagogy (3 hints + 1 solution),
     * then higher difficulty, then oldest id.
     */
    protected function markDiagnostics(bool $dry, string $scope): void
    {
        $scope = strtolower($scope);
        if ($scope !== 'skill' && $scope !== 'track') $scope = 'skill';

        // Common WHERE for valid MCQs (has an answer key and at least one option present)
        $whereMcq = "
            q.type_id = 1
            AND q.correct_answer IS NOT NULL
            AND (
                q.answer0 IS NOT NULL OR q.answer1 IS NOT NULL OR
                q.answer2 IS NOT NULL OR q.answer3 IS NOT NULL
            )
        ";

        // ORDER BY heuristic without qa_status
        $orderBy = "
            (q.source = 'auto:sentinel') DESC,
            -- full pedagogy first
            (SELECT COUNT(*) >= 3 FROM hints h WHERE h.question_id = q.id) DESC,
            (SELECT COUNT(*) >= 1 FROM solutions s WHERE s.question_id = q.id) DESC,
            q.difficulty_id DESC,
            q.id ASC
        ";

        if ($scope === 'skill') {
            $sql = "
                WITH ranked AS (
                    SELECT
                        q.id AS question_id,
                        q.skill_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY q.skill_id
                            ORDER BY $orderBy
                        ) AS rn
                    FROM questions q
                    WHERE $whereMcq
                )
                UPDATE questions q
                JOIN ranked r ON r.question_id = q.id
                SET q.is_diagnostic = (r.rn = 1)
            ";
        } else {
            // per TRACK: join via skill_track
            $sql = "
                WITH ranked AS (
                    SELECT
                        q.id AS question_id,
                        t.id AS track_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY t.id
                            ORDER BY $orderBy
                        ) AS rn
                    FROM tracks t
                    JOIN skill_track st ON st.track_id = t.id
                    JOIN questions q   ON q.skill_id  = st.skill_id
                    WHERE $whereMcq
                )
                UPDATE questions q
                JOIN ranked r ON r.question_id = q.id
                SET q.is_diagnostic = (r.rn = 1)
            ";
        }

        if ($dry) {
            $this->line("DRY ▸ would set diagnostics by {$scope} using auto:sentinel + pedagogy + difficulty heuristics.");
        } else {
            \DB::statement($sql);
        }
    }


    // ——————— Content generation ———————

    /**
     * Returns: [stem_html, [opt0,opt1,opt2,opt3], correctIndex, hint1, hint2, hint3, solution]
     */
    protected function generateMcqForSkill(string $field, string $track, string $skill): array
    {
        $topic = strtolower("{$field} {$track} {$skill}");

        // Route by keyword. Keep it simple and safe for P1–P6 range.
        if (str_contains($topic, 'add') && !str_contains($topic, 'fraction')) {
            return $this->mcqAddWithin100($skill);
        }
        if (str_contains($topic, 'subtract') && !str_contains($topic, 'fraction')) {
            return $this->mcqSubWithin100($skill);
        }
        if (str_contains($topic, 'multiply') && !str_contains($topic, 'fraction')) {
            return $this->mcqMulSingleDigit($skill);
        }
        if (str_contains($topic, 'divide') && !str_contains($topic, 'fraction')) {
            return $this->mcqDivSingleDigit($skill);
        }
        if (str_contains($topic, 'fraction')) {
            return $this->mcqFractionsLikeDenom($skill);
        }
        if (str_contains($topic, 'decimal')) {
            return $this->mcqDecimalsPlaceValue($skill);
        }
        if (str_contains($topic, 'percentage')) {
            return $this->mcqPercentageOfWhole($skill);
        }
        if (str_contains($topic, 'ratio')) {
            return $this->mcqRatioSimplify($skill);
        }
        if (str_contains($topic, 'angle')) {
            return $this->mcqAnglesStraightLine($skill);
        }
        if (str_contains($topic, 'area') && str_contains($topic, 'rectangle')) {
            return $this->mcqAreaRectangle($skill);
        }
        if (str_contains($topic, 'perimeter')) {
            return $this->mcqPerimeterRectangle($skill);
        }
        if (str_contains($topic, 'time')) {
            return $this->mcqTimeDuration($skill);
        }
        if (str_contains($topic, 'money')) {
            return $this->mcqMoneySimple($skill);
        }
        if (str_contains($topic, 'average')) {
            return $this->mcqAverage($skill);
        }
        if (str_contains($topic, 'speed')) {
            return $this->mcqSpeed($skill);
        }
        if (str_contains($topic, 'bar graph') || str_contains($topic, 'graph') || str_contains($topic, 'picture graph') || str_contains($topic, 'pie')) {
            return $this->mcqDataReadOff($skill);
        }

        // Fallback: simple addition within 100 (harmless baseline)
        return $this->mcqAddWithin100($skill);
    }

    // Utility: strip tags to show short previews
    protected function plain(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }

    // Default hints/solution if we didn’t route to a specific template
    protected function defaultHintsForSkill(string $skill): array
    {
        return [
            "Focus on the key idea in {$skill}. What operation or concept fits the question?",
            "Write the steps you would take. Check place value or units after each step.",
            "Try a smaller example of the same structure, then repeat the steps for the real numbers.",
        ];
    }

    protected function defaultSolutionForSkill(string $skill): string
    {
        return "Identify the concept in {$skill}. Apply the steps in order, keeping track of units or place value. Verify your result with an inverse check or quick estimation. The option that matches is correct.";
    }

    // ——————— Template bank (concise, P1–P6 safe) ———————

    protected function mcqAddWithin100(string $skill): array
    {
        $a = rand(11, 49); $b = rand(11, 49); $ans = $a + $b;
        $d1 = $ans + rand(1,3);
        $d2 = $ans - rand(1,3);
        $d3 = $ans + 10; // common slip
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;

        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                // Make all others wrong deliberately
                $opts[0] = $ans + 1; $opts[1] = $ans - 2; $opts[2] = $ans + 3;
                $correctIndex = 3;
            }
        }

        $stem = "Compute: {$a} + {$b}";
        $h1 = "Add tens, then ones. Keep place values aligned.";
        $h2 = "Compute {$a} + {$b} step by step. If you cross 10 in ones, carry to tens.";
        $h3 = "For example, if it were 20 + 35, tens add to 50 and ones to 5, total 55. Now apply that idea here.";
        $sol = "Add ones first, then tens. {$a} + {$b} = {$ans}. If you added incorrectly, check carrying. Therefore the correct option is {$ans}.";

        return [$stem, array_map('strval', $opts), $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqSubWithin100(string $skill): array
    {
        $a = rand(40, 99); $b = rand(11, 39); $ans = $a - $b;
        $d1 = $ans + rand(1,3);
        $d2 = $ans - rand(1,3);
        $d3 = $a + $b; // classic wrong operation
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;

        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = $ans + 2; $opts[1] = $ans - 2; $opts[2] = $ans + 4;
                $correctIndex = 3;
            }
        }

        $stem = "Compute: {$a} − {$b}";
        $h1 = "Subtract ones first. If needed, regroup from the tens.";
        $h2 = "Work column by column. If the ones digit of {$a} is smaller than {$b}, borrow from the tens.";
        $h3 = "Check by adding the result to {$b}. If you get {$a}, your subtraction is correct.";
        $answerLabel = ($correctIndex === 3) ? 'None of the above' : (string)$ans;
        $sol = "Perform column subtraction. The difference is {$ans}. Verify by {$ans} + {$b} = {$a}. So the correct option is {$answerLabel}.";

        return [$stem, array_map('strval', $opts), $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqMulSingleDigit(string $skill): array
    {
        $a = rand(3, 9); $b = rand(4, 9); $ans = $a * $b;
        $d1 = $ans + rand(1,3);
        $d2 = $ans - rand(1,3);
        $d3 = $a + $b; // confusion add vs multiply
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;

        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = $ans - 1; $opts[1] = $ans + 2; $opts[2] = $ans + 3;
                $correctIndex = 3;
            }
        }

        $stem = "Compute: {$a} × {$b}";
        $h1 = "Think in groups: {$a} groups of {$b}.";
        $h2 = "Use a known fact near it, then adjust. For instance, 5×{$b} then add/subtract.";
        $h3 = "Check by repeated addition: add {$b}, {$a} times. The total is the product.";
        $sumAdd = $a + $b;
        $sol = "{$a} × {$b} = {$ans}. If you added instead of multiplying you might get {$a}+{$b}={$sumAdd}, which is incorrect for multiplication.";
        return [$stem, array_map('strval', $opts), $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqDivSingleDigit(string $skill): array
    {
        $b = rand(3,9);
        $ans = rand(3,9);
        $a = $b * $ans;
        $d1 = $ans + 1; $d2 = $ans - 1; $d3 = $a - $b; // wrong op
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;

        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = $ans + 2; $opts[1] = $ans - 2; $opts[2] = $ans + 3;
                $correctIndex = 3;
            }
        }

        $stem = "Compute: {$a} ÷ {$b}";
        $h1 = "Division asks, how many groups of {$b} are in {$a}?";
        $h2 = "Use the inverse of multiplication: find x such that {$b} × x = {$a}.";
        $h3 = "Try building multiples of {$b}: {$b}, ".($b*2).", ".($b*3).", … until you reach {$a}. Count the steps.";
        $sol = "Since {$b} × {$ans} = {$a}, the quotient is {$ans}.";
        return [$stem, array_map('strval', $opts), $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqFractionsLikeDenom(string $skill): array
    {
        $d = [2,3,4,5,6,8,10,12][array_rand([2,3,4,5,6,8,10,12])];
        $n1 = rand(1, $d-1); $n2 = rand(1, $d-1); $ansN = $n1 + $n2; // addition like denom
        if ($ansN >= $d) { $ansN -= $d; } // keep proper
        $ans = "{$ansN}/{$d}";
        $d1 = ($ansN+1)."/{$d}";
        $d2 = ($ansN-1>0?$ansN-1:1)."/{$d}";
        $d3 = ($n1 + $n2)."/".($d*2); // wrong denom idea
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = ($ansN+2)."/{$d}";
                $opts[1] = ($ansN>1?$ansN-2:1)."/{$d}";
                $opts[2] = ($n1 * $n2)."/{$d}";
                $correctIndex = 3;
            }
        }

        $stem = "Compute: {$n1}/{$d} + {$n2}/{$d}";
        $h1 = "With the same denominator, add the numerators and keep the denominator.";
        $h2 = "Add {$n1}+{$n2} for the numerator, write over {$d}. Simplify if needed.";
        $h3 = "Example: 1/4 + 2/4 = 3/4. Apply the same rule here.";
        $sol = "Add numerators: {$n1}+{$n2} = ".($n1+$n2).". Keep denominator {$d}. If improper, convert, otherwise {$ans} is correct.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqDecimalsPlaceValue(string $skill): array
    {
        $whole = rand(2,9); $tenth = rand(1,9); $hund = rand(0,9);
        $n = "{$whole}.{$tenth}{$hund}";
        $q = "What is the value of the digit {$tenth} in {$n}?";
        $ans = "{$tenth}/10";
        $d1 = "{$tenth}/100";
        $d2 = "{$tenth}";
        $d3 = "{$tenth}0/100";
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = "{$tenth}/100";
                $opts[1] = "{$tenth}";
                $opts[2] = "{$tenth}0/10";
                $correctIndex = 3;
            }
        }

        $h1 = "The first digit after the decimal point is tenths.";
        $h2 = "Tenths means the digit over 10, hundreds would be over 100.";
        $h3 = "Example: 3.47, the 4 is 4/10, the 7 is 7/100.";
        $answerLabel = ($correctIndex === 3) ? 'None of the above' : "{$tenth}/10";
        $sol = "The digit in the tenths place represents {$tenth}/10. So the correct option is {$answerLabel}.";
        return [$q, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqPercentageOfWhole(string $skill): array
    {
        $whole = [50, 100, 200][array_rand([50,100,200])];
        $p = [10, 15, 20, 25][array_rand([10,15,20,25])];
        $ans = $whole * $p / 100;
        $d1 = $whole * $p / 10; // off by factor
        $d2 = $whole - $ans;    // complement confusion
        $d3 = $p;               // raw percent misread
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ (string)$ans, (string)$d1, (string)$d2, (string)$d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = (string)($ans + 1);
                $opts[1] = (string)($ans - 1);
                $opts[2] = (string)($ans + 2);
                $correctIndex = 3;
            }
        }

        $stem = "Find {$p}% of {$whole}.";
        $h1 = "{$p}% means {$p}/100 of the whole.";
        $h2 = "Compute {$whole} × {$p}/100.";
        $h3 = "Example: 25% of 200 = 200 × 25/100 = 50. Use the same method.";
        $sol = "Multiply the whole by the fraction form of the percent: {$whole} × {$p}/100 = {$ans}.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqRatioSimplify(string $skill): array
    {
        $a = [6,8,9,12,15][array_rand([6,8,9,12,15])];
        $b = [4,6,12,18,21][array_rand([4,6,12,18,21])];
        $g = gcd($a,$b);
        if (!$g) { $g = 1; }
        $ansA = intdiv($a, $g); $ansB = intdiv($b, $g);
        $ans = "{$ansA} : {$ansB}";
        $d1 = ($ansA+1)." : ".($ansB);
        $d2 = ($ansA)." : ".($ansB+1);
        $d3 = "{$a} : {$b}";
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [$ans, $d1, $d2, $d3];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = ($ansA+2)." : ".($ansB);
                $opts[1] = ($ansA)." : ".($ansB+2);
                $opts[2] = ($ansA+1)." : ".($ansB+1);
                $correctIndex = 3;
            }
        }

        $stem = "Simplify the ratio {$a} : {$b}.";
        $h1 = "Divide both terms by the same greatest common factor.";
        $h2 = "Find the largest number that divides both {$a} and {$b}.";
        $h3 = "Example: 6:9 → divide both by 3 → 2:3.";
        $sol = "GCD({$a}, {$b}) = {$g}. Divide both sides by {$g} to get {$ans}.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqAnglesStraightLine(string $skill): array
    {
        $x = [35, 40, 45, 50, 60][array_rand([35,40,45,50,60])];
        $ans = 180 - $x;
        $d1 = 180 + $x;
        $d2 = 2*$x;
        $d3 = 90 - $x;
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ (string)$ans, (string)$d1, (string)$d2, (string)$d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = (string)($ans + 1);
                $opts[1] = (string)($ans - 1);
                $opts[2] = (string)($ans + 2);
                $correctIndex = 3;
            }
        }

        $stem = "Two angles on a straight line are supplementary. If one angle is {$x}°, what is the other?";
        $h1 = "Angles on a straight line add to 180°.";
        $h2 = "Subtract from 180° to find the other angle.";
        $h3 = "Example: if one is 40°, the other is 180° − 40° = 140°.";
        $answerLabel = ($correctIndex === 3) ? 'None of the above' : (string)$ans;
        $sol = "Compute 180° − {$x}° = {$ans}°. So the correct option is {$answerLabel}.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqAreaRectangle(string $skill): array
    {
        $L = rand(5,12); $W = rand(3,10); $ans = $L*$W;
        $d1 = $L + $W; $d2 = 2*($L+$W); $d3 = ($L*$W)+$L;
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ (string)$ans, (string)$d1, (string)$d2, (string)$d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = (string)($ans + 1);
                $opts[1] = (string)($ans - 1);
                $opts[2] = (string)($ans + 2);
                $correctIndex = 3;
            }
        }

        $stem = "A rectangle has length {$L} cm and width {$W} cm. What is its area (cm²)?";
        $h1 = "Area of a rectangle is length × width.";
        $h2 = "Multiply {$L} by {$W}. Do not add or take the perimeter.";
        $h3 = "Check with an array model: {$L} rows of {$W} gives {$ans} small squares.";
        $answerLabel = ($correctIndex === 3) ? 'None of the above' : (string)$ans;
        $sol = "Compute {$L}×{$W} = {$ans} cm². Perimeter formulas would be {$L}+{$W} or 2(L+W), which are distractors.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqPerimeterRectangle(string $skill): array
    {
        $L = rand(5,12); $W = rand(3,10); $ans = 2*($L+$W);
        $d1 = $L*$W; $d2 = $L+$W; $d3 = 2*($L*$W);
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ (string)$ans, (string)$d1, (string)$d2, (string)$d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = (string)($ans + 1);
                $opts[1] = (string)($ans - 2);
                $opts[2] = (string)($ans + 3);
                $correctIndex = 3;
            }
        }

        $stem = "A rectangle has length {$L} cm and width {$W} cm. What is its perimeter (cm)?";
        $h1 = "Perimeter is the total distance around, add all sides.";
        $h2 = "Compute 2 × (length + width) = 2×({$L}+{$W}).";
        $h3 = "Area would be {$L}×{$W}, a common mix-up. Use the perimeter rule instead.";
        $answerLabel = ($correctIndex === 3) ? 'None of the above' : (string)$ans;
        $sol = "Perimeter = 2(L+W) = 2({$L}+{$W}) = {$ans} cm.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqTimeDuration(string $skill): array
    {
        $startH = rand(7,11); $startM = [0,15,30,45][array_rand([0,15,30,45])];
        $dur = [20, 35, 45, 55][array_rand([20,35,45,55])];
        $endM = $startM + $dur; $endH = $startH + intdiv($endM,60); $endM = $endM % 60;
        $ans = sprintf('%02d:%02d', $endH, $endM);
        $d1 = sprintf('%02d:%02d', $endH, ($endM+10)%60);
        $d2 = sprintf('%02d:%02d', $endH, ($endM+15)%60);
        $d3 = sprintf('%02d:%02d', $startH, $startM);
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ $ans, $d1, $d2, $d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = sprintf('%02d:%02d', $endH, ($endM+1)%60);
                $opts[1] = sprintf('%02d:%02d', $endH, ($endM+2)%60);
                $opts[2] = sprintf('%02d:%02d', $endH, ($endM+3)%60);
                $correctIndex = 3;
            }
        }

        $stem = "A task starts at ".sprintf('%02d:%02d', $startH, $startM)." and lasts {$dur} minutes. What time does it end?";
        $h1 = "Add minutes. If you pass 60, add 1 hour and keep the remainder as minutes.";
        $h2 = "Compute end minutes, then adjust hours: endM = startM + {$dur}.";
        $h3 = "Example: 10:45 + 35 min = 11:20. Apply the same carry-over idea.";
        $sol = "Add {$dur} minutes to the start time. Adjust if minutes ≥ 60. End time is {$ans}.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqMoneySimple(string $skill): array
    {
        $a = [1.2, 1.5, 2.0, 2.5][array_rand([1.2,1.5,2.0,2.5])];
        $b = [0.5, 0.8, 1.0, 1.3][array_rand([0.5,0.8,1.0,1.3])];
        $ans = round($a + $b, 2);
        $d1 = round($a + $b + 0.1, 2);
        $d2 = round($a + $b - 0.1, 2);
        $d3 = round($a - $b, 2);
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ number_format($ans,2), number_format($d1,2), number_format($d2,2), number_format($d3,2) ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = number_format($ans + 0.02,2);
                $opts[1] = number_format($ans - 0.02,2);
                $opts[2] = number_format($ans + 0.03,2);
                $correctIndex = 3;
            }
        }

        $stem = "A toy costs $".number_format($a,2)." and a sticker costs $".number_format($b,2).". What is the total cost?";
        $h1 = "Add dollars and cents carefully. Line up the decimal points.";
        $h2 = "Add the cents, then the dollars. Regroup if cents reach 100.";
        $h3 = "Quick check: estimate each to the nearest dollar to see if your answer is reasonable.";
        $sol = "Total = $".number_format($a,2)." + $".number_format($b,2)." = $".number_format($ans,2).".";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqAverage(string $skill): array
    {
        $x = rand(8,12); $y = rand(10,14); $z = rand(6,10);
        $sum = $x + $y + $z; $ans = round($sum/3, 2);
        $d1 = $sum; $d2 = max($x,$y,$z); $d3 = min($x,$y,$z);
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();
        $opts = [ (string)$ans, (string)$d1, (string)$d2, (string)$d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = (string)($ans + 1);
                $opts[1] = (string)($ans - 1);
                $opts[2] = (string)($ans + 2);
                $correctIndex = 3;
            }
        }
        $stem = "Find the average of {$x}, {$y}, and {$z}.";
        $h1 = "Average = total amount divided by number of items.";
        $h2 = "Add the numbers, then divide by 3.";
        $h3 = "Check if the answer lies between the smallest and largest value.";
        $sol = "Sum {$x}+{$y}+{$z} = {$sum}. Divide by 3 to get {$ans}.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqSpeed(string $skill): array
    {
        $d = [60, 90, 120][array_rand([60,90,120])];
        $t = [2, 3, 4][array_rand([2,3,4])];
        $ans = $d / $t;
        $d1 = $d * $t;
        $d2 = $d + $t;
        $d3 = $t / $d;
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();
        $opts = [ (string)$ans, (string)$d1, (string)$d2, (string)$d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = (string)($ans + 1);
                $opts[1] = (string)($ans - 1);
                $opts[2] = (string)($ans + 2);
                $correctIndex = 3;
            }
        }
        $stem = "A cyclist travels {$d} km in {$t} hours. What is the average speed (km/h)?";
        $h1 = "Speed = distance ÷ time.";
        $h2 = "Divide {$d} by {$t}.";
        $h3 = "Check units: km divided by h gives km/h.";
        $sol = "Compute {$d} ÷ {$t} = {$ans} km/h.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }

    protected function mcqDataReadOff(string $skill): array
    {
        // Simple “read the tallest/total” style without visuals
        $a = rand(4,9); $b = rand(2,8); $c = rand(1,7);
        $total = $a + $b + $c;
        $correct = max($a,$b,$c) === $a ? 'A' : (max($a,$b,$c) === $b ? 'B' : 'C');
        $ans = $correct;
        $d1 = ($correct==='A'?'B':'A');
        $d2 = ($correct==='C'?'B':'C');
        $d3 = 'All equal';
        [$includeNota, $notaCorrect] = McqNotaPolicy::decide();

        $opts = [ $ans, $d1, $d2, $d3 ];
        $correctIndex = 0;
        if ($includeNota) {
            $opts[3] = 'None of the above';
            if ($notaCorrect) {
                $opts[0] = 'A and C';
                $opts[1] = 'B and C';
                $opts[2] = 'A and B';
                $correctIndex = 3;
            }
        }

        $stem = "A graph shows counts: A={$a}, B={$b}, C={$c}. Which has the greatest value?";
        $h1 = "Compare each category’s value directly.";
        $h2 = "Identify the largest among {$a}, {$b}, {$c}.";
        $h3 = "If two are equal you would state both. Here, pick the one that’s largest.";
        $sol = "Largest count is {$correct}.";
        return [$stem, $opts, $correctIndex, $h1, $h2, $h3, $sol];
    }
}

// Simple gcd helper for ratios
if (!function_exists('gcd')) {
    function gcd($a, $b) {
        $a = abs((int)$a); $b = abs((int)$b);
        if ($a === 0) return $b;
        if ($b === 0) return $a;
        while ($b !== 0) {
            $t = $b; $b = $a % $b; $a = $t;
        }
        return $a;
    }
}
