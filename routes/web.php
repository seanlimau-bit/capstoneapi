<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\FieldController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\Admin\TypeController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\QAController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\ConfigurationController;
use App\Http\Controllers\Admin\DifficultyController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StatusController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\HintController;
use App\Http\Controllers\SolutionController;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/send-otp', [WebAuthController::class, 'sendOtp'])->name('auth.sendOtp');
Route::get('/verify', [WebAuthController::class, 'showVerify'])->name('auth.verify');
Route::post('/verify-otp', [WebAuthController::class, 'verifyOtp'])->name('auth.verifyOtp');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('auth.logout');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth'])->name('admin.')->group(function () {
    // ---------------- QA (qa middleware) ----------------
    Route::prefix('qa')->middleware(['qa'])->name('qa.')->group(function () {
        Route::get('/', [QAController::class, 'index'])->name('index');
        Route::get('/export', [QAController::class, 'export'])->name('export');
        Route::post('/bulk-approve', [QAController::class, 'bulkApprove'])->name('bulk-approve');
        Route::post('/bulk-flag', [QAController::class, 'bulkFlag'])->name('bulk-flag');
        Route::post('/issues/{issue}/resolve', [QAController::class, 'resolveIssue'])->name('resolve-issue');

        // Quick navigation to "next" question (e.g., next unreviewed)
        Route::get('/next', [QAController::class, 'next'])->name('next');

        Route::prefix('questions/{question}')->name('questions.')->group(function () {
            Route::get('/', [QAController::class, 'show'])->name('show'); // keep if you use it elsewhere
            Route::get('/review', [QAController::class, 'reviewQuestion'])->name('review');

            // Existing actions
            Route::post('/approve', [QAController::class, 'approveQuestion'])->name('approve');
            Route::post('/flag', [QAController::class, 'flagQuestion'])->name('flag');

            // New lightweight actions used by the updated QA UI
            Route::post('/status', [QAController::class, 'setStatus'])->name('status');     // unreviewed/approved/flagged/needs_revision/ai_generated
            Route::post('/assign', [QAController::class, 'assignToMe'])->name('assign');   // set qa_reviewer_id
            Route::post('/notes',  [QAController::class, 'saveNotes'])->name('notes');     // update qa_notes
        });
    });

    // ---------------- System Admin only ----------------
    Route::middleware(['admin'])->group(function () {
        // Dashboard
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard.index');

        // =====================================================
        // FIELDS MANAGEMENT
        // =====================================================
        Route::resource('fields', FieldController::class);
        Route::get('fields/export', [FieldController::class, 'exportAll'])->name('fields.export.all');
        Route::prefix('fields/{field}')->name('fields.')->group(function () {
            Route::get('/questions', [FieldController::class, 'questions'])->name('questions');
            Route::get('/analytics', [FieldController::class, 'analytics'])->name('analytics');
            Route::post('/duplicate', [FieldController::class, 'duplicate'])->name('duplicate');
            Route::prefix('tracks')->name('tracks.')->group(function () {
                Route::get('/', [FieldController::class, 'manageTracks'])->name('manage');
                Route::post('/', [FieldController::class, 'manageTracks'])->name('update');
                Route::post('/add', [FieldController::class, 'addTrack'])->name('add');
                Route::delete('/{track}', [FieldController::class, 'removeTrack'])->name('remove');
            });
        });

        // =====================================================
        // SKILLS MANAGEMENT
        // =====================================================
        Route::resource('skills', SkillController::class);
        Route::prefix('skills/{skill}')->name('skills.')->group(function () {
            Route::get('/data', [SkillController::class, 'getSkillData'])->name('data');
            Route::get('/similar', [SkillController::class, 'getSimilarSkills'])->name('similar');
            Route::get('/copy-sources', [SkillController::class, 'getSkillsForCopy'])->name('copy-sources');
            Route::post('/duplicate', [SkillController::class, 'duplicate'])->name('duplicate');

            Route::post('/add-track', [SkillController::class, 'addTrack'])->name('add-track');
            Route::delete('/tracks/{track}', [SkillController::class, 'removeTrack'])->name('remove-track');
            Route::post('/link-video', [SkillController::class, 'linkVideo'])->name('link-video');
            Route::delete('/videos/{video}', [SkillController::class, 'deleteVideo'])->name('deleteVideo');

            // =====================================================
            // QUESTIONS NESTED UNDER SKILLS
            // =====================================================
            Route::prefix('questions')->name('questions.')->group(function () {
                Route::get('/', [QuestionController::class, 'indexForSkill'])->name('index');
                Route::get('/create', [QuestionController::class, 'createForSkill'])->name('create');
                Route::post('/', [QuestionController::class, 'storeForSkill'])->name('store');
                Route::get('/{question}', [QuestionController::class, 'showForSkill'])->name('show');
                Route::get('/{question}/edit', [QuestionController::class, 'editForSkill'])->name('edit');
                Route::put('/{question}', [QuestionController::class, 'updateForSkill'])->name('update');
                Route::delete('/{question}', [QuestionController::class, 'destroyForSkill'])->name('destroy');

                Route::get('/generate/form', [QuestionController::class, 'showGenerationForm'])->name('generate.form');
                Route::post('/generate', [QuestionController::class, 'generate'])->name('generate');
                Route::post('/preview', [QuestionController::class, 'preview'])->name('preview');
                Route::post('/execute', [QuestionController::class, 'execute'])->name('execute');

                Route::post('/bulk-action', [QuestionController::class, 'bulkActionForSkill'])->name('bulk-action');
                Route::post('/bulk-import', [QuestionController::class, 'bulkImportForSkill'])->name('bulk-import');
                Route::post('/copy-from-skill', [QuestionController::class, 'copyFromSkillForSkill'])->name('copy-from-skill');
            });
        });
        // =====================================================
        // HINT & SOLUTIONS MANAGEMENT
        // =====================================================
        Route::resource('hints', HintController::class);
        Route::resource('solutions', SolutionController::class);

        // =====================================================
        // TRACKS MANAGEMENT
        // =====================================================
        Route::resource('tracks', TrackController::class);
        Route::get('/tracks/prerequisites', [TrackController::class, 'prerequisites'])->name('tracks.prerequisites');
        Route::prefix('tracks/{track}')->name('tracks.')->group(function () {
            Route::post('/duplicate', [TrackController::class, 'duplicate'])->name('copy');
            Route::prefix('skills/{skill}')->name('skills.')->group(function () {
                Route::post('/', [TrackController::class, 'addSkill'])->name('add');
                Route::delete('/', [TrackController::class, 'removeSkill'])->name('remove');
            });
        });

        // =====================================================
        // GLOBAL QUESTIONS MANAGEMENT
        // =====================================================
        Route::resource('questions', QuestionController::class);
        Route::prefix('questions')->name('questions.')->group(function () {
            Route::get('/search', [QuestionController::class, 'search'])->name('search');
            Route::get('/export', [QuestionController::class, 'export'])->name('export');
            Route::get('/bulk-import', [QuestionController::class, 'bulkImport'])->name('bulk-import');
            Route::get('/tracks', [QuestionController::class, 'getAvailableTracks'])->name('available-tracks');
            Route::get('/types', [QuestionController::class, 'getQuestionTypes'])->name('types');

            Route::post('/bulk-import', [QuestionController::class, 'processBulkImport'])->name('process-bulk-import');
            Route::post('/bulk-duplicate', [QuestionController::class, 'bulkDuplicate'])->name('bulk-duplicate');
            Route::post('/bulk-delete', [QuestionController::class, 'bulkDelete'])->name('bulk-delete');
            Route::post('/bulk-action', [QuestionController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/import', [QuestionController::class, 'import'])->name('import');

            Route::post('/generate', [QuestionController::class, 'generateQuestions'])->name('generateQuestions');

            Route::post('/bulk-review', [QuestionController::class, 'bulkReview'])->name('bulk-review');
        });

        // Individual question operations (global context)
        Route::prefix('questions/{question}')->name('questions.')->group(function () {
            Route::get('/preview', [QuestionController::class, 'preview'])->name('preview');
            Route::post('/duplicate', [QuestionController::class, 'duplicate'])->name('duplicate');
            Route::patch('/update-field', [QuestionController::class, 'updateField'])->name('update-field');

            Route::post('/image', [QuestionController::class, 'uploadQuestionImage'])->name('upload-image');
            Route::delete('/image', [QuestionController::class, 'deleteQuestionImage'])->name('delete-image');

            Route::prefix('answers/{option}')->name('answers.')->group(function () {
                Route::post('/image', [QuestionController::class, 'uploadAnswerImage'])->name('upload-image');
                Route::delete('/image', [QuestionController::class, 'deleteAnswerImage'])->name('delete-image');
            });
        });

        // =====================================================
        // USERS MANAGEMENT
        // =====================================================
        Route::resource('users', UserController::class);
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/export', [UserController::class, 'export'])->name('export');
            Route::get('/roles-houses', [UserController::class, 'getRolesAndHouses'])->name('roles-houses');
            Route::post('/import', [UserController::class, 'import'])->name('import');
        });

        Route::prefix('users/{user}')->name('users.')->group(function () {
            Route::get('/performance', [UserController::class, 'performance'])->name('performance');
            Route::post('/assign-role', [UserController::class, 'assignHouseRole'])->name('assign-role');
            Route::post('/reset', [UserController::class, 'reset'])->name('reset');
            Route::post('/diagnostic', [UserController::class, 'diagnostic'])->name('diagnostic');
            Route::post('/administrator', [UserController::class, 'administrator'])->name('administrator');
            Route::put('/fields/{field}', [UserController::class, 'updateField'])->name('fields.update');
            Route::delete('/house-roles/{houseRole}', [UserController::class, 'removeHouseRole'])->name('remove-role');
        });

    // =====================================================
        // CONFIGURATION TABLES
        // =====================================================   
        Route::get('configuration', [ConfigurationController::class, 'index'])->name('configuration.index');
  
        Route::resource('difficulties', DifficultyController::class);
        Route::resource('types', TypeController::class);
        Route::resource('levels', LevelController::class);
        Route::resource('statuses', StatusController::class);
        Route::resource('roles', RoleController::class);
        Route::resource('units', UnitController::class);

        // Add these for order updates
        Route::patch('difficulties/{difficulty}/order', [DifficultyController::class, 'updateOrder']);
        Route::patch('levels/{level}/order', [LevelController::class, 'updateOrder']);
        
        // API endpoints for refreshing dropdown data
        Route::get('/api/config/{table}', [ConfigurationController::class, 'getTableData'])->name('api.config.get');

    // =====================================================
        // SETTINGS
        // =====================================================
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/general',  [SettingsController::class, 'general'])->name('general');
            Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');

            Route::post('/test',    [SettingsController::class, 'testConfiguration'])->name('test');
            Route::post('/reset',   [SettingsController::class, 'resetToDefaults'])->name('reset');

            Route::post('/logo',             [SettingsController::class, 'updateLogo'])->name('logo.update');
            Route::delete('/logo',           [SettingsController::class, 'deleteLogo'])->name('logo.delete');

            Route::post('/favicon',          [SettingsController::class, 'updateFavicon'])->name('favicon.update');
            Route::delete('/favicon',        [SettingsController::class, 'deleteFavicon'])->name('favicon.delete');

            Route::post('/login_background', [SettingsController::class, 'updateLoginBackground'])->name('loginbg.update');
            Route::delete('/login_background',[SettingsController::class, 'deleteLoginBackground'])->name('loginbg.delete');
        });

        // =====================================================
        // ASSETS
        // =====================================================
        Route::prefix('assets')->name('assets.')->group(function () {
            Route::get('/',          [AssetController::class, 'index'])->name('index');
            Route::get('/list',      [AssetController::class, 'listAssets'])->name('list');
            Route::post('/upload',   [AssetController::class, 'upload'])->name('upload');
            Route::post('/folder',   [AssetController::class, 'createFolder'])->name('folder');
            Route::get('/file/{id}', [AssetController::class, 'getFileInfo'])->name('info');
            Route::delete('/{id}',   [AssetController::class, 'delete'])->name('delete');
            Route::post('/move',     [AssetController::class, 'move'])->name('move');
            Route::get('/videos',    [AssetController::class, 'getVideos'])->name('admin.assets.videos');
        });

        // =====================================================
        // REPORTS
        // =====================================================
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/usage',       [ReportController::class, 'usage'])->name('usage');
            Route::get('/performance', [ReportController::class, 'performance'])->name('performance');
        });

        // Global export
        Route::get('/export', [AdminController::class, 'export'])->name('export');
    });
});

/*
|--------------------------------------------------------------------------
| Utility
|--------------------------------------------------------------------------
*/
Route::get('/setup-storage', function () {
    $directories = ['assets', 'questions', 'answers', 'videos', 'uploads', 'logos', 'favicons', 'backgrounds'];

    foreach ($directories as $dir) {
        $path = storage_path('app/public/' . $dir);
        if (!file_exists($path)) {
            @mkdir($path, 0755, true);
        }
    }

    if (!file_exists(public_path('storage'))) {
        app('files')->link(
            storage_path('app/public'),
            public_path('storage')
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Storage directories created and linked successfully!',
        'directories' => $directories,
    ]);
})->name('setup.storage');
