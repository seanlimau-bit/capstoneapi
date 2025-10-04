<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Log;
use App\ErrorLog;
use DateTime;
use DB;

class Question extends Model
{
    use RecordLog;    
//    protected static $recordEvents = ['created'];    overriding what is to be logged
    
    protected $hidden = ['user_id', 'created_at', 'updated_at','pivot'];
    protected $fillable = ['user_id','skill_id','difficulty_id','question', 'type_id','status_id', 'answer0', 'answer1', 'answer2', 'answer3', 'answer4', 'correct_answer', 'source', 'question_image','answer0_image','answer1_image','answer2_image','answer3_image','answer4_image','calculator'];

    protected $casts = [
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'reviewed_at'    => 'datetime',
        'qa_reviewed_at' => 'datetime',
        'published_at'   => 'datetime',   
    ];

    public function qaIssues()
    {
        return $this->hasMany(\App\Models\QAIssue::class);
    }

    //relationship
    public function author() {                        //who created this question
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function level() {
        // Get the first track from the skill's tracks
        $track = $this->skill?->tracks()?->first();
        return $track?->level() ?? null;
    }
    
    public function difficulty(){
        return $this->belongsTo(\App\Models\Difficulty::class);
    }

    public function skill() {
        return $this->belongsTo(\App\Models\Skill::class);
    }

    public function status() {
        return $this->belongsTo(\App\Models\Status::class);
    }
    
    public function scopePublic($q) { return $q->where('status_id', 3); }

    public function type() {
        return $this->belongsTo(\App\Models\Type::class);
    }

    public function solutions(){
        return $this->hasMany(\App\Models\Solution::class);
    }   

    public function hints(){
        return $this->hasMany(\App\Models\Hint::class);
    }

    public function quizzes(){
        return $this->belongsToMany(\App\Models\Quiz::class)->withPivot('date_answered','correct')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)
        ->withPivot([
            'question_answered', 'answered_date', 'correct', 'test_id',
            'quiz_id', 'attempts', 'assessment_type', 'kudos'
        ])
        ->withTimestamps();
    }

    public function tests(){
        return $this->belongsToMany(\App\Models\Test::class, 'question_user')->withPivot('question_answered', 'answered_date','correct', 'user_id','attempts')->withTimestamps();
    }

    public function getDifficultyTextAttribute()
    {
        if (is_string($this->difficulty)) {
            $decoded = json_decode($this->difficulty, true);
            if ($decoded && isset($decoded['short_description'])) {
                return $decoded['short_description'];
            }
            return $this->difficulty;
        }
        
        if (is_array($this->difficulty) && isset($this->difficulty['short_description'])) {
            return $this->difficulty['short_description'];
        }
        
        return 'medium'; // default
    }

    public function attempts($userid){
        $num_attempts =$this->users()->whereUserId($userid)->select('attempts')->first(); 
        return $num_attempts ? $num_attempts->attempts:1;
    }

    public function correctness($user, $answers)
    {
        if ($this->type_id == 2) {
            // Fill-in-the-blank (up to 4 answers)
            return
            (!isset($answers[0]) || $answers[0] == $this->answer0) &&
            (!isset($answers[1]) || $answers[1] == $this->answer1) &&
            (!isset($answers[2]) || $answers[2] == $this->answer2) &&
            (!isset($answers[3]) || $answers[3] == $this->answer3);
        }

        // Multiple-choice (type 1)
        return $this->correct_answer == $answers[0];
    }

    public function answered($user, $correctness, $test)
    {
        return \App\Models\QuestionUser::where('question_id', $this->id)
        ->where('user_id', $user->id)
        ->where('test_id', $test?->id)
        ->update([
            'question_answered' => true,
            'answered_date' => now(),
            'correct' => $correctness,
            'test_id' => $test?->id,
            'attempts' => $this->attempts($user->id) + 1,
            'kudos' => $this->difficulty_id + 1,
            'assessment_type' => $test ? 'test':null,
            'updated_at' => now(),
        ]);
    }

