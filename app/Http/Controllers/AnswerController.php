<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateQuizAnswersRequest;
use App\Question;
use App\QuestionUser;
use App\Test;
use App\TestUser;
use App\Level;
use App\Services\LivesService;
use App\Services\KudosService;
use DateTime;
use DB;
use Illuminate\Support\Facades\Log;

class AnswerController extends Controller
{
    /**
     * Process submitted test answers
     */
    public function answer(CreateQuizAnswersRequest $request)
    {
        $user = $request->user();
        $testId = $request->input('test');
        $submittedAnswers = $request->input('answer');
        $submittedIds = $request->input('question_id');

        // Validate test assignment
        $testUser = TestUser::where('test_id', $testId)
            ->where('user_id', $user->id)
            ->first();

        if (!$testUser) {
            return response()->json([
                'code' => 403, 
                'message' => 'Test not assigned to this user.'
            ], 403);
        }

        $test = Test::findOrFail($testId);

        // Check if test already completed
        if ($testUser->test_completed || $test->completed) {
            return $this->buildCompletedTestResponse($test, $user);
        }

        // Check lives using new config system
        if (!LivesService::hasLivesRemaining($user)) {
            return response()->json([
                'message' => 'No lives remaining. Please wait or upgrade.',
                'code' => 403,
                'lives_info' => [
                    'current_lives' => LivesService::getCurrentLives($user),
                    'max_lives' => LivesService::getMaxLives($user),
                    'lives_enabled' => LivesService::isEnabled($user)
                ]
            ], 403);
        }

        return $this->processAnswers($user, $test, $testUser, $submittedIds, $submittedAnswers);
    }

