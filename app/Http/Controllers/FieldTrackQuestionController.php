<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use Auth;
use App\Models\Track;
use App\Models\User;
use DB;

class FieldTrackQuestionController extends Controller
{
  public function index(Track $track)
  {
    $user = Auth::guard('sanctum')->user();

    $questionsPerTest = \App\Models\Config::first()->questions_per_test;
    
    // Step 1: Get or create test
    $test = $this->getOrCreateTest($user, $track, $questionsPerTest);
    
    // Step 2: Return 5 unanswered questions from the test
    return $test->buildResponseFor($user, 5);
  }

  private function getOrCreateTest($user, $track, $questionsPerTest)
  {
    // Find incomplete test
    $test = $user->tests()
    ->where('test', 'LIKE', "%{$track->track} tracktest%")
    ->where('level_id', $track->level_id)
    ->whereHas('questions', function($q) use ($user) {
      $q->where('question_user.user_id', $user->id)
      ->where('question_user.question_answered', false);
    })
    ->latest()
    ->first();
    
    // If incomplete test exists, return it
    if ($test) {
      return $test;
    }
    
    // Create new test
    $test = $user->tests()->create([
      'test' => "{$user->name}'s {$track->track} tracktest",
      'description' => "{$user->name}'s " . now()->format('m/d/Y') . " {$track->track} tracktest",
      'level_id' => $track->level_id,
      'start_available_time' => now()->subDay(),
      'end_available_time' => now()->addYear(),
        'due_time' => now()->addYear(), // Add this line
        'diagnostic' => false
      ]);
    
    // Populate test with questions
    $this->populateTest($test, $track, $user, $questionsPerTest);
    
    return $test;
  }

  private function populateTest($test, $track, $user, $questionsPerTest)
  {
    // Get public skills from this track
    $trackSkillIds = $track->skills()->public()->pluck('id');
    
    if ($trackSkillIds->isEmpty()) {
      $this->notifyAdminNoPublicSkills($track);
      abort(422, 'No available questions for this track');
    }
    
    // Priority 1: Get questions NOT answered correctly
    $newQuestions = Question::public()
    ->whereIn('skill_id', $trackSkillIds)
    ->whereNotIn('id', function($query) use ($user, $trackSkillIds) {
      $query->select('question_id')
      ->from('question_user')
      ->where('user_id', $user->id)
      ->where('correct', 1)
      ->whereIn('question_id', function($subQuery) use ($trackSkillIds) {
        $subQuery->select('id')
        ->from('questions')
        ->whereIn('skill_id', $trackSkillIds);
      });
    })
    ->inRandomOrder()
    ->limit($questionsPerTest)
    ->get();
    
    $questionsNeeded = $questionsPerTest - $newQuestions->count();
    
    // Priority 2: Reuse questions from track if needed
    if ($questionsNeeded > 0) {
      $reusedQuestions = Question::public()
      ->whereIn('skill_id', $trackSkillIds)
      ->whereNotIn('id', $newQuestions->pluck('id'))
      ->inRandomOrder()
      ->limit($questionsNeeded)
      ->get();

      $newQuestions = $newQuestions->merge($reusedQuestions);
      $questionsNeeded = $questionsPerTest - $newQuestions->count();
    }
    
    // Priority 3: Pull from same level if still needed
    if ($questionsNeeded > 0) {
      $sameLevelQuestions = Question::public()
      ->whereHas('skill', function($q) use ($track, $trackSkillIds) {
        $q->public()
        ->whereHas('tracks', fn($query) => 
          $query->public()
          ->where('level_id', $track->level_id)
        )
        ->whereNotIn('id', $trackSkillIds);
      })
      ->whereNotIn('id', $newQuestions->pluck('id'))
      ->inRandomOrder()
      ->limit($questionsNeeded)
      ->get();

      $newQuestions = $newQuestions->merge($sameLevelQuestions);

        // If STILL not enough, notify admin
      if ($newQuestions->count() < $questionsPerTest) {
        $this->notifyAdminInsufficientQuestions($track, $questionsPerTest, $newQuestions->count());
      }
    }
    
    // Bulk insert into question_user
    $this->bulkAssignQuestions($newQuestions, $user, $test, $track);
  }

  private function bulkAssignQuestions($questions, $user, $test, $track)
  {
    $timestamp = now();
    $assessmentType = "track:{$track->track}";
    
    $pivotData = $questions->map(fn($q) => [
      'question_id' => $q->id,
      'user_id' => $user->id,
      'test_id' => $test->id,
      'question_answered' => false,
      'correct' => false,
      'answered_date' => null,
      'attempts' => 0,
      'quiz_id' => null,
      'assessment_type' => $assessmentType,
      'kudos' => 0,
      'created_at' => $timestamp,
      'updated_at' => $timestamp,
    ])->toArray();
    
    DB::table('question_user')->insert($pivotData);
  }

  private function notifyAdminInsufficientQuestions($track, $needed, $found)
  {
    try {
      $config = \App\Models\Config::first();

      Mail::raw(
        "Insufficient questions for track: {$track->track}\n\n" .
        "Questions needed: {$needed}\n" .
        "Questions found: {$found}\n" .
        "Shortage: " . ($needed - $found) . "\n\n" .
        "Please add more public questions to the skills in this track.",
        function($message) use ($config, $track) {
          $message->to($config->email)
          ->subject("⚠️ Insufficient Questions: {$track->track}");
        }
      );
    } catch (\Exception $e) {
      \Log::error("Failed to notify admin about insufficient questions: " . $e->getMessage());
    }
  }

  private function notifyAdminNoPublicSkills($track)
  {
    try {
      $config = \App\Models\Config::first();
      Mail::raw(
        "Track '{$track->track}' has no public skills.\n\n" .
        "Please add public skills to this track before students can take tests.",
        function($message) use ($config, $track) {
          $message->to($config->email)
          ->subject("⚠️ No Public Skills: {$track->track}");
        }
      );
    } catch (\Exception $e) {
      \Log::error("Failed to notify admin: " . $e->getMessage());
    }
  }
}
