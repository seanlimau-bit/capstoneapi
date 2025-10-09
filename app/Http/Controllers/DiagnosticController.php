<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DiagnosticSession;
use App\Models\DiagnosticResponse;
use App\Models\DiagnosticFieldProgress;
use App\Models\Question;
use App\Models\Field;
use App\Models\Track;
use App\Models\Level;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiagnosticController extends Controller
{
    protected $user;
    
    /**
     * Apply Sanctum authentication and store user
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        
        $this->middleware(function ($request, $next) {
            $this->user = Auth::guard('sanctum')->user();
            return $next($request);
        });
    }
    
    /**
     * Get diagnostic status for current user
     */
    public function getStatus(Request $request)
    {
        $user = $this->user;
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $hasAgeAnchor = !is_null($user->date_of_birth) 
            || !is_null($user->birth_year) 
            || !is_null($user->grade);
        
        $hasDiagnostic = !is_null($user->last_test_date) 
            && ((float) $user->maxile_level > 0.0);
        
        return response()->json([
            'kiasu_needed' => !$hasAgeAnchor,
            'diagnostic_needed' => $hasAgeAnchor && !$hasDiagnostic,
            'ready_for_lessons' => $hasDiagnostic,
        ]);
    }

    /**
     * Store user hint (birthdate, age, or grade)
     */
    public function storeHint(Request $request)
    {
        Log::info('🎯 storeHint called', [
            'user_id' => $this->user ? $this->user->id : 'null',
            'request_data' => $request->all(),
        ]);
        
        $data = $request->validate([
            'birthdate' => 'nullable|date|before:today|after:1900-01-01',
            'age'       => 'nullable|integer|min:1|max:100',
            'grade'     => 'nullable|string|in:Nursery,K1,K2,P1,P2,P3,P4,P5,P6',
        ]);
        
        Log::info('✅ Validation passed', ['validated_data' => $data]);
        
        $user = $this->user;
        
        if (!$user) {
            Log::error('❌ User not found');
            return response()->json(['ok' => false, 'message' => 'User not found'], 400);
        }
        
        Log::info('📝 User found', ['user_id' => $user->id]);
        
        $updated = false;
        
        if (!empty($data['birthdate'])) {
            $user->date_of_birth = $data['birthdate'];
            $user->birth_year = Carbon::parse($data['birthdate'])->year;
            Log::info('💾 Storing birthdate', [
                'date_of_birth' => $user->date_of_birth,
                'birth_year' => $user->birth_year,
            ]);
            $updated = true;
        } elseif (!empty($data['age'])) {
            $user->birth_year = now()->year - $data['age'];
            Log::info('💾 Storing age-derived birth_year', ['birth_year' => $user->birth_year]);
            $updated = true;
        } elseif (!empty($data['grade'])) {
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
            
            Log::info('💾 Storing grade-derived birth_year', [
                'grade_provided' => $data['grade'],
                'estimated_age' => $estimatedAge,
                'birth_year' => $user->birth_year,
            ]);
            $updated = true;
        }
        
        if ($updated) {
            $saved = $user->save();
            Log::info('✅ User save called', [
                'save_result' => $saved,
                'user_id' => $user->id,
                'date_of_birth' => $user->date_of_birth,
                'birth_year' => $user->birth_year,
            ]);
        } else {
            Log::warning('⚠️ No updates to save (empty hint)');
        }
        
        return response()->json([
            'ok' => true, 
            'message' => 'Hint saved successfully',
            'hint_saved' => $updated,
        ], 200);
    }

    /**
     * Start diagnostic placement test
     */
    public function start(Request $request)
    {
        $user = $this->user;
        
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'User not found'], 400);
        }
        
        // Terminate old incomplete diagnostics (older than 30 days)
        DiagnosticSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('started_at', '<', now()->subDays(30))
            ->update([
                'status' => 'abandoned',
                'completed_at' => now(),
            ]);
        
        // Check if there's a recent incomplete diagnostic
        $existingSession = DiagnosticSession::where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->where('started_at', '>', now()->subDays(30))
            ->latest('started_at')
            ->first();
        
        if ($existingSession) {
            // Auto-abandon if it's been more than 1 day
            if ($existingSession->started_at < now()->subDay()) {
                $existingSession->update([
                    'status' => 'abandoned',
                    'completed_at' => now(),
                ]);
            } else {
                // Return existing diagnostic with its questions
                $testId = $existingSession->test_id;
                
                // Get questions from question_user table for this test
                $questionIds = DB::table('question_user')
                    ->where('test_id', $testId)
                    ->where('user_id', $user->id)
                    ->pluck('question_id');
                
                if ($questionIds->isNotEmpty()) {
                    $questions = Question::whereIn('id', $questionIds)
                        ->get()
                        ->map(function($q) use ($testId, $user) {
                            // Check if already answered
                            $answered = DB::table('question_user')
                                ->where('question_id', $q->id)
                                ->where('test_id', $testId)
                                ->where('user_id', $user->id)
                                ->first();
                            
                            // Skip if already answered
                            if ($answered && $answered->question_answered) {
                                return null;
                            }
                            
                            // Get maxile level
                            $maxileLevel = 100;
                            $level = DB::table('skills')
                                ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
                                ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
                                ->join('levels', 'tracks.level_id', '=', 'levels.id')
                                ->where('skills.id', $q->skill_id)
                                ->select('levels.start_maxile_level', 'levels.end_maxile_level')
                                ->first();
                            
                            if ($level) {
                                $maxileLevel = ($level->start_maxile_level + $level->end_maxile_level) / 2;
                            }
                            
                            return [
                                'id' => $q->id,
                                'question' => $q->question ?? '',
                                'image_url' => $q->question_image,
                                'maxile_level' => (int)$maxileLevel,
                                'correct_option_id' => (int)$q->correct_answer,
                                'options' => [
                                    ['id' => 0, 'text' => $q->answer0 ?? '', 'image_url' => $q->answer0_image],
                                    ['id' => 1, 'text' => $q->answer1 ?? '', 'image_url' => $q->answer1_image],
                                    ['id' => 2, 'text' => $q->answer2 ?? '', 'image_url' => $q->answer2_image],
                                    ['id' => 3, 'text' => $q->answer3 ?? '', 'image_url' => $q->answer3_image],
                                ],
                            ];
                        })
                        ->filter(); // Remove null entries (answered questions)
                    
                    // If all questions answered, mark session as complete
                    if ($questions->isEmpty()) {
                        $existingSession->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);
                        
                        return response()->json([
                            'ok' => false,
                            'message' => 'Diagnostic already completed',
                        ], 400);
                    }
                    
                    return response()->json([
                        'ok' => true,
                        'session_id' => $existingSession->id,
                        'test_id' => $testId,
                        'estimated_level' => $existingSession->start_maxile / 100,
                        'total_questions' => $questions->count(),
                        'resumed' => true,
                        'questions' => $questions->values(), // Re-index array after filter
                    ]);
                }
            }
        }
        
        // Get user's estimated level from hint (if any)
        $estimatedLevel = $this->estimateStartingLevel($user); // Returns maxile level (e.g., 100, 200, 300)
        
        // Determine seed hint type and value
        $seedHintType = 'none';
        $seedHintValue = null;
        
        if ($user->grade) {
            $seedHintType = 'grade';
            $seedHintValue = $user->grade;
        } elseif ($user->date_of_birth) {
            $seedHintType = 'birthdate';
            $seedHintValue = $user->date_of_birth;
        } elseif ($user->birth_year) {
            $seedHintType = 'age';
            $seedHintValue = (string)(now()->year - $user->birth_year);
        }
        
        // Create test entry first (marked as diagnostic)
        $test = DB::table('tests')->insertGetId([
            'test' => 'Diagnostic Test',
            'description' => 'Initial placement diagnostic',
            'diagnostic' => true,
            'start_available_time' => now(),
            'end_available_time' => now()->addDays(30),
            'due_time' => now()->addDays(30),
            'number_of_tries_allowed' => 1,
            'which_result' => 'highest',
            'status_id' => 1,
            'user_id' => $user->id,
            'test_maxile' => $estimatedLevel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create test_user entry
        DB::table('test_user')->insert([
            'test_id' => $test,
            'user_id' => $user->id,
            'test_completed' => false,
            'attempts' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Create new diagnostic session with test_id
        $session = DiagnosticSession::create([
            'user_id' => $user->id,
            'test_id' => $test,
            'subject' => 'math',
            'mode' => 'diagnostic',
            'seed_hint_type' => $seedHintType,
            'seed_hint_value' => $seedHintValue,
            'start_maxile' => $estimatedLevel,
            'status' => 'in_progress',
            'started_at' => now(),
            'item_count' => 0,
        ]);
        
        // Get ONE question from EACH public FIELD at the appropriate level
        $publicFields = Field::where('status_id', 3)->pluck('id');
        
        if ($publicFields->isEmpty()) {
            Log::error('No public fields found for diagnostic');
            return response()->json([
                'ok' => false,
                'message' => 'No public fields available for diagnostic.',
            ], 500);
        }
        
        // Get one question per FIELD at the estimated level
        $questions = collect();
        $targetMaxile = $estimatedLevel;
        $maxileRange = 200;
        
        foreach ($publicFields as $fieldId) {
            $question = Question::where('questions.skill_id', '>', 0)
                ->where('questions.is_diagnostic', true)
                ->join('skills', 'questions.skill_id', '=', 'skills.id')
                ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
                ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
                ->join('levels', 'tracks.level_id', '=', 'levels.id')
                ->where('tracks.field_id', $fieldId)
                ->where(function($query) use ($targetMaxile, $maxileRange) {
                    $query->whereBetween('levels.start_maxile_level', [$targetMaxile - $maxileRange, $targetMaxile + $maxileRange])
                          ->orWhereBetween('levels.end_maxile_level', [$targetMaxile - $maxileRange, $targetMaxile + $maxileRange])
                          ->orWhere(function($q) use ($targetMaxile) {
                              $q->where('levels.start_maxile_level', '<=', $targetMaxile)
                                ->where('levels.end_maxile_level', '>=', $targetMaxile);
                          });
                })
                ->select('questions.*', 'levels.start_maxile_level', 'levels.end_maxile_level', 'tracks.field_id')
                ->inRandomOrder()
                ->first();
            
            if (!$question) {
                $question = Question::where('questions.skill_id', '>', 0)
                    ->where('questions.is_diagnostic', true)
                    ->join('skills', 'questions.skill_id', '=', 'skills.id')
                    ->join('skill_track', 'skills.id', '=', 'skill_track.skill_id')
                    ->join('tracks', 'skill_track.track_id', '=', 'tracks.id')
                    ->where('tracks.field_id', $fieldId)
                    ->select('questions.*')
                    ->inRandomOrder()
                    ->first();
                    
                if ($question) {
                    $level = DB::table('tracks')
                        ->join('levels', 'tracks.level_id', '=', 'levels.id')
                        ->where('tracks.field_id', $fieldId)
                        ->select('levels.start_maxile_level', 'levels.end_maxile_level')
                        ->first();
                        
                    if ($level) {
                        $question->start_maxile_level = $level->start_maxile_level;
                        $question->end_maxile_level = $level->end_maxile_level;
                    }
                }
            }
            
            if ($question) {
                $questions->push($question);
            }
        }
        
        if ($questions->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No questions available for diagnostic test.',
            ], 500);
        }
        
        // Store questions in question_user table
        foreach ($questions as $question) {
            DB::table('question_user')->insert([
                'question_id' => $question->id,
                'test_id' => $test,
                'user_id' => $user->id,
                'question_answered' => false,
                'correct' => false,
                'attempts' => 0,
                'assessment_type' => 'diagnostic',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        Log::info('Diagnostic started', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'test_id' => $test,
            'estimated_level' => $estimatedLevel,
            'question_count' => $questions->count(),
            'fields_tested' => $publicFields->count(),
        ]);
        
        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
            'test_id' => $test,
            'estimated_level' => $estimatedLevel,
            'total_questions' => $questions->count(),
            'resumed' => false,
            'questions' => $questions->map(function ($q) {
                $maxileLevel = 100;
                if (isset($q->start_maxile_level) && isset($q->end_maxile_level)) {
                    $maxileLevel = ($q->start_maxile_level + $q->end_maxile_level) / 2;
                }
                
                return [
                    'id' => $q->id,
                    'question' => $q->question ?? '',
                    'image_url' => $q->question_image,
                    'maxile_level' => (int)$maxileLevel,
                    'correct_option_id' => (int)$q->correct_answer,
                    'options' => [
                        ['id' => 0, 'text' => $q->answer0 ?? '', 'image_url' => $q->answer0_image],
                        ['id' => 1, 'text' => $q->answer1 ?? '', 'image_url' => $q->answer1_image],
                        ['id' => 2, 'text' => $q->answer2 ?? '', 'image_url' => $q->answer2_image],
                        ['id' => 3, 'text' => $q->answer3 ?? '', 'image_url' => $q->answer3_image],
                    ],
                ];
            }),
        ]);
    }

    /**
     * Submit answer and get next batch of questions
     * 
     * Algorithm: "Hit Ceiling Twice OR Floor Once"
     * - Ceiling: 2 wrong answers total → cement at level below
     * - Floor: 1 correct at max level → cement at max level
     * - Returns one question per incomplete field (batch)
     */
    public function submit(Request $request)
    {
        // 1. VALIDATE & SETUP
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:diagnostic_sessions,id',
            'question_id' => 'required|integer|exists:questions,id',
            'selected_answer' => 'required|integer|min:0|max:3',
            'response_time' => 'nullable|integer',
        ]);

        $sessionId = $validated['session_id'];
        $questionId = $validated['question_id'];
        $selectedAnswer = $validated['selected_answer'];

        // Get session and question
        $session = DiagnosticSession::findOrFail($sessionId);
        $question = Question::with(['skill.tracks.level', 'field'])->findOrFail($questionId);
        
        // Verify session belongs to authenticated user
        if ($session->user_id !== $this->user->id) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if session is still active
        if ($session->status !== 'in_progress') {
            return response()->json(['ok' => false, 'message' => 'Session is not active'], 400);
        }

        // 2. CHECK ANSWER
        $isCorrect = $this->checkAnswer($question, $selectedAnswer);
        $fieldId = $question->field_id;
        
        // Get current track and level
        $currentTrack = $question->skill->tracks->first();
        if (!$currentTrack || !$currentTrack->level) {
            return response()->json(['ok' => false, 'message' => 'Question has no valid track/level'], 400);
        }
        
        $currentMaxile = $currentTrack->level->start_maxile_level;

        // 3. SAVE RESPONSE
        DiagnosticResponse::create([
            'diagnostic_session_id' => $sessionId,
            'question_id' => $questionId,
            'skill_id' => $question->skill_id,
            'track_id' => $currentTrack->id,
            'field_id' => $fieldId,
            'selected_answer' => (string)$selectedAnswer,
            'is_correct' => $isCorrect,
            'response_time_seconds' => $validated['response_time'] ?? 0,
        ]);

        $session->increment('item_count');

        // 4. GET OR CREATE FIELD PROGRESS
        $fieldProgress = DiagnosticFieldProgress::firstOrCreate(
            [
                'session_id' => $sessionId,
                'field_id' => $fieldId,
            ],
            [
                'current_level' => $this->getStartingMaxile($fieldId),
                'wrong_count_at_level' => 0,
                'correct_count_at_level' => 0,
                'completed' => false,
            ]
        );

        $fieldJustCompleted = null;

        // 5. APPLY ALGORITHM
        if ($isCorrect) {
            // ✅ CORRECT ANSWER
            $fieldProgress->correct_count_at_level++;
            
            $maxLevel = $this->getMaxLevelForField($fieldId);
            
            if ($fieldProgress->current_level >= $maxLevel) {
                // AT MAX LEVEL - CEMENT HERE!
                $fieldProgress->final_level = $maxLevel;
                $fieldProgress->completed = true;
                
                Log::info("Field {$fieldId} CEMENTED at MAX level {$maxLevel}");
                
                $fieldProgress->save();
                
                $fieldJustCompleted = $this->getCompletedFieldInfo($fieldProgress);
                
                // CHECK IF ALL FIELDS COMPLETE
                $completedFields = DiagnosticFieldProgress::where('session_id', $sessionId)
                    ->where('completed', true)
                    ->count();
                
                $totalPublicFields = Field::where('status_id', 3)->count();
                
                if ($completedFields >= $totalPublicFields) {
                    return $this->completeDiagnostic($session);
                }
            } else {
                // Move up
                $nextMaxile = $this->getNextLevelUp($fieldProgress->current_level, $fieldId);
                $fieldProgress->current_level = $nextMaxile;
                
                Log::info("Field {$fieldId}: Correct! Moving to {$nextMaxile}");
            }
            
        } else {
            // ❌ WRONG ANSWER
            $fieldProgress->wrong_count_at_level++;
            
            Log::info("Field {$fieldId}: Wrong! Total wrongs: {$fieldProgress->wrong_count_at_level}");
            
            if ($fieldProgress->wrong_count_at_level >= 2) {
                // HIT CEILING - CEMENT!
                $levelBelow = $this->getNextLevelDown($fieldProgress->current_level, $fieldId);
                $fieldProgress->final_level = $levelBelow;
                $fieldProgress->completed = true;
                
                Log::info("Field {$fieldId} CEMENTED at level {$levelBelow}");
                
                $fieldProgress->save();
                
                $fieldJustCompleted = $this->getCompletedFieldInfo($fieldProgress);
                
                // CHECK IF ALL FIELDS COMPLETE
                $completedFields = DiagnosticFieldProgress::where('session_id', $sessionId)
                    ->where('completed', true)
                    ->count();
                
                $totalPublicFields = Field::where('status_id', 3)->count();
                
                if ($completedFields >= $totalPublicFields) {
                    return $this->completeDiagnostic($session);
                }
            } else {
                // Drop down
                $levelBelow = $this->getNextLevelDown($fieldProgress->current_level, $fieldId);
                $fieldProgress->current_level = $levelBelow;
                
                Log::info("Field {$fieldId}: Dropping to {$levelBelow}");
            }
        }

        $fieldProgress->save();

        // 6. GET NEXT BATCH OF QUESTIONS
        $nextQuestions = $this->getNextQuestionBatch($session);

        if (empty($nextQuestions)) {
            return $this->completeDiagnostic($session);
        }

        // 7. RETURN RESPONSE
        $response = [
            'ok' => true,
            'questions' => $nextQuestions,
        ];

        if ($fieldJustCompleted) {
            $response['completed_field'] = $fieldJustCompleted;
        }

        return response()->json($response);
    }

    /**
     * Get diagnostic result
     */
    public function getResult(Request $request)
    {
        $user = $this->user;
        
        $session = DiagnosticSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();
        
        if (!$session) {
            return response()->json([
                'ok' => false,
                'message' => 'No completed diagnostic found',
            ], 404);
        }
        
        $result = $session->result ?? [];
        
        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
            'completed_at' => $session->completed_at,
            'result' => [
                'total_questions' => $result['total_questions'] ?? 0,
                'correct_answers' => $result['correct_answers'] ?? 0,
                'accuracy' => round(($result['accuracy'] ?? 0) * 100, 1),
                'placement_level' => $result['placement_level'] ?? 0,
                'maxile_level' => $result['maxile_level'] ?? 0,
                'level_name' => $this->getLevelName($result['placement_level'] ?? 0),
            ],
        ]);
    }

    /**
     * Abandon an incomplete diagnostic
     */
    public function abandonDiagnostic(Request $request, $sessionId)
    {
        $user = $this->user;
        $session = DiagnosticSession::findOrFail($sessionId);
        
        if ($session->user_id !== $user->id) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }
        
        if ($session->status !== 'in_progress') {
            return response()->json(['ok' => false, 'message' => 'Session already completed or abandoned'], 400);
        }
        
        $session->update([
            'status' => 'abandoned',
            'completed_at' => now(),
        ]);
        
        Log::info('Diagnostic abandoned', [
            'user_id' => $user->id,
            'session_id' => $session->id,
        ]);
        
        return response()->json([
            'ok' => true,
            'message' => 'Diagnostic abandoned successfully',
        ]);
    }

    // ============================================
    // PRIVATE HELPER METHODS - ADAPTIVE DIAGNOSTIC
    // ============================================

    /**
     * Check if the selected answer is correct
     */
    private function checkAnswer($question, $selectedAnswer)
    {
        return $question->correct_answer == $selectedAnswer;
    }

    /**
     * Get next level up based on maxile ranges from levels table
     */
    private function getNextLevelUp($currentMaxile, $fieldId)
    {
        $nextLevel = Level::where('start_maxile_level', '>', $currentMaxile)
            ->where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('start_maxile_level', 'asc')
            ->first();
        
        return $nextLevel ? $nextLevel->start_maxile_level : $currentMaxile;
    }

    /**
     * Get next level down based on maxile ranges from levels table
     */
    private function getNextLevelDown($currentMaxile, $fieldId)
    {
        $prevLevel = Level::where('end_maxile_level', '<', $currentMaxile)
            ->where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('end_maxile_level', 'desc')
            ->first();
        
        return $prevLevel ? $prevLevel->start_maxile_level : $currentMaxile;
    }

    /**
     * Get the starting maxile level for a field
     */
    private function getStartingMaxile($fieldId)
    {
        $startLevel = Level::where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('start_maxile_level', 'asc')
            ->first();
        
        return $startLevel ? $startLevel->start_maxile_level : 100;
    }

    /**
     * Get the maximum maxile level for a field
     */
    private function getMaxLevelForField($fieldId)
    {
        $maxLevel = Level::where('status_id', 3)
            ->whereHas('tracks', function($query) use ($fieldId) {
                $query->where('field_id', $fieldId)
                      ->where('status_id', 3);
            })
            ->orderBy('end_maxile_level', 'desc')
            ->first();
        
        return $maxLevel ? $maxLevel->start_maxile_level : 600;
    }

    /**
     * Get next batch of questions - one per incomplete field
     */
    private function getNextQuestionBatch($session)
    {
        $fieldIds = Field::where('status_id', 3)
            ->orderBy('id', 'asc')
            ->pluck('id')
            ->toArray();
        
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

            $targetMaxile = $fieldProgress ? $fieldProgress->current_level : $this->getStartingMaxile($fieldId);

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
     * Format question for response
     */
    private function formatQuestion($question)
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
    private function getCompletedFieldInfo($fieldProgress)
    {
        $field = Field::find($fieldProgress->field_id);
        
        $level = Level::where('start_maxile_level', '<=', $fieldProgress->final_level)
            ->where('end_maxile_level', '>=', $fieldProgress->final_level)
            ->first();
        
        $fieldName = $field->field ?? 'Unknown';
        $levelName = $level ? $level->description : 'Level ' . $fieldProgress->final_level;
        
        return "{$fieldName} - {$levelName}";
    }

    /**
     * Complete the diagnostic and calculate results
     */
    private function completeDiagnostic($session)
    {
        $fieldProgresses = DiagnosticFieldProgress::where('session_id', $session->id)->get();
        
        foreach ($fieldProgresses as $progress) {
            if (!$progress->completed) {
                $progress->completed = true;
                $progress->final_level = $progress->current_level;
                $progress->save();
            }
        }

        $responses = DiagnosticResponse::where('diagnostic_session_id', $session->id)->get();
        $totalQuestions = $responses->count();
        $correctAnswers = $responses->where('is_correct', true)->count();
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        $averageMaxile = $fieldProgresses->avg('final_level');

        $fieldResults = [];
        foreach ($fieldProgresses as $progress) {
            $field = Field::find($progress->field_id);
            
            $level = Level::where('start_maxile_level', '<=', $progress->final_level)
                ->where('end_maxile_level', '>=', $progress->final_level)
                ->first();
            
            $fieldResults[] = [
                'field_id' => $progress->field_id,
                'field_name' => $field->field ?? 'Unknown',
                'maxile' => $progress->final_level,
                'level_name' => $level ? $level->description : 'Level ' . $progress->final_level,
            ];
        }

        $session->update([
            'status' => 'completed',
            'completed_at' => now(),
            'end_maxile' => $averageMaxile,
        ]);

        $this->user->update([
            'maxile_level' => $averageMaxile,
            'last_test_date' => now(),
        ]);

        $overallLevel = Level::where('start_maxile_level', '<=', $averageMaxile)
            ->where('end_maxile_level', '>=', $averageMaxile)
            ->first();

        Log::info('Diagnostic completed', [
            'user_id' => $this->user->id,
            'session_id' => $session->id,
            'accuracy' => $accuracy,
            'average_maxile' => $averageMaxile,
        ]);

        return response()->json([
            'ok' => true,
            'result' => [
                'overall_level' => $overallLevel ? $overallLevel->description : 'Level ' . round($averageMaxile),
                'overall_maxile' => round($averageMaxile, 0),
                'accuracy' => round($accuracy, 1),
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'field_results' => $fieldResults,
            ],
        ]);
    }

    // ============================================
    // PRIVATE HELPER METHODS - EXISTING
    // ============================================

    /**
     * Estimate starting level based on user hints
     */
    private function estimateStartingLevel($user)
    {
        if ($user->grade) {
            return $this->gradeToLevel($user->grade);
        }
        
        if ($user->date_of_birth) {
            $age = Carbon::parse($user->date_of_birth)->age;
            return $this->ageToLevel($age);
        }
        
        if ($user->birth_year) {
            $age = now()->year - $user->birth_year;
            return $this->ageToLevel($age);
        }
        
        return 3;
    }

    /**
     * Convert grade to level
     */
    private function gradeToLevel($grade)
    {
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
        
        return $gradeMap[$grade] ?? 3;
    }

    /**
     * Convert age to estimated level
     */
    private function ageToLevel($age)
    {
        if ($age <= 6) return 1;
        if ($age >= 12) return 6;
        
        return min(6, max(1, $age - 5));
    }

    /**
     * Get level name from level number
     */
    private function getLevelName($level)
    {
        $levelNames = [
            1 => 'Primary 1',
            2 => 'Primary 2',
            3 => 'Primary 3',
            4 => 'Primary 4',
            5 => 'Primary 5',
            6 => 'Primary 6',
        ];
        
        return $levelNames[$level] ?? 'Unknown';
    }
}