    public static function getQaStatuses() {
        try {
            // Extract ENUM values from database schema
            $type = DB::select("SHOW COLUMNS FROM questions LIKE 'qa_status'")[0]->Type;
            preg_match('/^enum\(\'(.+)\'\)$/', $type, $matches);
            return explode("','", $matches[1]);
        } catch (Exception $e) {
            // Fallback to distinct values from data
            return self::distinct()->pluck('qa_status')->filter()->sort()->values()->toArray();
        }
    }
    /*
     * Assigns all necessary relationships for a test question:
     * 1. Skill → user (skill_user)
     * 2. Question → user for test (question_user)
     * 3. Question → test (via question_user)
     * 4. Skill → test (skill_test)
     * 5. Track(s) of the skill → user (track_user)
     *
     * Note: Test-user relation is assumed to be created when the test was initialized.
     */

    public function assigned($user, $test)
    {
        $now = now();

        // 1. Link question to user and test (question_user)
        DB::table('question_user')->updateOrInsert(
            [
                'question_id' => $this->id,
                'test_id' => $test->id,
                'user_id' => $user->id,
            ],
            [
                'question_answered' => false,
                'correct' => false,
                'attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 2. Link skill to user (skill_user)
        DB::table('skill_user')->updateOrInsert(
            [
                'skill_id' => $this->skill_id,
                'user_id' => $user->id,
            ],
            [
                'skill_test_date' => $now,
                'skill_passed' => false,
                'difficulty_passed' => false,
                'noOfTries' => 0,
                'correct_streak' => 0,
                'fail_streak' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 3. Link skill to test (skill_test)
        DB::table('skill_test')->updateOrInsert(
            [
                'skill_id' => $this->skill_id,
                'test_id' => $test->id,
            ],
            [
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // 4. Link track(s) of this skill to user (track_user)
        $tracks = Skill::find($this->skill_id)?->tracks ?? collect();

        foreach ($tracks as $track) {
            DB::table('track_user')->updateOrInsert(
                [
                    'track_id' => $track->id,
                    'user_id' => $user->id,
                ],
                [
                    'track_maxile' => 0.00,
                    'track_passed' => false,
                    'track_test_date' => $now,
                    'doneNess' => 0.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        return $test->fresh();
    }


    /*
     *  Assigns skill to users, questions to users, questions to quiz, quiz to user.
     */
    public function assignQuiz($user, $quiz, $house){
        $user->myQuestions()->attach([$this->id=>['quiz_id'=>$quiz->id]]);
        $user->skill_user()->sync([$this->skill_id],false);
        $this->quizzes()->sync([$quiz->id],false);
        $quiz->skills()->sync([$this->skill_id], false);
        $track = $this->skill->tracks()->pluck('id')->intersect($house->tracks()->pluck('id'));
        $user->testedTracks()->syncWithoutDetaching($tracks);
        return $quiz;
    }

    public function processProgressFor($user, $correct, $test = null)
    {
        $now = now();
        $skill = $this->skill;
        $track = $skill->tracks()->first();
        $field = $track->field;
        $difficulty = $this->difficulty_id;


        // Load skill_user pivot record
        $pivot = $skill->users()->where('user_id', $user->id)->first()?->pivot;

        $correct_streak = $pivot?->correct_streak ?? 0;
        $fail_streak = $pivot?->fail_streak ?? 0;
        $difficulty_passed = $pivot?->difficulty_passed ?? 0;
        $skill_passed = $pivot?->skill_passed ?? 0;
        $total_correct = $pivot?->total_correct_attempts ?? 0;
        $total_incorrect = $pivot?->total_incorrect_attempts ?? 0;
        $noOfTries = $pivot?->noOfTries ?? 0;

        $max_difficulty = config('app.difficulty_levels');
        $to_pass = config('app.number_to_pass');
        $to_fail = config('app.number_to_fail');

        $noOfTries++;
        if ($correct) {
            $correct_streak++;
            $fail_streak = 0;
            $total_correct++;
        } else {
            $fail_streak++;
            $correct_streak = 0;
            $total_incorrect++;
        }

        // Upgrade/downgrade logic
        if ($correct && $difficulty > $difficulty_passed && $correct_streak >= $to_pass) {
            $difficulty_passed = $difficulty;
            $correct_streak = 1; // reset
        } elseif (!$correct && $difficulty <= $difficulty_passed && $fail_streak >= $to_fail) {
            $difficulty_passed = max(0, $difficulty_passed - 1);
            $fail_streak = 1; // reset
        }

        $skill_passed = $difficulty_passed >= $max_difficulty;
        $skill_maxile = $difficulty_passed > 0
        ? ($skill_passed
            ? $track->level->end_maxile_level
            : $track->level->start_maxile_level + ($difficulty_passed * 100 / $max_difficulty))
        : $track->level->start_maxile_level;

        // Update skill_user pivot
        $skill->users()->syncWithoutDetaching([
            $user->id => [
                'skill_test_date' => $now,
                'skill_passed' => $skill_passed,
                'difficulty_passed' => $difficulty_passed,
                'skill_maxile' => round($skill_maxile, 2),
                'noOfTries' => $noOfTries,
                'correct_streak' => $correct_streak,
                'fail_streak' => $fail_streak,
                'total_correct_attempts' => $total_correct,
                'total_incorrect_attempts' => $total_incorrect,
                'updated_at' => $now,
            ]
        ]);

        // Update track maxile
        $passedSkills = $track->skills()->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('skill_passed', true);
        })->count();

        $totalSkills = $track->skills()->count();
        $track_passed = ($passedSkills === $totalSkills) && $totalSkills > 0;

        $track_maxile = $track_passed
        ? $track->level->end_maxile_level
        : round(($track->level->start_maxile_level + ($passedSkills / max(1, $totalSkills)) * ($track->level->end_maxile_level - $track->level->start_maxile_level)), 2);

        $track->users()->syncWithoutDetaching([
            $user->id => [
                'track_test_date' => $now,
                'track_passed' => $track_passed,
                'track_maxile' => $track_maxile,
                'updated_at' => $now,
            ]
        ]);

        // Update field maxile (highest of all track maxiles in this field)
        $highestTrackMaxile = $user->testedTracks()
        ->where('tracks.field_id', $field->id)
        ->wherePivot('track_maxile', '>', 0)
        ->max('track_maxile') ?? 0;

        $field_user = $user->fields()
        ->where('field_id', $field->id)
        ->wherePivot('month_achieved', date('Ym'))
        ->first();

        $existing_field_maxile = $field_user?->pivot?->field_maxile ?? 0;

        if ($highestTrackMaxile > $existing_field_maxile) {
            $user->fields()->syncWithoutDetaching([
                $field->id => [
                    'field_maxile' => $highestTrackMaxile,
                    'field_test_date' => $now,
                    'month_achieved' => date('Ym'),
                    'updated_at' => $now,
                ]
            ]);
        }
        $user->maxile_level = $user->fields()
        ->wherePivot('field_maxile', '>', 0)
        ->avg('field_user.field_maxile') ?? 0;

        $user->save();

        return [
            'skill_maxile' => $skill_maxile,
            'track_maxile' => $track_maxile,
            'field_maxile' => max($existing_field_maxile, $highestTrackMaxile),
        ];
    }

    public const QA_STATUS_LABELS = [
        'unreviewed'     => 'Unreviewed',
        'ai_generated'   => 'AI Generated',
        'approved'       => 'Approved',
        'flagged'        => 'Flagged',
        'needs_revision' => 'Needs Revision',
    ];

    // New, additive: value => label map, built from the enum list you already expose
    public static function qaStatusOptions(): array
    {
        $enumValues = collect(self::getQaStatuses()); // your existing method
        return $enumValues->mapWithKeys(
            fn($v) => [$v => self::QA_STATUS_LABELS[$v] ?? ucfirst(str_replace('_',' ', $v))]
        )->all();
    }

    // Optional convenience accessor (doesn’t break anything):
    public function getQaStatusLabelAttribute(): string
    {
        return self::qaStatusOptions()[$this->qa_status] ?? (string) $this->qa_status;
    }
    public function reports()
    {
        return $this->hasMany(QuestionReport::class);
    }

    public function pendingReports()
    {
        return $this->reports()->where('status', 'pending');
    }
}
