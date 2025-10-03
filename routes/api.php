<?php

use App\Http\Controllers\OTPController;
use App\Http\Controllers\VisitorController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TrackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// === User Info Route (Protected) ===
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'name' => $user->firstname,
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/request-otp', [OTPController::class, 'requestOtp']);
    Route::post('/verify-otp', [OTPController::class, 'verifyOtp']);
    Route::post('/verify-email', [OTPController::class, 'verifyEmail']);
    Route::post('/complete-registration', [OTPController::class, 'completeRegistration']);
});

// Visitor Routes
Route::post('/mastercode', [VisitorController::class, 'mastercode']);
Route::post('/diagnostic', [VisitorController::class, 'diagnostic']);
Route::post('/subscribe', [VisitorController::class, 'subscribe']);

Route::get('/tracks', [App\Http\Controllers\API\TrackController::class, 'index']);
/*
|--------------------------------------------------------------------------
| Protected Routes (Require Sanctum Token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // === Dashboard & QA ===
    Route::get('/protected', [App\Http\Controllers\DashboardController::class, 'index']);
    Route::post('/qa', [App\Http\Controllers\CheckAnswerController::class, 'index']);
    Route::post('/qa/answer', [App\Http\Controllers\CheckAnswerController::class, 'answer']);

    // === User Management ===
    Route::apiResource('users', App\Http\Controllers\UserController::class);
    Route::get('/users/{user}/reset', [App\Http\Controllers\UserController::class, 'reset']);
    Route::get('/users/{user}/performance', [App\Http\Controllers\UserController::class, 'performance']);
    Route::post('/users/{user}/diagnostic', [App\Http\Controllers\UserController::class, 'diagnostic']);
    Route::get('/users/{user}/report', [App\Http\Controllers\DiagnosticController::class, 'report']);
    Route::get('/users/subscription/status', [App\Http\Controllers\UserController::class, 'subscriptionStatus']);
    Route::apiResource('users.tests', App\Http\Controllers\UserTestController::class);
    Route::get('users/{username}/logs', [App\Http\Controllers\LogController::class, 'show']);

    // === Course Management ===
    Route::apiResource('courses', App\Http\Controllers\CourseController::class);
    Route::post('courses/{course}', [App\Http\Controllers\CourseController::class, 'copy']);
    Route::apiResource('courses.houses', App\Http\Controllers\CourseHouseController::class);
    Route::apiResource('courses.users', App\Http\Controllers\CourseUserController::class);

    // === Quiz Management ===
    Route::apiResource('quizzes', App\Http\Controllers\QuizController::class);
    Route::post('/quizzes/{quiz}/copy', [App\Http\Controllers\QuizController::class, 'copy']);
    Route::get('/quizzes/create', [App\Http\Controllers\QuizController::class, 'create']);
    Route::apiResource('quizzes.houses', App\Http\Controllers\QuizHouseController::class);
    Route::apiResource('quizzes.skills', App\Http\Controllers\QuizSkillController::class);
    Route::post('/quizzes/{quiz}/generate', [App\Http\Controllers\QuizSkillController::class, 'generateQuiz']);
    Route::delete('quizzes/{quiz}/houses', [App\Http\Controllers\QuizHouseController::class, 'deleteHouses']);
    Route::delete('quizzes/{quiz}/skills', [App\Http\Controllers\QuizSkillController::class, 'deleteSkills']);

    // === Skills Management ===
  //  Route::apiResource('skills', App\Http\Controllers\SkillController::class);
   // Route::post('skills/{skills}/copy', [App\Http\Controllers\SkillController::class, 'copy']);
   // Route::get('skills/{skills}/passed', [App\Http\Controllers\SkillController::class, 'usersPassed']);
//    Route::post('skills/search', [App\Http\Controllers\SkillController::class, 'search']);
    Route::apiResource('skills.questions', App\Http\Controllers\SkillQuestionsController::class);

    Route::apiResource('tracks.questions', App\Http\Controllers\TrackQuestionsController::class);
    Route::apiResource('tracks.skills', App\Http\Controllers\TrackSkillController::class);
    Route::get('skills/{skill}/tracks', [App\Http\Controllers\TrackSkillController::class, 'list_tracks']);
    Route::delete('skills/{skill}/tracks', [App\Http\Controllers\TrackSkillController::class, 'deleteTracks']);
    Route::delete('tracks/{track}/skills', [App\Http\Controllers\TrackSkillController::class, 'deleteSkills']);

    // === House Management ===
    Route::apiResource('houses', App\Http\Controllers\HouseController::class);
    Route::apiResource('houses.users', App\Http\Controllers\HouseUserController::class);
    Route::apiResource('houses.tracks', App\Http\Controllers\HouseTrackController::class);

    // === Testing & Assessment ===
    Route::apiResource('tests', App\Http\Controllers\TestController::class);
    Route::post('/test/protected/{type}', [App\Http\Controllers\DiagnosticController::class, 'index']);
    Route::post('/test/answers', [App\Http\Controllers\AnswerController::class, 'answer']);
    Route::get('/test/trackquestions/{track}', [App\Http\Controllers\TrackTestController::class, 'index']);
    Route::post('/loginInfo', [App\Http\Controllers\DiagnosticController::class, 'login']);

    // === System Resources ===
//    Route::apiResources([
//        'difficulties'   => App\Http\Controllers\DifficultyController::class,
//        'fields'         => App\Http\Controllers\FieldController::class,
 //       'levels'         => App\Http\Controllers\LevelController::class,
  //      'permissions'    => App\Http\Controllers\PermissionController::class,
   //     'roles'          => App\Http\Controllers\RoleController::class,
//        'units'          => App\Http\Controllers\UnitController::class,
  //      'types'          => App\Http\Controllers\TypeController::class,
  //      'questions'      => App\Http\Controllers\QuestionController::class,
    //    'enrolments'     => App\Http\Controllers\EnrolmentController::class,
    //]);

    // == Partners Management ==
  /*  Route::prefix('partners')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [PartnerController::class, 'index']);
        Route::post('/', [PartnerController::class, 'store']);
        Route::('/{code}', [PartnerController::class, 'show']);
        Route::put('/{partner}', [PartnerController::class, 'update']);
        Route::delete('/{partner}', [PartnerController::class, 'destroy']);
    });

    Route::post('partner/{partnerCode}/webhook', [PartnerWebhookController::class, 'handleStatusUpdate']);
*/
    // === Logs ===
 //   Route::get('logs', [App\Http\Controllers\LogController::class, 'index']);

});