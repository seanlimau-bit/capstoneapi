<?php

namespace App\Services;

use App\Models\DiagnosticSession;
use App\Models\DiagnosticResponse;
use App\Models\DiagnosticFieldProgress;
use App\Models\Question;
use App\Models\Field;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DiagnosticService
{
    protected AdaptiveLevelService $levelService;

    public function __construct(AdaptiveLevelService $levelService)
    {
        $this->levelService = $levelService;
    }

    /**
     * Submit answer and process adaptive algorithm
     * 
     * Returns: ['completed' => bool, 'result' => array|null, 'questions' => array|null, 'completed_field' => string|null]
     */
    public function submitAnswer(
        DiagnosticSession $session,
        Question $question,
        int $selectedAnswer,
        ?int $responseTime = 0
    ): array {
        $isCorrect = $this->checkAnswer($question, $selectedAnswer);
        $fieldId = $question->field_id;
        
        $currentTrack = $question->skill->tracks->first();
        if (!$currentTrack || !$currentTrack->level) {
            throw new \Exception('Question has no valid track/level');
        }
        
        $currentMaxile = $currentTrack->level->start_maxile_level;

        // Save response
        DiagnosticResponse::create([
            'diagnostic_session_id' => $session->id,
            'question_id' => $question->id,
            'skill_id' => $question->skill_id,
            'track_id' => $currentTrack->id,
            'field_id' => $fieldId,
            'selected_answer' => (string)$selectedAnswer,
            'is_correct' => $isCorrect,
            'response_time_seconds' => $responseTime,
        ]);

        $session->increment('item_count');

        // Get or create field progress
        $fieldProgress = DiagnosticFieldProgress::firstOrCreate(
            [
                'session_id' => $session->id,
                'field_id' => $fieldId,
            ],
            [
                'current_level' => $this->levelService->getStartingMaxile($fieldId),
                'wrong_count_at_level' => 0,
                'correct_count_at_level' => 0,
                'completed' => false,
            ]
        );

        // Apply adaptive algorithm
        $fieldJustCompleted = $this->applyAdaptiveAlgorithm(
            $fieldProgress,
            $isCorrect,
            $fieldId,
            $currentMaxile
        );

        // Check if all fields complete
        if ($this->areAllFieldsComplete($session)) {
            return $this->completeDiagnostic($session);
        }

        // Get next batch of questions
        $nextQuestions = $this->getNextQuestionBatch($session);

        if (empty($nextQuestions)) {
            return $this->completeDiagnostic($session);
        }

        $response = [
            'completed' => false,
            'questions' => $nextQuestions,
            'result' => null,
        ];

        if ($fieldJustCompleted) {
            $response['completed_field'] = $fieldJustCompleted;
        }

        return $response;
    }

    /**
     * Apply "Hit Ceiling Twice OR Floor Once" algorithm
     * 
     * Returns: completed field string or null
     */
    protected function applyAdaptiveAlgorithm(
        DiagnosticFieldProgress $fieldProgress,
        bool $isCorrect,
        int $fieldId,
        int $currentMaxile
    ): ?string {
        if ($isCorrect) {
            // ✅ CORRECT ANSWER
            $fieldProgress->correct_count_at_level++;
            
            $maxLevel = $this->levelService->getMaxLevelForField($fieldId);
            
            if ($fieldProgress->current_level >= $maxLevel) {
                // AT MAX LEVEL - CEMENT HERE!
                $fieldProgress->final_level = $maxLevel;
                $fieldProgress->completed = true;
                
                Log::info("Field {$fieldId} CEMENTED at MAX level {$maxLevel}");
                
                $fieldProgress->save();
                
                return $this->getCompletedFieldInfo($fieldProgress);
            } else {
                // Move up
                $nextMaxile = $this->levelService->getNextLevelUp($fieldProgress->current_level, $fieldId);
                $fieldProgress->current_level = $nextMaxile;
                
                Log::info("Field {$fieldId}: Correct! Moving to {$nextMaxile}");
            }
            
        } else {
            // ❌ WRONG ANSWER
            $fieldProgress->wrong_count_at_level++;
            
            Log::info("Field {$fieldId}: Wrong! Total wrongs: {$fieldProgress->wrong_count_at_level}");
            
            if ($fieldProgress->wrong_count_at_level >= 2) {
                // HIT CEILING - CEMENT!
                $levelBelow = $this->levelService->getNextLevelDown($fieldProgress->current_level, $fieldId);
                $fieldProgress->final_level = $levelBelow;
                $fieldProgress->completed = true;
                
                Log::info("Field {$fieldId} CEMENTED at level {$levelBelow}");
                
                $fieldProgress->save();
                
                return $this->getCompletedFieldInfo($fieldProgress);
            } else {
                // Drop down
                $levelBelow = $this->levelService->getNextLevelDown($fieldProgress->current_level, $fieldId);
                $fieldProgress->current_level = $levelBelow;
                
                Log::info("Field {$fieldId}: Dropping to {$levelBelow}");
            }
        }

        $fieldProgress->save();
        
        return null;
    }

    /**
     * Check if all fields are complete
     */
    protected function areAllFieldsComplete(DiagnosticSession $session): bool
    {
        $completedFields = DiagnosticFieldProgress::where('session_id', $session->id)
            ->where('completed', true)
            ->count();
        
        $totalPublicFields = Field::where('status_id', 3)->count();
        
        return $completedFields >= $totalPublicFields;
    }

    /**
     * Get next batch of questions - one per incomplete field
     */
    public function getNextQuestionBatch(DiagnosticSession $session): array
    {
        $fieldIds = $this->levelService->getPublicFieldIds();
        
        if (empty($fieldIds)) {
            Log::warning("No public fields found!");
            return [];
        }
        
        $completedFieldIds = DiagnosticFieldProgress::where('session_id', $session->id)
            ->where('completed', true)
            ->pluck('field_id')
            ->toArray();

        $answeredQuestionIds = DiagnosticResponse::where('diagnostic_session_id', $session->id)
            ->pluck('question_id')
            ->toArray();

        $incompleteFieldIds = array_values(array_diff($fieldIds, $completedFieldIds));
        
        if (empty($incompleteFieldIds)) {
            return [];
        }

        $questions = [];

        foreach ($incompleteFieldIds as $fieldId) {
            $fieldProgress = DiagnosticFieldProgress::where('session_id', $session->id)
                ->where('field_id', $fieldId)
                ->first();

            $targetMaxile = $fieldProgress 
                ? $fieldProgress->current_level 
                : $this->levelService->getStartingMaxile($fieldId);

            $question = Question::where('is_diagnostic', true)
                ->whereHas('skill.tracks', function($query) use ($targetMaxile, $fieldId) {
                    $query->whereHas('level', function($levelQuery) use ($targetMaxile) {
                        $levelQuery->where('start_maxile_level', $targetMaxile);
                    })
                    ->where('field_id', $fieldId)
                    ->where('status_id', 3);
                })
                ->where('status_id', 3)
                ->whereNotIn('id', $answeredQuestionIds)
                ->inRandomOrder()
                ->first();

            if ($question) {
                Log::info("Question for Field {$fieldId} at level {$targetMaxile}");
                $questions[] = $this->formatQuestion($question);
            } else {
                Log::warning("No question found for Field {$fieldId} at level {$targetMaxile}");
            }
        }

        return $questions;
    }

    /**
     * Complete the diagnostic and calculate results
     */
    public function completeDiagnostic(DiagnosticSession $session): array
    {
        $fieldProgresses = DiagnosticFieldProgress::where('session_id', $session->id)->get();
        
        // Mark any incomplete fields as complete at current level
        foreach ($fieldProgresses as $progress) {
            if (!$progress->completed) {
                $progress->completed = true;
                $progress->final_level = $progress->current_level;
                $progress->save();
            }
        }

        // Calculate statistics
        $responses = DiagnosticResponse::where('diagnostic_session_id', $session->id)->get();
        $totalQuestions = $responses->count();
        $correctAnswers = $responses->where('is_correct', true)->count();
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        $averageMaxile = $fieldProgresses->avg('final_level');

        // Build field results
        $fieldResults = [];
        foreach ($fieldProgresses as $progress) {
            $field = Field::find($progress->field_id);
            
            $fieldResults[] = [
                'field_id' => $progress->field_id,
                'field_name' => $field->field ?? 'Unknown',
                'maxile' => $progress->final_level,
                'level_name' => $this->levelService->getLevelDescription($progress->final_level),
            ];
        }

        // Update session
        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
            'end_maxile' => $averageMaxile,
        ]);

        // Update user
        $session->user->update([
            'maxile_level' => $averageMaxile,
            'last_test_date' => now(),
        ]);

        Log::info('Diagnostic completed', [
            'user_id' => $session->user_id,
            'session_id' => $session->id,
            'accuracy' => $accuracy,
            'average_maxile' => $averageMaxile,
        ]);

        return [
            'completed' => true,
            'result' => [
                'overall_level' => $this->levelService->getLevelDescription($averageMaxile),
                'overall_maxile' => round($averageMaxile, 0),
                'accuracy' => round($accuracy, 1),
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'field_results' => $fieldResults,
            ],
            'questions' => null,
        ];
    }

    /**
     * Check if the selected answer is correct
     */
    protected function checkAnswer(Question $question, int $selectedAnswer): bool
    {
        return $question->correct_answer == $selectedAnswer;
    }

    /**
     * Format question for response
     */
    protected function formatQuestion(Question $question): array
    {
        $track = $question->skill->tracks->first();
        $level = $track->level ?? null;
        
        return [
            'id' => $question->id,
            'question' => $question->question ?? '',
            'image_url' => $question->question_image,
            'maxile_level' => $level ? $level->start_maxile_level : 100,
            'correct_option_id' => (int)$question->correct_answer,
            'field_id' => $question->field_id,
            'field_name' => optional($question->field)->field ?? 'Unknown',
            'options' => [
                ['id' => 0, 'text' => $question->answer0 ?? '', 'image_url' => $question->answer0_image],
                ['id' => 1, 'text' => $question->answer1 ?? '', 'image_url' => $question->answer1_image],
                ['id' => 2, 'text' => $question->answer2 ?? '', 'image_url' => $question->answer2_image],
                ['id' => 3, 'text' => $question->answer3 ?? '', 'image_url' => $question->answer3_image],
            ],
        ];
    }

    /**
     * Get completed field information as simple string
     */
    protected function getCompletedFieldInfo(DiagnosticFieldProgress $fieldProgress): string
    {
        $field = Field::find($fieldProgress->field_id);
        $fieldName = $field->field ?? 'Unknown';
        $levelName = $this->levelService->getLevelDescription($fieldProgress->final_level);
        
        return "{$fieldName} - {$levelName}";
    }
}