<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Field;
use App\Models\Question;
use App\Models\PlacementSession;
use Carbon\Carbon;
use App\Models\DiagnosticSession as PlacementSession;

class PlacementController extends Controller
{
    /**
     * Apply Sanctum authentication to all methods
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    /**
     * Store hint (birthdate/age/grade) for placement
     */
    public function storeHint(Request $request)
    {
        $data = $request->validate([
            'birthdate' => 'nullable|date|before:today|after:1900-01-01',
            'age'       => 'nullable|integer|min:3|max:99',
            'grade'     => 'nullable|string|in:Nursery,K1,K2,P1,P2,P3,P4,P5,P6',
        ]);
        
        // Get authenticated user via Sanctum
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'User not found'], 400);
        }
        
        \Log::info('Storing hint for user', ['user_id' => $user->id, 'hint' => $data]);
        
        // Store the hint
        if (!empty($data['birthdate'])) {
            $user->date_of_birth = $data['birthdate'];
            $user->birth_year = Carbon::parse($data['birthdate'])->year;
        } elseif (!empty($data['age'])) {
            $user->birth_year = now()->year - $data['age'];
        } elseif (!empty($data['grade'])) {
            // Map grade to estimated age, then to birth year
            $gradeToAge = [
                'Nursery' => 3,
                'K1' => 4,
                'K2' => 5,
                'P1' => 6,
                'P2' => 7,
                'P3' => 8,
                'P4' => 9,
                'P5' => 10,
                'P6' => 11,
            ];
            
            $estimatedAge = $gradeToAge[$data['grade']] ?? 8;
            $user->birth_year = now()->year - $estimatedAge;
            
            // Store grade if column exists
            if (\Schema::hasColumn('users', 'grade')) {
                $user->grade = $data['grade'];
            }
        }
        
        $user->save();
        
        \Log::info('✅ Hint saved successfully', [
            'user_id' => $user->id,
            'date_of_birth' => $user->date_of_birth,
            'birth_year' => $user->birth_year,
            'grade' => $user->grade ?? null,
        ]);
        
        return response()->json(['ok' => true, 'used_hint' => true], 200);
    }

    /**
     * Get placement status - whether user needs Kiasu, diagnostic, etc.
     */
    public function getStatus(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $hasAgeAnchor = !is_null($user->date_of_birth) || !is_null($user->birth_year);
        $hasDiagnostic = !is_null($user->last_test_date) && ((float) $user->maxile_level > 0.0);
        
        // Check for incomplete diagnostic (less than 30 days old)
        $incompleteDiagnostic = \App\Models\DiagnosticSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('started_at', '>', now()->subDays(30))
            ->latest('started_at')
            ->first();
        
        // Check for any learning progress
        $hasProgress = \DB::table('question_user')
            ->where('user_id', $user->id)
            ->where('question_answered', 1)
            ->exists();
        
        // Check incomplete tests
        $incompleteTest = \DB::table('test_user')
            ->where('user_id', $user->id)
            ->where('test_completed', 0)
            ->latest('created_at')
            ->first();
        
        // Get user's learning state
        $skillsCount = \DB::table('skill_user')
            ->where('user_id', $user->id)
            ->where('skill_passed', 1)
            ->count();
        
        $tracksCount = \DB::table('track_user')
            ->where('user_id', $user->id)
            ->where('track_passed', 1)
            ->count();
        
        return response()->json([
            // Onboarding flags
            'kiasu_needed' => !$hasAgeAnchor,
            'diagnostic_needed' => $hasAgeAnchor && !$hasDiagnostic && !$incompleteDiagnostic && !$hasProgress,
            
            // Incomplete work
            'has_incomplete_diagnostic' => !!$incompleteDiagnostic,
            'diagnostic_session_id' => $incompleteDiagnostic?->id ?? null,
            'diagnostic_progress' => $incompleteDiagnostic ? 
                "{$incompleteDiagnostic->item_count}/15" : null,
            'has_incomplete_test' => !!$incompleteTest,
            'incomplete_test_id' => $incompleteTest?->test_id ?? null,
            
            // Progress indicators
            'has_learning_progress' => $hasProgress,
            'skills_passed' => $skillsCount,
            'tracks_passed' => $tracksCount,
            'current_level' => (int) $user->maxile_level,
            'current_game_level' => (int) $user->game_level,
            
            // Ready state
            'ready_for_lessons' => $hasDiagnostic || $hasProgress,
            
            // User state
            'is_new_user' => !$hasProgress && !$hasDiagnostic,
        ]);
    }

    /**
     * Start diagnostic placement test
     */
    public function startDiagnostic(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'User not found'], 400);
        }
        
        // Terminate old incomplete diagnostics (older than 30 days)
        \App\Models\DiagnosticSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('started_at', '<', now()->subDays(30))
            ->update([
                'status' => 'abandoned',
                'completed_at' => now(),
            ]);
        
        // Check if there's a recent incomplete diagnostic
        $existingSession = \App\Models\DiagnosticSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('started_at', '>', now()->subDays(30))
            ->latest('started_at')
            ->first();
        
        if ($existingSession) {
            return response()->json([
                'ok' => false,
                'message' => 'You have an incomplete diagnostic. Please complete or abandon it first.',
                'existing_session_id' => $existingSession->id,
                'progress' => "{$existingSession->item_count}/15",
            ], 409);
        }
        
        // Get user's estimated level from hint (if any)
        $estimatedLevel = $this->estimateStartingLevel($user);
        
        // Get 5 fields in primary school math (only public)
        $fields = Field::public()
            ->where('field', 'LIKE', 'Primary School%')
            ->limit(5)
            ->get();
        
        $questions = [];
        
        // Get 1 diagnostic question from each field at estimated level (only public)
        foreach ($fields as $field) {
            $question = Question::public()
                ->whereHas('skill.tracks', function($q) use ($field, $estimatedLevel) {
                    $q->where('field_id', $field->id)
                      ->where('level_id', $estimatedLevel);
                })
                ->where('is_diagnostic', true)
                ->inRandomOrder()
                ->first();
            
            if ($question) {
                $questions[] = [
                    'id' => $question->id,
                    'field_id' => $field->id,
                    'field_name' => $field->field,
                    'level_id' => $estimatedLevel,
                    'question_text' => $question->question,
                    'options' => $question->options,
                    'correct_answer' => $question->answer,
                ];
            }
        }
        
        // Create placement session
        $session = PlacementSession::create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'current_round' => 1,
            'started_at' => now(),
        ]);
        
        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
            'round' => 1,
            'total_rounds' => 3, // 3 rounds of 5 questions = 15 questions max
            'questions' => $questions,
            'estimated_level' => $estimatedLevel,
        ], 200);
    }

    /**
     * Submit diagnostic answers
     */
    public function submitDiagnostic(Request $request)
    {
        $data = $request->validate([
            'session_id' => 'required|integer|exists:placement_sessions,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.selected_answer' => 'required',
            'answers.*.time_spent' => 'nullable|integer',
        ]);
        
        $session = PlacementSession::find($data['session_id']);
        
        $user = Auth::guard('sanctum')->user();
        
        if (!$session || $session->user_id !== $user->id) {
            return response()->json(['message' => 'Invalid session'], 403);
        }
        
        // Process answers and calculate results
        $correctCount = 0;
        $totalQuestions = count($data['answers']);
        
        foreach ($data['answers'] as $answer) {
            $question = Question::find($answer['question_id']);
            if ($question && $question->answer == $answer['selected_answer']) {
                $correctCount++;
            }
        }
        
        // Calculate placement level based on performance
        $accuracy = $totalQuestions > 0 ? ($correctCount / $totalQuestions) : 0;
        $placementLevel = $this->calculatePlacementLevel($session->user, $accuracy);
        
        // Update session
        $session->status = 'completed';
        $session->completed_at = now();
        $session->result = [
            'correct' => $correctCount,
            'total' => $totalQuestions,
            'accuracy' => round($accuracy * 100, 2),
            'placement_level' => $placementLevel,
        ];
        $session->save();
        
        // Update user's maxile level
        $user = $session->user;
        $user->maxile_level = $placementLevel;
        $user->last_test_date = now();
        $user->save();
        
        return response()->json([
            'ok' => true,
            'correct' => $correctCount,
            'total' => $totalQuestions,
            'accuracy' => round($accuracy * 100, 2),
            'placement_level' => $placementLevel,
        ]);
    }

    /**
     * Get diagnostic result
     */
    public function getResult(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        
        $session = PlacementSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();
        
        if (!$session) {
            return response()->json(['message' => 'No completed diagnostic found'], 404);
        }
        
        return response()->json([
            'ok' => true,
            'result' => $session->result,
            'completed_at' => $session->completed_at,
        ]);
    }
    
    /**
     * Abandon an incomplete diagnostic
     */
    public function abandonDiagnostic(Request $request, $sessionId)
    {
        $user = Auth::guard('sanctum')->user();
        
        $session = \App\Models\DiagnosticSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();
        
        if (!$session) {
            return response()->json(['message' => 'Session not found or already completed'], 404);
        }
        
        $session->update([
            'status' => 'abandoned',
            'completed_at' => now(),
        ]);
        
        return response()->json([
            'ok' => true,
            'message' => 'Diagnostic abandoned successfully',
        ]);
    }

    /**
     * Estimate starting level based on user hints
     */
    private function estimateStartingLevel($user)
    {
        // Priority 1: Use grade directly (most accurate)
        if ($user->grade) {
            $gradeMap = [
                'Nursery' => 1,
                'K1' => 1,
                'K2' => 1,
                'P1' => 1,
                'P2' => 2,
                'P3' => 3,
                'P4' => 4,
                'P5' => 5,
                'P6' => 6,
            ];
            return $gradeMap[$user->grade] ?? 3;
        }
        
        // Priority 2: Use date_of_birth (most precise age)
        if ($user->date_of_birth) {
            $age = Carbon::parse($user->date_of_birth)->age;
            return $this->ageToLevel($age);
        }
        
        // Priority 3: Use birth_year (derived from age hint)
        if ($user->birth_year) {
            $age = now()->year - $user->birth_year;
            return $this->ageToLevel($age);
        }
        
        // No hint given - start at middle level
        return 3;
    }

    /**
     * Convert age to estimated level
     */
    private function ageToLevel($age)
    {
        // Singapore Primary School: Ages 6-12 = P1-P6
        if ($age <= 6) return 1;
        if ($age >= 12) return 6;
        
        return min(6, max(1, $age - 5));
    }

    /**
     * Calculate final placement level based on diagnostic performance
     */
    private function calculatePlacementLevel($user, $accuracy)
    {
        $estimatedLevel = $this->estimateStartingLevel($user);
        
        // Adjust level based on accuracy
        if ($accuracy >= 0.9) {
            // Excellent - move up 1 level
            return min(6, $estimatedLevel + 1);
        } elseif ($accuracy >= 0.7) {
            // Good - keep estimated level
            return $estimatedLevel;
        } elseif ($accuracy >= 0.5) {
            // Average - move down 1 level
            return max(1, $estimatedLevel - 1);
        } else {
            // Below average - move down 2 levels
            return max(1, $estimatedLevel - 2);
        }
    }
}