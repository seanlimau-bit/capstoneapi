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
     * CRITICAL: Add constructor to check permissions on ALL methods
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            
            // First check: Must be able to access admin
            if (!$user->canAccessAdmin()) {
                abort(403, 'Admin access required.');
            }

            // Second check: Check specific permission based on route action
            $action = $request->route()->getActionMethod();
            
            switch ($action) {
                case 'index':
                case 'show':
                case 'export':
                case 'performance':
                    if (!$user->hasPermission('list_users')) {
                        abort(403, 'Permission denied: You do not have permission to view users.');
                    }
                    break;
                    
                case 'create':
                case 'store':
                    if (!$user->hasPermission('create_users')) {
                        abort(403, 'Permission denied: You do not have permission to create users.');
                    }
                    break;
                    
                case 'edit':
                case 'update':
                case 'inlineUpdate':
                case 'reset':
                case 'diagnostic':
                case 'administrator':
                    if (!$user->hasPermission('modify_users')) {
                        abort(403, 'Permission denied: You do not have permission to modify users.');
                    }
                    break;
                    
                case 'destroy':
                case 'bulkAction':
                    if (!$user->hasPermission('delete_users')) {
                        abort(403, 'Permission denied: You do not have permission to delete users.');
                    }
                    break;
            }
            
            return $next($request);
        });
    }

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
                  ->orWhere('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Apply role filter
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }
        
        // Apply status filter
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
        
        $query->orderBy('created_at', 'desc');
        $query->with(['role']);
        
        $users = $query->paginate(20);
        
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
        
        $roles = Role::orderBy('role')->get();
        
        return view('admin.users.index', compact('users', 'roles', 'totals'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $isApi = $request->expectsJson() || $request->wantsJson() || $request->is('api/*');

        $baseRules = [
            'name'          => ['required','string','max:255'],
            'email'         => ['required','email','unique:users,email'],
            'contact'       => ['required','string','regex:/^\+\d{1,3}(?:\s?\d{3,})+$/'],
            'date_of_birth' => ['required','date','before:today'],
            'role_id'       => ['nullable','integer','exists:roles,id'],
            'status'        => ['nullable', Rule::in(['active','inactive','suspended'])],
            'is_admin'      => ['sometimes','boolean'],
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
        } else {
            $validated = $request->validate($baseRules);
        }

        try {
            DB::beginTransaction();

            $data = $isApi ? $request->all() : $validated;
            $contact = preg_replace('/\s+/', '', $data['contact']);

            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'contact'           => $contact,
                'date_of_birth'     => $data['date_of_birth'],
                'role_id'           => $data['role_id'] ?? null,
                'status'            => $data['status'] ?? 'active',
                'is_admin'          => (bool)($data['is_admin'] ?? false),
                'email_verified_at' => !empty($data['email_verified']) ? now() : null,
            ]);

            DB::commit();

            if ($isApi) {
                return response()->json([
                    'message' => 'User created successfully',
                    'user'    => $user,
                    'code'    => 201,
                ], 201);
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'User created successfully', 'user' => $user]);
            }

            return redirect()->route('admin.users.index')->with('success', 'User created successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            
            if ($isApi) {
                return response()->json(['message' => $e->getMessage(), 'code' => 500], 500);
            }
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified user
     */
    public function show(User $user, Request $request, LookupOptionsService $lookups)
    {
        $user->load([
            'role',
            'enrolledClasses',
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
            'myquestions.difficulty',
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

        $stats = [
            'total_questions_answered' => $user->myQuestions()->wherePivot('question_answered', true)->count(),
            'correct_answers'          => $user->myQuestions()->wherePivot('correct', true)->count(),
            'accuracy_percentage'      => $user->accuracy(),
            'tests_completed'          => $user->tests()->wherePivot('test_completed', true)->count(),
            'quizzes_completed'        => $user->quizzes()->wherePivot('quiz_completed', true)->count(),
            'tracks_passed'            => $user->testedTracks()->wherePivot('track_passed', true)->count(),
            'skills_mastered'          => $user->skill_user()->wherePivot('skill_passed', true)->count(),
            'current_maxile'           => $user->currentMaxile(),
        ];

        $statusOptions = $lookups->statuses();
        
        $currentStatusId = null;
        if (isset($user->status_id)) {
            $currentStatusId = $user->status_id;
        } elseif (!empty($user->status)) {
            $match = $statusOptions->first(fn($s) => strtolower($s['text']) === strtolower($user->status));
            $currentStatusId = $match['id'] ?? null;
        }

        $roles = Role::orderBy('role')->get(['id','role']);

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
        // Prevent non-admins with modify permission from elevating privileges
        if ($request->filled('role_id') && !auth()->user()->hasPermission('manage_roles')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to change user roles.'
            ], 403);
        }

        // Prevent user from demoting their own role
        if ($user->id === auth()->id() && $request->filled('role_id')) {
            $newRole = Role::find($request->role_id);
            if ($newRole && !in_array($newRole->role, ['System Admin', 'Administrator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own admin role.'
                ], 403);
            }
        }

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

        $request->request->remove('email');
        $validated = $request->validate($rules);
        
        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'User updated successfully', 'user' => $user]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'User updated successfully']);
        }

        return redirect()->route('admin.users.show', $user)->with('success', 'User updated successfully');
    }
public function inlineUpdate(Request $request, User $user)
{
    // Prevent non-admins with modify permission from elevating privileges
    if ($request->filled('role_id') && !auth()->user()->hasPermission('manage_roles')) {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to change user roles.'
        ], 403);
    }
    
    // Prevent user from demoting their own role
    if ($user->id === auth()->id() && $request->filled('role_id')) {
        $newRole = Role::find($request->role_id);
        if ($newRole && !in_array($newRole->role, ['System Admin', 'Administrator'])) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own admin role.'
            ], 403);
        }
    }

    $validated = $request->validate([
        'field' => 'required|string',
        'value' => 'nullable'
    ]);

    // Map field to actual column name if needed
    $field = $validated['field'];
    $value = $validated['value'];

    $user->update([$field => $value]);

    return response()->json([
        'success' => true,
        'message' => 'User updated successfully',
        'user' => $user->fresh()
    ]);
}
    /**
     * Remove the specified user
     */
    public function destroy(User $user, Request $request)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            $message = 'You cannot delete your own account';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 400);
            }
            return back()->withErrors($message);
        }

        // Prevent deletion of other admins without special permission
        if ($user->canAccessAdmin() && !auth()->user()->hasPermission('delete_admin_users')) {
            $message = 'You do not have permission to delete admin users';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            return back()->withErrors($message);
        }

        // Prevent deletion if user has enrolled classes
        if ($user->enrolledClasses()->exists()) {
            $message = 'User has existing classes and cannot be deleted';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 400);
            }
            return back()->withErrors($message);
        }
        
        // Prevent deletion if user has question attempts/progress
        if (DB::table('question_user')->where('user_id', $user->id)->exists()) {
            $message = 'User has question progress/attempts and cannot be deleted';
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