<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class P3SentinelSeeder extends Seeder
{
    public function run(): void
    {
        $now          = now();
        $systemUserId = 1;   // change if needed
        $activeStatus = 3;   // active
        $mcqTypeId    = 1;   // MCQ
        $p3LevelId    = 4;   // Primary 3

        // P3 track names (exact, as in DB). We’ll attach one sentinel each.
        // If a track has no skill mapping yet, we’ll skip with a warning.
        $tracks = [
            // Geometry & Spatial Reasoning
            'Angles Year 3',
            'Perpendicular and Parallel Lines',

            // Measurement
            'Area and Perimeter',
            'Length, Mass and Volume Year 3',
            'Time Year 3',

            // Number & Algebra
            'Addition and Subtraction of equivalent fractions',
            'Addition and Subtraction to 10000',
            'Equivalent Fractions',
            'Multiplication and Dvision to 10000',
            'Numbers up to 10000',

            // Statistics & Probability
            'Bar Graphs',

            // Word Problems & Applications
            'Fractions in Context',
            'Money Year 3',
            'Multi-Step Word Problems',
        ];

        // Question bank by track (stem, answers, correct index, hints, solution)
        // 4 include “None of the above”; exactly 2 of those make it the correct answer.
        $bank = [
            'Angles Year 3' => [
                'q' => 'An angle turns from 0° to 90°. What kind of angle is it?',
                'a' => ['Acute', 'Right', 'Obtuse', 'None of the above'],
                'c' => 1,
                'h' => [
                    'Angles smaller than a right angle are called acute.',
                    'A right angle measures exactly 90°.',
                    'If it is exactly 90°, it is a right angle.',
                ],
                's' => 'A right angle is exactly 90°. The turn is 90°, so it is a right angle.',
            ],
            'Perpendicular and Parallel Lines' => [
                'q' => 'Which pair of lines must never meet and are always the same distance apart?',
                'a' => ['Perpendicular lines', 'Parallel lines', 'Intersecting lines', 'None of the above'],
                'c' => 1,
                'h' => [
                    'Perpendicular lines meet at right angles.',
                    'Parallel lines never meet and stay the same distance apart.',
                    'So the pair that never meets is parallel lines.',
                ],
                's' => 'Parallel lines never meet and remain the same distance apart.',
            ],
            'Area and Perimeter' => [
                'q' => 'A rectangle is 8 cm long and 3 cm wide. What is its perimeter?',
                'a' => ['11 cm', '22 cm', '24 cm', 'None of the above'],
                'c' => 1,
                'h' => [
                    'Perimeter is the total distance around a shape.',
                    'For a rectangle, P = 2 × (length + width).',
                    'Compute: 2 × (8 + 3) = 22 cm.',
                ],
                's' => 'Perimeter = 2 × (8 + 3) = 2 × 11 = 22 cm.',
            ],
            'Length, Mass and Volume Year 3' => [
                'q' => 'Each bottle holds 750 mL. How much is 8 bottles altogether?',
                'a' => ['5.5 L', '5.8 L', '6.5 L', 'None of the above'],
                'c' => 3, // None correct
                'h' => [
                    'Multiply 750 mL by 8.',
                    '750 × 8 = 6000 mL.',
                    '6000 mL = 6 L. Not listed → "None of the above".',
                ],
                's' => '750 mL × 8 = 6000 mL = 6 L. Not listed, so "None of the above".',
            ],
            'Time Year 3' => [
                'q' => 'A movie starts at 2:35 PM and ends at 4:05 PM. How long is it?',
                'a' => ['1 h 20 min', '1 h 30 min', '1 h 40 min', 'None of the above'],
                'c' => 1,
                'h' => [
                    'Find the difference from 2:35 to 4:05.',
                    '2:35 → 3:35 is 1 hour; 3:35 → 4:05 is 30 minutes.',
                    'Total duration is 1 hour 30 minutes.',
                ],
                's' => '2:35 → 3:35 (1 h), then to 4:05 (30 min). Total = 1 h 30 min.',
            ],
            'Addition and Subtraction of equivalent fractions' => [
                'q' => 'What is 3/8 + 1/8?',
                'a' => ['3/16', '4/16', '4/8', 'None of the above'],
                'c' => 2,
                'h' => [
                    'Same denominators: add numerators.',
                    '3/8 + 1/8 = (3+1)/8.',
                    'That equals 4/8 (which is 1/2).',
                ],
                's' => '3/8 + 1/8 = 4/8 (equals 1/2). The listed correct option is 4/8.',
            ],
            'Addition and Subtraction to 10000' => [
                'q' => 'What is 7006 − 2987?',
                'a' => ['4019', '4029', '4039', 'None of the above'],
                'c' => 0,
                'h' => [
                    'Subtract carefully; regroup where needed.',
                    'Work column by column.',
                    'The result is 4019.',
                ],
                's' => 'Column subtraction yields 4019.',
            ],
            'Equivalent Fractions' => [
                'q' => 'Which fraction is equivalent to 3/4?',
                'a' => ['6/8', '8/12', '9/16', 'None of the above'],
                'c' => 0,
                'h' => [
                    'Multiply numerator and denominator by the same number.',
                    '3/4 × 2/2 = 6/8.',
                    'So 6/8 is equivalent to 3/4.',
                ],
                's' => '3/4 = 6/8 (×2/2).',
            ],
            'Multiplication and Dvision to 10000' => [
                'q' => 'What is 240 × 30?',
                'a' => ['6200', '7000', '8200', 'None of the above'],
                'c' => 3, // None correct
                'h' => [
                    'Compute 24 × 3, then add the zeros.',
                    '24 × 3 = 72; add two zeros → 7200.',
                    '7200 not listed → "None of the above".',
                ],
                's' => '240 × 30 = 7200. Not in options, so "None of the above".',
            ],
            'Numbers up to 10000' => [
                'q' => 'What is the value of the 7 in 7,406?',
                'a' => ['7 ones', '7 tens', '7 hundreds', '7 thousands'],
                'c' => 3,
                'h' => [
                    'Place value increases from right: ones, tens, hundreds, thousands.',
                    'The leftmost 7 is in the thousands place.',
                    'So it represents 7 thousands.',
                ],
                's' => 'In 7,406, the 7 is in the thousands place.',
            ],
            'Bar Graphs' => [
                'q' => 'A bar graph shows toy cars sold: Red=12, Blue=9, Green=6, Yellow=9. Which color sold the most?',
                'a' => ['Red', 'Blue', 'Green', 'None of the above'],
                'c' => 0,
                'h' => [
                    'Look for the tallest bar.',
                    'Compare: 12, 9, 6, 9.',
                    '12 is the largest → Red.',
                ],
                's' => 'Red has 12, the highest.',
            ],
            'Fractions in Context' => [
                'q' => 'A pizza has 8 equal slices. Mei eats 3 and Tim eats 2. What fraction of the pizza is left?',
                'a' => ['3/8', '5/8', '6/8', 'None of the above'],
                'c' => 0,
                'h' => [
                    'Total eaten = 3 + 2.',
                    'Total slices = 8; left = 8 − (3+2).',
                    'Left = 3/8.',
                ],
                's' => '5/8 eaten, so 3/8 left.',
            ],
            'Money Year 3' => [
                'q' => 'A book costs $18. You have $50. How much change do you get?',
                'a' => ['$22', '$30', '$32', 'None of the above'],
                'c' => 2,
                'h' => [
                    'Change = money you have − cost.',
                    'Compute 50 − 18.',
                    '50 − 18 = 32 dollars.',
                ],
                's' => 'Change = 50 − 18 = $32.',
            ],
            'Multi-Step Word Problems' => [
                'q' => 'A shop sells 4 pens in a pack. Zara buys 3 packs and gives 2 pens to a friend. How many pens does she have left?',
                'a' => ['10', '12', '14', 'None of the above'],
                'c' => 0,
                'h' => [
                    'First find total pens in 3 packs.',
                    '3 × 4 = 12 pens.',
                    'Then subtract 2: 12 − 2 = 10.',
                ],
                's' => '3 packs × 4 = 12, then 12 − 2 = 10.',
            ],
        ];

        DB::beginTransaction();
        try {
            foreach ($tracks as $trackName) {
                // Find the P3 track row
                $track = DB::table('tracks')
                    ->select('id')
                    ->where('track', $trackName)
                    ->where('status_id', $activeStatus)
                    ->where('level_id', $p3LevelId)
                    ->first();

                if (!$track) {
                    $this->command->warn("Track not found or inactive at P3: {$trackName}");
                    continue;
                }

                // Resolve most-advanced skill via pivot (max skill id)
                $skill = DB::table('skill_track as st')
                    ->join('skills as s', 's.id', '=', 'st.skill_id')
                    ->where('st.track_id', $track->id)
                    ->select(DB::raw('MAX(s.id) AS id'))
                    ->first();

                if (!$skill || !$skill->id) {
                    $this->command->warn("No skills mapped to track (skipped): {$trackName}");
                    continue;
                }

                // Pull question template for this track
                if (!isset($bank[$trackName])) {
                    $this->command->warn("No question template for track (skipped): {$trackName}");
                    continue;
                }
                $b = $bank[$trackName];

                // Insert question
                $questionId = DB::table('questions')->insertGetId([
                    'skill_id'        => $skill->id,
                    'difficulty_id'   => null,   // optional to backfill later
                    'is_diagnostic'   => 1,
                    'user_id'         => $systemUserId,
                    'question'        => $b['q'],
                    'explanation'     => null,
                    'answer0'         => $b['a'][0] ?? null,
                    'answer1'         => $b['a'][1] ?? null,
                    'answer2'         => $b['a'][2] ?? null,
                    'answer3'         => $b['a'][3] ?? null,
                    'correct_answer'  => $b['c'],
                    'status_id'       => $activeStatus,
                    'type_id'         => $mcqTypeId,
                    'qa_status'       => 'unreviewed',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                // Insert 3 hints
                foreach ($b['h'] as $i => $hint) {
                    DB::table('hints')->insert([
                        'question_id' => $questionId,
                        'hint_level'  => $i + 1,
                        'hint_text'   => $hint,
                        'user_id'     => $systemUserId,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }

                // Insert solution
                DB::table('solutions')->insert([
                    'question_id' => $questionId,
                    'status_id'   => 1,
                    'user_id'     => $systemUserId,
                    'solution'    => $b['s'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                $this->command->info("Inserted P3 sentinel for track '{$trackName}' (QID {$questionId}, Skill {$skill->id})");
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error($e->getMessage());
            throw $e;
        }
    }
}
