<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use DateTime;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Notifications\Notifiable;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use HasApiTokens, Notifiable, Authorizable, CanResetPassword, HasRoles, RecordLog;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'firstname', 'lastname', 'email', 'email_verified', 'image',
        'maxile_level', 'game_level', 'mastercode', 'contact', 'password',
        'is_admin', 'phone_number', 'date_of_birth', 'status',
           'partner_id', 'partner_subscriber_id', 'access_type', 'billing_method',
        'features', 'partner_verified', 'partner_status_updated_at','otp_code','otp_expires_at',
        'trial_expires_at', 'suspended_at', 'cancelled_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token', 'created_at'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['date_of_birth', 'last_test_date', 'next_test_date', 'activated_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verified' => 'boolean',
        'is_admin' => 'boolean',
        'is_subscriber' => 'boolean',
        'features' => 'array',
        'partner_verified' => 'boolean',
        'partner_status_updated_at' => 'datetime',
        'trial_expires_at' => 'datetime',
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'otp_expires_at'=> 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Partner Configuration Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user has lives remaining using partner-specific config
     */
    public function hasLivesRemaining(): bool
    {
        return \App\Services\LivesService::hasLivesRemaining($this);
    }

    /**
     * Get current lives count using partner-specific config
     */
    public function getCurrentLives()
    {
        return \App\Services\LivesService::getCurrentLives($this);
    }

    /**
     * Get partner-specific configuration
     */
    public function getPartnerConfig()
    {
        return \App\Services\PartnerConfigService::getConfig($this);
    }

    /**
     * Legacy method - maintained for backward compatibility
     * @deprecated Use getCurrentLives() instead
     */
    public function livesRemaining()
    {
        return $this->getCurrentLives();
    }

    public function needsProfileCompletion(): bool
    {
        // Don't check email_verified for users who just completed registration
        // Check if core profile fields are missing
        return !$this->firstname || 
               !$this->date_of_birth ||
               ($this->partner_id && !$this->partner_verified);
    }
    /*
    |--------------------------------------------------------------------------
    | Basic Relationships
    |--------------------------------------------------------------------------
    */

    public function mastercodes()
    {
        return $this->hasMany(Mastercode::class);
    }
    
    public function lives()
    {
        return $this->hasOne(UserLives::class);
    }
    
    public function livesTransactions()
    {
        return $this->hasMany(LivesTransaction::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function difficulties()
    {
        return $this->hasMany(Difficulty::class);
    }

    public function levels()
    {
        return $this->hasMany(Level::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function houses()
    {
        return $this->hasMany(House::class, 'house_id');
    }

    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Field and Skill Relationships
    |--------------------------------------------------------------------------
    */

    public function fields()
    {
        return $this->belongsToMany(Field::class)
            ->withPivot('field_maxile', 'field_test_date', 'month_achieved')
            ->withTimestamps();
    }

    public function skilluser()
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot(
                'skill_test_date', 'skill_passed', 'skill_maxile', 'noOfTries',
                'correct_streak', 'difficulty_passed', 'fail_streak'
            )
            ->withTimestamps();
    }

    public function skillspassed()
    {
        return $this->skilluser()->wherePivot('skill_passed', '=', TRUE)->get();
    }

    public function skill_user()
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot(
                'skill_test_date', 'skill_passed', 'skill_maxile', 'noOfTries',
                'correct_streak', 'difficulty_passed', 'fail_streak'
            );
    }

    public function skillMaxile()
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot(
                'skill_maxile', 'skill_test_date', 'noOfTries', 'correct_streak',
                'skill_passed', 'difficulty_passed'
            )
            ->select(
                'skill_id', 'skill', 'skill_maxile', 'skill_test_date',
                'noOfTries', 'correct_streak', 'skill_passed', 'difficulty_passed'
            )
            ->groupBy('skill');
    }

    public function completedSkills()
    {
        return $this->skillMaxile()->whereSkillPassed(True);
    }

    /*
    |--------------------------------------------------------------------------
    | Field Maxile Management
    |--------------------------------------------------------------------------
    */

    public function storefieldmaxile($maxile, $field_id)
    {
        $field_user = $this->fields()
            ->whereFieldId($field_id)
            ->whereMonthAchieved(date('Ym', time()))
            ->select('field_maxile')
            ->first();
            
        $old_maxile = $field_user ? $field_user->field_maxile : 0;

        if ($old_maxile < $maxile) {
            $this->fields()->sync([
                $field_id => [
                    'field_maxile' => $maxile,
                    'field_test_date' => new DateTime('now'),
                    'month_achieved' => date('Ym', time())
                ]
            ], false);
        }
        
        return $maxile;
    }

    public function getmyresults()
    {
        return $this->with('fields.user_maxile')->get();
    }

    public function getfieldmaxile()
    {
        return $this->belongsToMany(Field::class)
            ->withPivot('field_maxile', 'field_test_date', 'month_achieved')
            ->withTimestamps()
            ->select('field_maxile', 'field_test_date', 'month_achieved', 'field', 'id');
    }

    public function fieldMaxile()
    {
        return $this->belongsToMany(Field::class)
            ->withPivot('field_maxile', 'field_test_date')
            ->select('field', 'field_test_date', 'field_maxile')
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Enrollment and Role Management
    |--------------------------------------------------------------------------
    */

    public function enrolledClasses()
    {
        return $this->enrolment()
            ->where('expiry_date', '>=', now())
            ->orderBy('expiry_date', 'desc');
    }

    public function expiredClasses()
    {
        return $this->enrolment()
            ->withPivot('role_id')
            ->groupBy('house_id')
            ->where('expiry_date', '<', date("Y-m-d"))
            ->orderBy('expiry_date', 'desc');
    }

    public function houseRoles()
    {
        return $this->belongsToMany(Role::class, 'house_role_user')
            ->withPivot('house_id')
            ->withTimestamps();
    }

    public function roleHouse()
    {
        return $this->belongsToMany(House::class, 'house_role_user')
            ->withPivot('role_id')
            ->withTimestamps();
    }

    public function studentHouse()
    {
        return $this->roleHouse()
            ->whereRoleId(Role::where('role', 'LIKE', '%Student')->pluck('id'))
            ->groupBy('house_id');
    }

    public function teachHouse()
    {
        return $this->roleHouse()
            ->whereRoleId(Role::where('role', 'LIKE', '%Teacher')->pluck('id'))
            ->groupBy('house_id');
    }

    public function enrolment()
    {
        return $this->hasMany(Enrolment::class);
    }

    public function enrolclass($user_maxile)
    {
        $houses = House::whereIn(
            'course_id',
            Course::where('start_maxile_score', '<=', round($user_maxile / 100) * 100)->pluck('id')
        )->pluck('id')->all();
        
        foreach ($houses as $house) {
            $houses_id[$house] = ['role_id' => 6];
        }
        
        $this->roleHouse()->sync(1, false);
        return 'enrolment created';
    }

    public function validEnrolment($courseid)
    {
        return $this->enrolment()
            ->whereRoleId(Role::where('role', 'LIKE', '%Student')->pluck('id'))
            ->whereIn('house_id', House::whereIn('course_id', $courseid)->pluck('id'))
            ->where('expiry_date', '>=', new DateTime('today'))
            ->get();
    }

    public function validHouse()
    {
        return $this->enrolment()->get();
    }

    public function teachingHouses()
    {
        return $this->enrolment()
            ->where('role_id', '<', Role::where('role', 'LIKE', '%Teacher')->pluck('id'))
            ->groupBy('house_id');
    }

    public function hasClassRole($role, $house)
    {
        $houseRole = $this->houseRoles()
            ->with(['userHouses' => function ($q) use ($house) {
                $q->whereHouseId($house)->groupBy('house_id');
            }])
            ->groupBy('id')
            ->whereHouseId($house)
            ->get();

        if (is_string($role)) {
            return $houseRole->contains('role', $role);
        }
        
        return !!$role->intersect($houseRole)->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Test and Quiz Management
    |--------------------------------------------------------------------------
    */

    public function writetests()
    {
        return $this->hasMany(Test::class);
    }

    public function tests()
    {
        return $this->belongsToMany(Test::class)
            ->withPivot('test_completed', 'completed_date', 'result', 'attempts', 'kudos')
            ->withTimestamps();
    }

    public function incompletetests()
    {
        return $this->tests()
            ->wherePivot('test_completed', 0)
            ->where('tests.start_available_time', '<=', now())
            ->where('tests.end_available_time', '>=', now())
            ->orderBy('tests.created_at', 'desc');
    }

    public function diagnostictests()
    {
        return $this->incompletetests()->whereDiagnostic(TRUE);
    }

    public function currenttest()
    {
        return $this->incompletetests()->take(1);
    }

    public function completedtests()
    {
        return $this->tests()->whereTestCompleted(1);
    }

    public function quiz()
    {
        return $this->hasMany(Quiz::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class)
            ->withPivot('quiz_completed', 'completed_date', 'result', 'attempts')
            ->withTimestamps();
    }

    public function incompletequizzes()
    {
        return $this->quizzes()
            ->whereQuizCompleted(FALSE)
            ->where('start_available_time', '<=', new DateTime('today'))
            ->where('end_available_time', '>=', new DateTime('today'))
            ->orderBy('created_at', 'desc');
    }

    public function currentquiz()
    {
        return $this->incompletequizzes()->take(1);
    }

    public function completedquizzes()
    {
        return $this->quizzes()->whereQuizCompleted(1);
    }

    /*
    |--------------------------------------------------------------------------
    | Question Management
    |--------------------------------------------------------------------------
    */

    public function myQuestions()
    {
        return $this->belongsToMany(Question::class)
            ->withPivot(
                'question_answered', 'answered_date', 'correct', 'attempts',
                'test_id', 'quiz_id', 'assessment_type'
            )
            ->withTimestamps();
    }

    public function unattemptedQuestions()
    {
        return $this->myQuestions()
            ->wherePivot('question_answered', false)
            ->wherePivotNull('answered_date');
    }

    public function wrongQuestions()
    {
        return $this->myQuestions()
            ->wherePivot('question_answered', true)
            ->wherePivot('correct', false);
    }

    public function correctQuestions()
    {
        return $this->myQuestions()
            ->wherePivot('correct', true);
    }

    public function hasQuestion($question_id)
    {
        return $this->myQuestions()
            ->where('question_id', $question_id)
            ->exists();
    }

    public function numberOfAttempts($question_id)
    {
        return optional(
            $this->myQuestions()
                ->where('question_id', $question_id)
                ->select('attempts')
                ->first()
        )->attempts ?? 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Track Management
    |--------------------------------------------------------------------------
    */

    public function testedTracks()
    {
        return $this->belongsToMany(Track::class)
            ->withPivot('track_maxile', 'track_passed', 'track_test_date', 'doneNess')
            ->withTimestamps();
    }

    public function tracksPassed()
    {
        return $this->testedTracks()->whereTrackPassed(TRUE);
    }

    public function tracksFailed()
    {
        return $this->testedTracks()->whereTrackPassed(FALSE);
    }

    public function trackResults()
    {
        return $this->testedTracks()->pluck('track_maxile');
    }

    /*
    |--------------------------------------------------------------------------
    | Logging and Error Management
    |--------------------------------------------------------------------------
    */

    public function logs()
    {
        return $this->hasMany(Log::class)->orderBy('updated_at', 'desc')->take(20);
    }

    public function errorlogs()
    {
        return $this->hasMany(ErrorLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Calculation Methods
    |--------------------------------------------------------------------------
    */

    public function currentMaxile()
    {
        return $this->fields()
            ->wherePivot('field_maxile', '>', 0)
            ->avg('field_user.field_maxile') ?? 0;
    }

    public function accuracy()
    {
        $totalAnswered = $this->myQuestions()->sum('question_answered');
        return $totalAnswered ? 
            ($this->myQuestions()->sum('correct') / $totalAnswered * 100) : 0;
    }

    public function calculateQuizScore($quiz)
    {
        $quiz_questions = $this->myQuestions()->whereQuizId($quiz->id)->count();
        $correct = $this->myQuestions()->whereQuizId($quiz->id)->whereCorrect(TRUE)->count();
        
        $this->game_level = $this->game_level + $correct;
        $this->diagnostic = FALSE;
        $this->save();
        
        $correct_questions = $quiz_questions ? $correct / $quiz_questions : 0;
        return number_format($correct_questions * 100, 2, '.', '');
    }

    public function calculateUserMaxile($test)
    {
        $test->load('questions.skill.tracks');
        
        $test_tracks = $test->questions->pluck('skill.tracks')
            ->collapse()
            ->pluck('id')
            ->unique()
            ->all();
        
        if ($test->diagnostic) {
            $user_maxile = 100 + $this->testedTracks()
                ->whereIn('id', $test_tracks)
                ->avg('track_maxile');
        } elseif ($test->noOfSkillsPassed > 0) {
            $highest_passed = $this->tracksPassed()->max('level_id') ?? 
                (int)($this->maxile_level / 100);
            $noPassed = $this->tracksPassed()->whereLevelId($highest_passed)->count();
            $totalHighest = \App\Track::whereLevelId($highest_passed)->count();
            
            $maxile = $noPassed / max($totalHighest, 1) * 100 + 
                \App\Level::where('id', $highest_passed)->value('start_maxile_level');
            $user_maxile = max($maxile, $this->maxile_level);
        } else {
            $user_maxile = $this->maxile_level;
        }
        
        $this->update([
            'maxile_level' => $user_maxile,
            'last_test_date' => now(),
        ]);
        
        // Notify if maxile level is beyond 600
        if ($user_maxile > 600) {
            $this->sendHighMaxileNotification();
        }

        return $user_maxile;
    }

    /**
     * Send notification when user reaches high maxile level
     */
    private function sendHighMaxileNotification()
    {
        $note = 'This is a note to let you know that Student ' . $this->name . 
            ' at ' . $this->email . ' has reached beyond level 600,<br><br>' .
            'You might want to contact the parent at email address at ' . $this->email . 
            ' to suggest moving the child to pre-Algebra or other more advanced topics.<br><br>' .
            '<i>This is an automated machine generated by the All Gifted System.</i>';
            
        Mail::send([], [], function ($message) use ($note) {
            $message->from('pam@allgifted.com', 'All Gifted Admin')
                ->to('japher@allgifted.com')
                ->cc('kang@allgifted.com')
                ->subject('Student reached Maxile 600')
                ->setBody($note, 'text/html');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeAge()
    {
        return date_diff(date_create(Auth::user()->date_of_birth), date_create('today'))->y;
    }

    public function scopeProfile($query, $id)
    {
        return $query->whereId($id)->with([
            'getfieldmaxile', 'fields.user_maxile', 'enrolledClasses.roles',
            'enrolledClasses.houses.created_by',
            'enrolledClasses.houses.tracks.track_maxile',
            'enrolledClasses.houses.tracks.skills'
        ])->first();
    }

    public function scopeGameleader($query)
    {
        return $query->orderBy('game_level', 'desc')
            ->select(
                'image', 'maxile_level', 'game_level',
                'last_test_date as leader_since', 'firstname as name'
            )
            ->take(10)
            ->get();
    }

    public function scopeMaxileleader($query)
    {
        return $query->orderBy('maxile_level', 'desc')
            ->select(
                'image', 'maxile_level', 'game_level',
                'last_test_date as leader_since', 'firstname as name'
            )
            ->take(10)
            ->get();
    }

    public function scopeHighest_scores($query)
    {
        return $query->addSelect(
            DB::raw('MAX(maxile_level) AS highest_maxile'),
            DB::raw('MAX(game_level) AS highest_game'),
            DB::raw('AVG(game_level) AS average_game')
        )->first();
    }
}