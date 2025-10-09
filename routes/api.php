<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\DiagnosticController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes - Active Routes for Math Diagnostic System
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// === OTP Authentication ===
Route::prefix('auth')->group(function () {
    Route::post('/request-otp', [OTPController::class, 'sendOtp']);
    Route::post('/verify-otp', [OTPController::class, 'verifyOtp']);
});

// === diagnostic Hint (Public - for users without token yet) ===
Route::post('/diagnostic/hint', [DiagnosticController::class, 'storeHint']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Sanctum Token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // === User Info ===
    Route::get('/me', function (Request $request) {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        return response()->json($user->only([
            'id', 'name', 'firstname', 'lastname', 'email',
            'phone_number', 'date_of_birth', 'birth_year',
            'maxile_level', 'game_level', 'last_test_date',
            'created_at', 'updated_at',
        ]));
    });
    
    // === diagnostic Routes ===
    Route::get('/diagnostic/status', [DiagnosticController::class, 'getStatus']);
    Route::post('/diagnostic/start', [DiagnosticController::class, 'start']);
    Route::post('/diagnostic/submit', [DiagnosticController::class, 'submit']);
    Route::get('/diagnostic/result', [DiagnosticController::class, 'getResult']);
    Route::post('/diagnostic/abandon/{sessionId}', [DiagnosticController::class, 'abandonDiagnostic']);
    
 
    // === By subject ===
    Route::get('/tracks', [App\Http\Controllers\API\TrackController::class, 'index']);

});

/*
|--------------------------------------------------------------------------
| Legacy Routes (Commented Out - Not Currently Used)
|--------------------------------------------------------------------------
| Uncomment these if you need them in the future
*/

/*
// === Legacy Auth Routes ===
Route::post('/auth/complete-registration', [OTPController::class, 'completeRegistration']);

// === Legacy Visitor Routes ===
Route::post('/mastercode', [VisitorController::class, 'mastercode']);
Route::post('/diagnostic', [VisitorController::class, 'diagnostic']);
Route::post('/subscribe', [VisitorController::class, 'subscribe']);

// === Legacy Track Routes ===
Route::get('/tracks', [App\Http\Controllers\API\TrackController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    
    // === Legacy Question Reports ===
    Route::post('/questions/{question}/report', [App\Http\Controllers\API\QuestionReportController::class, 'store']);
    
    // === Legacy Dashboard & QA ===
    Route::get('/protected', [App\Http\Controllers\DashboardController::class, 'index']);
    Route::post('/test/answers', [App\Http\Controllers\AnswerController::class, 'answer']);
    Route::get('/test/trackquestions/{track}', [App\Http\Controllers\TrackTestController::class, 'index']);
    
    // === Legacy User Management ===
    Route::apiResource('users', App\Http\Controllers\UserController::class);
    Route::get('/users/{user}/reset', [App\Http\Controllers\UserController::class, 'reset']);
    Route::get('/users/{user}/performance', [App\Http\Controllers\UserController::class, 'performance']);
    Route::post('/users/{user}/diagnostic', [App\Http\Controllers\UserController::class, 'diagnostic']);
    Route::get('/users/{user}/report', [App\Http\Controllers\DiagnosticController::class, 'report']);
    Route::get('/users/subscription/status', [App\Http\Controllers\UserController::class, 'subscriptionStatus']);
    Route::apiResource('users.tests', App\Http\Controllers\UserTestController::class);
    Route::get('users/{username}/logs', [App\Http\Controllers\LogController::class, 'show']);
    
    // === Legacy Course Management ===
    Route::apiResource('courses', App\Http\Controllers\CourseController::class);
    Route::post('courses/{course}', [App\Http\Controllers\CourseController::class, 'copy']);
    Route::apiResource('courses.houses', App\Http\Controllers\CourseHouseController::class);
    Route::apiResource('courses.users', App\Http\Controllers\CourseUserController::class);
    
    // === Legacy Quiz Management ===
    Route::apiResource('quizzes', App\Http\Controllers\QuizController::class);
    Route::post('/quizzes/{quiz}/copy', [App\Http\Controllers\QuizController::class, 'copy']);
    Route::get('/quizzes/create', [App\Http\Controllers\QuizController::class, 'create']);
    Route::apiResource('quizzes.houses', App\Http\Controllers\QuizHouseController::class);
    Route::apiResource('quizzes.skills', App\Http\Controllers\QuizSkillController::class);
    Route::post('/quizzes/{quiz}/generate', [App\Http\Controllers\QuizSkillController::class, 'generateQuiz']);
    Route::delete('quizzes/{quiz}/houses', [App\Http\Controllers\QuizHouseController::class, 'deleteHouses']);
    Route::delete('quizzes/{quiz}/skills', [App\Http\Controllers\QuizSkillController::class, 'deleteSkills']);
    
    // === Legacy Skills Management ===
    Route::apiResource('skills.questions', App\Http\Controllers\SkillQuestionsController::class);
    Route::apiResource('tracks.questions', App\Http\Controllers\TrackQuestionsController::class);
    Route::apiResource('tracks.skills', App\Http\Controllers\TrackSkillController::class);
    Route::get('skills/{skill}/tracks', [App\Http\Controllers\TrackSkillController::class, 'list_tracks']);
    Route::delete('skills/{skill}/tracks', [App\Http\Controllers\TrackSkillController::class, 'deleteTracks']);
    Route::delete('tracks/{track}/skills', [App\Http\Controllers\TrackSkillController::class, 'deleteSkills']);
    
    // === Legacy House Management ===
    Route::apiResource('houses', App\Http\Controllers\HouseController::class);
    Route::apiResource('houses.users', App\Http\Controllers\HouseUserController::class);
    Route::apiResource('houses.tracks', App\Http\Controllers\HouseTrackController::class);
    
    // === Legacy Testing & Assessment ===
    Route::apiResource('tests', App\Http\Controllers\TestController::class);
    Route::post('/test/protected/{type}', [App\Http\Controllers\DiagnosticController::class, 'index']);
    Route::post('/loginInfo', [App\Http\Controllers\DiagnosticController::class, 'login']);
});
*/