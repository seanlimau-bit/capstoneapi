<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Question;
use App\Models\Plan;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use DB;
use App\Services\LookupOptionsService;


class UserController extends Controller
{
    /**
     * Display a listing of users with statistics for admin interface
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Apply role filter
        if ($request->filled('role_id')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('role_id', $request->role_id);
            });
        }
        
        // Apply status filter using your actual 'status' field
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                $query->where('status', 'active');
                break;
                case 'inactive':
                $query->where('status', 'inactive');
                break;
                case 'suspended':
                $query->where('status', 'suspended');
                break;
                case 'verified':
                $query->whereNotNull('email_verified_at');
                break;
                case 'unverified':
                $query->whereNull('email_verified_at');
                break;
            }
        }
        
        // Order by latest first
        $query->orderBy('created_at', 'desc');
        
        // Eager load relationships to avoid N+1 queries
        $query->with(['role']);
        
        $users = $query->paginate(20);
        
        // Calculate statistics using your actual 'status' field
        $totals = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
        ];
        
        if ($request->expectsJson()) {
            return response()->json([
                'users' => $users->items(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'totals' => $totals
            ]);
        }
        
        // Get all roles for the filter dropdown - use 'role' column
        $roles = collect(); // Empty collection by default
        if (class_exists('\App\Models\Role')) {
            try {
                $roles = \App\Models\Role::orderBy('role')->get();
            } catch (\Exception $e) {
                $roles = collect();
            }
        }
        
        return view('admin.users.index', compact('users', 'roles', 'totals'));
    }

    // Bulk operations using 'status' field
    public function bulkActivate(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);
        
        try {
            User::whereIn('id', $request->user_ids)->update(['status' => 'active']);
            
            return response()->json([
                'success' => true,
                'message' => count($request->user_ids) . ' users activated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error activating users: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkSuspend(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);
        
        try {
            // Don't allow suspending the current user
            $userIds = collect($request->user_ids)->filter(function($id) {
                return $id != auth()->id();
            });
            
            if ($userIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot suspend yourself or no valid users selected'
                ]);
            }
            
            User::whereIn('id', $userIds)->update(['status' => 'suspended']);
            
            return response()->json([
                'success' => true,
                'message' => count($userIds) . ' users suspended successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error suspending users: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $isApi = $request->expectsJson() || $request->wantsJson() || $request->is('api/*');

        if ($request->has('date_of_birth') && !$request->has('date_of_birth')) {
            $request->merge(['date_of_birth' => $request->input('date_of_birth')]);
        }

        // Common rules (API + Web)
        $baseRules = [
            'name'          => ['required','string','max:255'],
            'email'         => ['required','email','unique:users,email'],
            'contact'       => ['required','string','regex:/^\+\d{1,3}(?:\s?\d{3,})+$/'],
            'date_of_birth' => ['required','date','before:today'],
            'role_id'       => ['nullable','integer','exists:roles,id'],
            'status'        => ['nullable', Rule::in(['active','inactive','suspended'])],
            'is_admin'      => ['sometimes','boolean'],
            'is_active'     => ['sometimes','boolean'],
            'email_verified'=> ['sometimes','boolean'],
        ];

        if ($isApi) {
            $validator = Validator::make($request->all(), $baseRules);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                    'code'    => 422
                ], 422);
            }

            try {
                DB::beginTransaction();

                $contact = preg_replace('/\s+/', '', $request->contact);

                $user = User::create([
                    'name'              => $request->name,
                    'email'             => $request->email,
                    'contact'           => $contact,
                    'date_of_birth'     => $request->date_of_birth,   // <-- correct column
                    'role_id'           => $request->role_id ?? null, // <-- single role_id
                    'status'            => $request->status ?? 'active',
                    'is_admin'          => (bool) ($request->is_admin ?? false),
                    'is_active'         => array_key_exists('is_active', $request->all()) ? (bool)$request->is_active : true,
                    'email_verified_at' => !empty($request->email_verified) ? now() : null,
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'User created successfully',
                    'user'    => $user,
                    'code'    => 201,
                ], 201);
            } catch (\Throwable $e) {
                DB::rollBack();
                return response()->json(['message' => $e->getMessage(), 'code' => 500], 500);
            }
        }

        // Web path
        $validated = $request->validate($baseRules);

        try {
            DB::beginTransaction();

            $contact = preg_replace('/\s+/', '', $validated['contact']);

            $user = User::create([
                'name'              => $validated['name'],
                'email'             => $validated['email'],
                'contact'           => $contact,
                'date_of_birth'     => $validated['date_of_birth'],  // <-- correct column
                'role_id'           => $validated['role_id'] ?? null, // <-- single role_id
                'status'            => $validated['status'] ?? 'active',
                'is_admin'          => (bool)($validated['is_admin'] ?? false),
                'is_active'         => array_key_exists('is_active', $validated) ? (bool)$validated['is_active'] : true,
                'email_verified_at' => !empty($validated['email_verified']) ? now() : null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User created successfully', 'user' => $user]);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    /**
     * Display the specified user
     */
    public function show(User $user, Request $request, LookupOptionsService $lookups)
    {
        // Eager loads (your original, kept intact)
        $user->load([
            'role',
            'enrolledClasses', // house_role_user pivot
            'tests' => function($q) {
                $q->withPivot('test_completed','completed_date','result','attempts','kudos')
                ->orderBy('test_user.completed_date','desc');
            },
            'quizzes' => function($q) {
                $q->withPivot('quiz_completed','completed_date','result','attempts')
                ->orderBy('quiz_user.completed_date','desc');
            },
            'myQuestions' => function($q) {
                $q->withPivot('question_answered','answered_date','correct','attempts','test_id','quiz_id','assessment_type','kudos')
                ->orderBy('question_user.answered_date','desc')
                ->limit(100);
            },
            'testedTracks' => function($q) {
                $q->withPivot('track_maxile','track_passed','track_test_date','doneNess')
                ->orderBy('track_user.track_test_date','desc');
            },
            'skill_user' => function($q) {
                $q->withPivot([
                    'skill_maxile','skill_test_date','skill_passed','difficulty_passed',
                    'noOfTries','correct_streak','total_correct_attempts',
                    'total_incorrect_attempts','fail_streak'
                ])->orderBy('skill_user.skill_test_date','desc');
            },
            'fields' => function($q) {
                $q->withPivot('field_maxile','field_test_date','month_achieved')
                ->orderBy('field_user.field_test_date','desc');
            },
            'logs' => function($q) {
                $q->orderBy('created_at','desc')->limit(50);
            },
        ]);

        // Stats (your originals + keep helpers)
        $stats = [
            'total_questions_answered' => $user->myQuestions()->wherePivot('question_answered', true)->count(),
            'correct_answers'          => $user->myQuestions()->wherePivot('correct', true)->count(),
            'accuracy_percentage'      => $user->accuracy(),            // assuming helper on model
            'tests_completed'          => $user->tests()->wherePivot('test_completed', true)->count(),
            'quizzes_completed'        => $user->quizzes()->wherePivot('quiz_completed', true)->count(),
            'tracks_passed'            => $user->testedTracks()->wherePivot('track_passed', true)->count(),
            'skills_mastered'          => $user->skill_user()->wherePivot('skill_passed', true)->count(),
            'current_maxile'           => $user->currentMaxile(),       // assuming helper on model
        ];

        // Lookup-driven statuses for selects/badges
        // returns collection of ['id' => ..., 'text' => ...]
        $statusOptions = $lookups->statuses();

        // If you store a string enum in users.status, we’ll try to resolve its lookup id for convenience.
        // If you store users.status_id, the Blade will use that directly.
        $currentStatusId = null;
        if (isset($user->status_id)) {
            $currentStatusId = $user->status_id;
        } elseif (!empty($user->status)) {
            $match = $statusOptions->first(fn($s) => strtolower($s['text']) === strtolower($user->status));
            $currentStatusId = $match['id'] ?? null;
        }

        // Roles for inline select
        $roles = Role::orderBy('role')->get(['id','role']);

        // JSON responses preserved
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'user'   => $user,
                'stats'  => $stats,
                'lookups'=> [
                    'statuses' => $statusOptions,
                    'roles'    => $roles,
                ],
                'code'   => 200
            ]);
        }

        return view('admin.users.show', [
            'user'             => $user,
            'stats'            => $stats,
            'statusOptions'    => $statusOptions,
            'currentStatusId'  => $currentStatusId,
            'roles'            => $roles,
        ]);
    }
    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        // Base validation rules
        $rules = [
            'name' => 'sometimes|string|max:255',
            'firstname' => 'sometimes|string|max:255',
            'lastname' => 'sometimes|string|max:255', 
            'phone_number' => 'sometimes|string|max:20|nullable',
            'date_of_birth' => 'sometimes|date|nullable',
            'status' => 'sometimes|in:active,inactive,suspended',
            'role_id' => 'sometimes|exists:roles,id|nullable',
            'maxile_level' => 'sometimes|numeric|nullable',
            'game_level' => 'sometimes|numeric|nullable',
        ];

        // Don't allow email changes for security
        $request->request->remove('email');

        $validated = $request->validate($rules);
        
        // UPDATE THE USER - this was missing!
        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User updated successfully', 'user' => $user]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User updated successfully']);
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully');
    }

    public function inlineUpdate(User $user, Request $request)
    {
        $request->validate([
            'field' => ['required','string'],
            'value' => ['nullable'],
        ]);

        // enum options from schema
        $statusEnum       = ['potential','active','suspended','inactive','pending'];
        $accessTypeEnum   = ['premium','freemium','trial','basic','free'];
        $paymentMethodEnum= ['free','credit_card','telco_billing','school_billing','tuition_billing'];

        // whitelist fields we will allow from the UI
        $allowed = [
            // text
            'name','firstname','lastname','phone_number','telco_provider','telco_subscriber_id',
            'partner_subscriber_id','contact','billing_method','signup_channel','image','auth0',

            // enums (string)
            'status','access_type','payment_method',

            // integers
            'role_id','lives','game_level','partner_id',

            // decimals
            'maxile_level',

            // booleans (tinyint 0/1)
            'partner_verified','diagnostic','email_verified','is_admin',

            // dates/timestamps
            'date_of_birth','last_test_date','next_test_date','trial_expires_at',
            'suspended_at','cancelled_at','activated_at','email_verified_at',
        ];

        $field = $request->string('field')->toString();
        if (!in_array($field, $allowed, true)) {
            return response()->json(['ok'=>false,'message'=>'Field not allowed'], 422);
        }

        // normalize/cast/validate per-field
        $val = $request->input('value');

        // enums
        if ($field === 'status') {
            $request->validate(['value' => ['required', Rule::in($statusEnum)]]);
            $user->status = $val;
        } elseif ($field === 'access_type') {
            $request->validate(['value' => ['required', Rule::in($accessTypeEnum)]]);
            $user->access_type = $val;
        } elseif ($field === 'payment_method') {
            $request->validate(['value' => ['required', Rule::in($paymentMethodEnum)]]);
            $user->payment_method = $val;

        // integers
        } elseif (in_array($field, ['role_id','lives','game_level','partner_id'], true)) {
            $request->validate(['value' => ['nullable','integer']]);
            $user->{$field} = ($val === '' || $val === null) ? null : (int)$val;

        // decimals
        } elseif ($field === 'maxile_level') {
            $request->validate(['value' => ['nullable','numeric']]);
            $user->maxile_level = ($val === '' || $val === null) ? 0 : (float)$val;

        // booleans → tinyint(1)
        } elseif (in_array($field, ['partner_verified','diagnostic','email_verified','is_admin'], true)) {
            // accept true/false/"1"/"0"/"on"
            $user->{$field} = filter_var($val, FILTER_VALIDATE_BOOL) ? 1 : 0;

        // dates & timestamps
        } elseif (in_array($field, [
            'date_of_birth','last_test_date','next_test_date','trial_expires_at',
            'suspended_at','cancelled_at','activated_at','email_verified_at',
        ], true)) {
            if ($val === '' || $val === null) {
                $user->{$field} = null;
            } else {
                try {
                    // 'date_of_birth' expects date; others accept datetime (but date works too)
                    $dt = Carbon::parse($val);
                    $user->{$field} = $field === 'date_of_birth'
                        ? $dt->format('Y-m-d')
                        : $dt; // let Eloquent cast to timestamp
                } catch (\Throwable $e) {
                    return response()->json(['ok'=>false,'message'=>'Invalid date/time'], 422);
                }
            }

        // strings (trim; empty → null where sensible)
        } else {
            $v = is_string($val) ? trim($val) : $val;
            $user->{$field} = ($v === '') ? null : $v;
        }

        $user->save();

        return response()->json([
            'ok' => true,
            'updated_at_human' => optional($user->updated_at)->format('M d, Y'),
        ]);
    }

     /*
     * Remove the specified user
     */
    public function destroy(User $user, Request $request)
    {

            // Prevent deletion if user has enrolled classes
        if ($user->enrolledClasses()->exists()) {
            $message = 'User has existing classes and cannot be deleted';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 400);
            }
            return back()->withErrors($message);
        }

        $user->delete();

        $message = 'User deleted successfully';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    public function showTestQuestion(User $user, Test $test)
    {
        // Optional safety: ensure this test belongs to the user
        if (!$user->tests()->where('tests.id', $test->id)->exists()) {
            abort(404);
        }

        // Eager-load relations only; don't put them in SELECT
        $with = [];
        if (method_exists(Question::class, 'skill')) {
            $with[] = 'skill:id,skill'; // skill name
            if (method_exists(\App\Models\Skill::class ?? (object)[], 'tracks')) {
                // Assuming Track has field_id and Track->field relation
                $with[] = 'skill.tracks:id,track,field_id';
                $with[] = 'skill.tracks.field:id,field';
            }
        }

        $attempts = $user->myQuestions()
            ->wherePivot('test_id', $test->id)
            ->withPivot('question_answered','answered_date','correct','attempts','kudos','assessment_type')
            ->with('skill.tracks.field')
            ->orderBy('question_user.answered_date', 'desc')
            ->get(); // no constrained columns

        $testPivot = $user->tests()
            ->where('tests.id', $test->id)
            ->first()?->pivot;

        // Flags so Blade can conditionally render columns
        $hasSkill = method_exists(Question::class, 'skill');

        return view('admin.users.test_questions', [
            'user'      => $user,
            'test'      => $test,
            'attempts'  => $attempts,
            'testPivot' => $testPivot,
            'hasSkill'  => $hasSkill,
            // we always compute fields from skill->tracks->field in the blade if present
        ]);
    }



    /**
     * Handle bulk actions for multiple users
     */
    public function bulkAction(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->is_admin) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,suspend,verify,delete'
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $count = 0;

        foreach ($users as $user) {
            // Prevent actions on self
            if ($user->id === $authUser->id) continue;

            switch ($validated['action']) {
                case 'activate':
                $user->update(['is_active' => true, 'is_suspended' => false]);
                $count++;
                break;
                case 'suspend':
                $user->update(['is_suspended' => true, 'is_active' => false]);
                $count++;
                break;
                case 'verify':
                $user->update(['email_verified_at' => now()]);
                $count++;
                break;
                case 'delete':
                if (!$user->enrolledClasses()->exists()) {
                    $user->delete();
                    $count++;
                }
                break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully {$validated['action']}d {$count} users"
        ]);
    }

    /**
     * Export users data
     */
    public function export()
    {
        $authUser = Auth::user();
        if (!$authUser->is_admin) {
            abort(403, 'Unauthorized access');
        }

        $users = User::select(['id', 'name', 'email', 'is_admin', 'is_active', 'email_verified_at', 'created_at'])
        ->get()
        ->map(function($user) {
            return [
                'ID' => $user->id,
                'Name' => $user->name,
                'Email' => $user->email,
                'Role' => $user->is_admin ? 'Admin' : 'Student',
                'Status' => $user->is_active ? 'Active' : 'Inactive',
                'Verified' => $user->email_verified_at ? 'Yes' : 'No',
                'Joined' => $user->created_at?->format('Y-m-d'),
            ];
        });

        $filename = 'users_export_' . now()->format('Y-m-d') . '.json';

        return response()->json($users)
        ->header('Content-Type', 'application/json')
        ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    /**
     * Reset user's progress and data
     */
    public function reset($id)
    {
        $user = User::findOrFail($id);
        
        // Reset user relationships and data
        $user->myQuestions()->detach();
        $user->testedTracks()->detach();
        $user->fields()->detach();
        $user->skill_user()->detach();
        $user->tests()->detach();
        $user->quizzes()->detach();
        $user->tests()->delete();
        
        $user->update([
            'maxile_level' => 0,
            'diagnostic' => true
        ]);

        return response()->json([
            'message' => "Reset complete for {$user->name}. Game level {$user->game_level} maintained.",
            'user' => $user,
            'code' => 200
        ]);
    }

    /**
     * Toggle diagnostic mode for user
     */
    public function diagnostic($id)
    {
        $authUser = Auth::user();
        if (!$authUser->is_admin) {
            return response()->json(['message' => 'Unauthorized access', 'code' => 401], 401);
        }

        $user = User::findOrFail($id);
        $user->update(['diagnostic' => !$user->diagnostic]);

        return response()->json([
            'message' => "Diagnostic mode " . ($user->diagnostic ? 'enabled' : 'disabled') . " for {$user->name}",
            'user' => $user,
            'code' => 200
        ]);
    }

    /**
     * Toggle administrator status
     */
    public function administrator($id)
    {
        $authUser = Auth::user();
        if (!$authUser->is_admin) {
            return response()->json(['message' => 'Unauthorized access', 'code' => 401], 401);
        }

        $user = User::findOrFail($id);
        $user->update(['is_admin' => !$user->is_admin]);

        return response()->json([
            'message' => "Administrator status " . ($user->is_admin ? 'granted' : 'revoked') . " for {$user->name}",
            'user' => $user,
            'code' => 200
        ]);
    }

    /**
     * Get user performance data
     */
    public function performance($id)
    {
        return response()->json([
            'message' => 'User performance retrieved',
            'performance' => User::whereId($id)
            ->with(['tracksPassed', 'completedTests', 'fieldMaxile', 'tracksFailed', 'incompletetests'])
            ->first(),
            'code' => 200
        ]);
    }

    /**
     * Update game score
     */
    public function gameScore(Request $request)
    {
        $validated = $request->validate([
            'old_game_level' => 'required|integer',
            'new_game_level' => 'required|integer'
        ]);

        $user = Auth::user();
        
        if ($validated['old_game_level'] !== $user->game_level) {
            return response()->json(['message' => 'Old game score mismatch', 'code' => 400], 400);
        }

        $user->update(['game_level' => $validated['new_game_level']]);

        return User::profile($user->id);
    }

    /**
     * Get subscription status
     */
    public function subscriptionStatus()
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $activeEnrolments = $user->enrolledClasses()->exists();
        
        return response()->json([
            'active' => $activeEnrolments,
            'user' => $user,
        ]);
    }

    /**
     * Handle profile image upload
     */
    private function handleImageUpload(Request $request, User $user)
    {
        // Delete old image
        if ($user->image && file_exists(public_path(parse_url($user->image, PHP_URL_PATH)))) {
            unlink(public_path(parse_url($user->image, PHP_URL_PATH)));
        }

        $filename = time() . '.png';
        $request->file('image')->move(public_path('images/profiles'), $filename);
        $user->update(['image' => url('/images/profiles/' . $filename)]);
    }
}