    /**
     * Process each submitted answer
     */
    private function processAnswers($user, $test, $testUser, $submittedIds, $submittedAnswers)
    {
        DB::beginTransaction();

        try {
            $totalKudos = 0;
            $correctCount = 0;
            $streakCount = KudosService::getStreakCount($user, $test->id);

            foreach ($submittedIds as $questionId) {
                $userAnswers = $submittedAnswers[$questionId] ?? null;

                if ($userAnswers === null) {
                    continue;
                }

                $result = $this->processSingleAnswer($user, $test, $questionId, $userAnswers, $streakCount);
                
                if ($result['error']) {
                    DB::rollBack();
                    return response()->json([
                        'code' => 403,
                        'message' => $result['message']
                    ], 403);
                }

                $totalKudos += $result['kudos'];
                if ($result['correct']) {
                    $correctCount++;
                    $streakCount++; // Increment streak for next question
                } else {
                    $streakCount = 0; // Reset streak on incorrect answer
                }
            }

            // Update test statistics
            $test->questions_answered += count($submittedAnswers);
            $test->kudos_earned += $totalKudos;
            $test->save();

            // Check if test should be completed
            $completionResult = $this->checkTestCompletion($user, $test, $testUser);
            
            DB::commit();

            // Add partner-specific data to response
            $response = $completionResult ?: $test->buildResponseFor($user);
            
            // Enhance response with partner config data
            if (is_array($response) || method_exists($response, 'getData')) {
                $responseData = is_array($response) ? $response : $response->getData(true);
                $responseData['partner_info'] = $this->getPartnerInfo($user);
                $responseData['lives'] = LivesService::getCurrentLives($user);
                
                return response()->json($responseData);
            }

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Answer processing failed', [
                'user_id' => $user->id,
                'test_id' => $test->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Internal error processing answers',
                'code' => 500
            ], 500);
        }
    }

    /**
     * Process a single answer submission with new kudos calculation
     */
    private function processSingleAnswer($user, $test, $questionId, $userAnswers, $streakCount)
    {
        $questionUser = QuestionUser::where('test_id', $test->id)
            ->where('question_id', $questionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$questionUser) {
            return [
                'error' => true,
                'message' => "Question {$questionId} not assigned to this user."
            ];
        }

        $question = $questionUser->question;
        $correct = $question->correctness($user, $userAnswers);
        
        // Calculate kudos using new config-based system
        $timeTaken = $this->getQuestionTime($questionUser); // You'll need to implement this
        $kudos = KudosService::calculateKudos($user, $question, $correct, $timeTaken, $streakCount);

        // Mark question as answered
        $question->answered($user, $correct, $test);

        // Handle lives for incorrect answers using new service
        if (!$correct) {
            LivesService::deductLife($user, 'incorrect_answer', $questionId);
        }

        // Process progress tracking
        $question->processProgressFor($user, $correct, $test);

        return [
            'error' => false,
            'correct' => $correct,
            'kudos' => $kudos
        ];
    }

    /**
     * Get time taken for question (placeholder - implement based on your tracking)
     */
    private function getQuestionTime($questionUser)
    {
        // This depends on how you track question start/end times
        // Return null if not implemented yet
        return null;
    }

    /**
     * Check if test should be marked as completed
     */
    private function checkTestCompletion($user, $test, $testUser)
    {
        $unansweredCount = QuestionUser::where('test_id', $test->id)
            ->where('user_id', $user->id)
            ->whereNull('answered_date')
            ->count();

        if ($unansweredCount > 0) {
            return null; // Test not completed yet
        }

        // Calculate final score
        $totalQuestions = QuestionUser::where('test_id', $test->id)
            ->where('user_id', $user->id)
            ->count();

        $correctAnswers = QuestionUser::where('test_id', $test->id)
            ->where('user_id', $user->id)
            ->where('correct', true)
            ->count();

        $score = $totalQuestions > 0
            ? round(($correctAnswers / $totalQuestions) * 100, 2)
            : 0;

        // Update test completion
        $test->update([
            'completed' => true,
            'test_score' => $score
        ]);

        // Update pivot table
        $testUser->update([
            'test_completed' => true,
            'completed_date' => now(),
            'result' => $score,
            'kudos' => $test->kudos_earned
        ]);

        return $this->buildCompletionResponse($test, $user, $score);
    }

    /**
     * Build response for completed test with partner-specific data
     */
    private function buildCompletionResponse($test, $user, $score)
    {
        $level = Level::where('start_maxile_level', '<=', $user->maxile_level)
            ->where('end_maxile_level', '>', $user->maxile_level)
            ->first();

        $encouragements = $level && $level->encouragements
            ? explode('|', $level->encouragements)
            : ['Keep going!', 'Good effort!', 'Well done!', 'Nice work!'];

        $encouragement = $encouragements[array_rand($encouragements)];

        return response()->json([
            'code' => 206,
            'encouragement' => $encouragement,
            'kudos' => $test->kudos_earned,
            'maxile' => (float) $user->maxile_level,
            'completed' => true,
            'percentage' => $score,
            'name' => $user->firstname,
            'lives' => LivesService::getCurrentLives($user),
            'partner_info' => $this->getPartnerInfo($user),
        ]);
    }

    /**
     * Build response for already completed test
     */
    private function buildCompletedTestResponse($test, $user)
    {
        return response()->json([
            'code' => 206,
            'kudos' => $test->kudos_earned,
            'maxile' => (float) $user->maxile_level,
            'completed' => true,
            'percentage' => (float) $test->test_score,
            'name' => $user->firstname,
            'message' => 'Test previously completed.',
            'lives' => LivesService::getCurrentLives($user),
            'partner_info' => $this->getPartnerInfo($user),
        ]);
    }

    /**
     * Get partner-specific information for response
     */
    private function getPartnerInfo($user)
    {
        $config = LiveService::getConfig($user);  // Changed from PartnerConfigService
        
        return [
            'lives_enabled' => $config['lives']['enabled'],
            'max_lives' => $config['lives']['max_lives'],
            'show_correct_answers' => $config['features']['show_correct_answers'] ?? true,
            'unlimited_retakes' => $config['features']['unlimited_retakes'] ?? false,
        ];
    }

    // ... rest of the legacy methods remain the same
}