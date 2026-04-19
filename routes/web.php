<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectUserByRole;

use App\Http\Controllers\Delegate\DashboardController;
use App\Http\Controllers\Delegate\VoterController;

use App\Http\Controllers\Operations\WarRoomController;
use App\Http\Controllers\Operations\SeatProjectionController;
use App\Http\Controllers\Operations\MapController;
use App\Http\Controllers\Operations\CommandCenterController;
use App\Http\Controllers\Operations\AlertController;
use App\Http\Controllers\Operations\MobilizationController;
use App\Http\Controllers\Operations\LiveDataController;
use App\Http\Controllers\Operations\VoterDetailsController;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Supervisor\SupervisorDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Operations\FieldTaskController;
use App\Http\Controllers\Field\TaskInboxController;
use App\Http\Controllers\Operations\DataPreparationController;
use App\Http\Controllers\Field\TargetController;
use App\Http\Controllers\Admin\VoterImportController;
use App\Http\Controllers\Operations\DataValidationController;
use App\Http\Controllers\Admin\VoterNoteController;
use App\Http\Controllers\Admin\VoterRelationshipController;
use App\Http\Controllers\Admin\UserHierarchyController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {

    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
});

/*
|--------------------------------------------------------------------------
| Dashboard Redirect
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', RedirectUserByRole::class])
    ->get('/dashboard', function () {
        return view('dashboard');
    })
    ->name('dashboard');
Route::get('/profile', function () {
        return 'Profile Page';
    })->name('profile.edit');
/*
|--------------------------------------------------------------------------
| Delegate Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:delegate'])
    ->prefix('delegate')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('delegate.dashboard');

        Route::get('/voters', [VoterController::class, 'index'])
            ->name('delegate.voters');

        Route::post('/voters/{voter}/mark', [VoterController::class, 'markVoted'])
            ->middleware('throttle:60,1')
            ->name('delegate.voters.mark');

        Route::get('/voters/search', [VoterController::class, 'search'])
            ->name('delegate.voters.search');

        Route::get('/priority', [VoterController::class, 'priority'])
            ->name('delegate.priority');
    });



    Route::post('/voters/{voter}/contacted', function ($voterId) {

        // لاحقاً: logging

        return response()->json(['success'=>true]);
    });

    Route::middleware(['auth', 'role:admin|supervisor|delegate'])
        ->prefix('field')
        ->group(function () {

            Route::get('/tasks', [TaskInboxController::class, 'index'])
                ->name('field.tasks.inbox');

            Route::get('/targets', [TargetController::class, 'index'])
                ->name('field.targets'); // ✅ موحد

            Route::post('/voters/{voter}/contacted', [TargetController::class, 'markContacted'])
                ->name('field.voters.contacted');

            Route::get('/election-mode', [TargetController::class, 'electionMode'])
                ->name('field.election-mode');

            Route::get('/election-mode/live', [TargetController::class, 'electionModeLive'])
                ->name('field.election-mode.live');

            Route::get('/election-mode/search', [TargetController::class, 'electionModeSearch'])
                ->name('field.election-mode.search');
        });

/*
|--------------------------------------------------------------------------
| Supervisor Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:supervisor'])
    ->prefix('supervisor')
    ->group(function () {

        Route::get('/dashboard', [SupervisorDashboardController::class, 'index'])
            ->name('supervisor.dashboard');

        // NEW
        Route::get('/delegates/{delegate}/voters', [SupervisorDashboardController::class, 'delegateVoters'])
            ->name('supervisor.delegate.voters');

        Route::post('/voters/{voter}/mark', [SupervisorDashboardController::class, 'markVoted'])
            ->name('supervisor.voters.mark');

        Route::get('/voters', [SupervisorDashboardController::class, 'voters'])
            ->name('supervisor.voters');
    });

/*
|--------------------------------------------------------------------------
| Operations Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin|operations'])
    ->prefix('operations')
    ->group(function () {

        Route::get('/dashboard', [WarRoomController::class, 'dashboard'])
            ->name('operations.dashboard');

        Route::get('/command-center', [CommandCenterController::class, 'index'])
            ->name('operations.command-center');

        Route::get('/map', [MapController::class, 'index'])
            ->name('operations.map');

        Route::get('/mobilization', [MobilizationController::class, 'index'])
            ->name('operations.mobilization');

        Route::get('/alerts', [AlertController::class, 'index'])
            ->name('operations.alerts.index');

        Route::get('/supporters-missing', [WarRoomController::class, 'supportersMissing'])
            ->name('operations.supporters.missing');

        Route::get('/seat-projection', [SeatProjectionController::class, 'index'])
            ->name('operations.seat-projection.index');

        Route::post('/seat-projection', [SeatProjectionController::class, 'updateVotes'])
            ->name('operations.seat-projection.update');

        Route::get('/live/command-center', [LiveDataController::class, 'commandCenter'])
            ->name('operations.live.command-center');

        Route::get('/tasks', [FieldTaskController::class, 'index'])
            ->name('operations.tasks.index');

        Route::get('/tasks/create', [FieldTaskController::class, 'create'])
            ->name('operations.tasks.create');

        Route::post('/tasks', [FieldTaskController::class, 'store'])
            ->name('operations.tasks.store');

        Route::post('/tasks/{task}/done', [FieldTaskController::class, 'markDone'])
            ->name('operations.tasks.done');

        Route::post('/tasks/{task}/progress', [FieldTaskController::class, 'markInProgress'])
            ->name('operations.tasks.progress');

        Route::get('/data-validation', [DataValidationController::class, 'index'])
            ->name('operations.data-validation');

        Route::post('/admin/voters/{voter}/notes', [VoterNoteController::class, 'store'])
            ->name('voters.notes.store');

        Route::delete('/admin/voter-notes/{voterNote}', [VoterNoteController::class, 'destroy'])
            ->name('voters.notes.destroy');

        Route::post('/admin/voters/{voter}/relationships', [VoterRelationshipController::class, 'store'])
            ->name('voters.relationships.store');

        Route::put('/admin/voter-notes/{voterNote}', [VoterNoteController::class, 'update'])
            ->name('voters.notes.update');

        Route::delete('/admin/voter-notes/{voterNote}', [VoterNoteController::class, 'destroy'])
            ->name('voters.notes.destroy');

        Route::put('/admin/voter-relationships/{voterRelationship}', [VoterRelationshipController::class, 'update'])
            ->name('voters.relationships.update');

        Route::delete('/admin/voter-relationships/{voterRelationship}', [VoterRelationshipController::class, 'destroy'])
            ->name('voters.relationships.destroy');

        Route::get('/voters/search-simple', [DataPreparationController::class, 'searchSimple'])
            ->name('voters.search.simple');

        Route::get('/voters/{voter}', [VoterDetailsController::class, 'show'])
            ->name('operations.voters.show');

        Route::get('/voters/{voter}/notes', [DataPreparationController::class, 'notes'])
            ->name('operations.voters.notes');


    });


Route::middleware(['auth', 'role:admin|operations|data_operator'])
    ->prefix('operations')
    ->group(function () {

        Route::get('/data-preparation', [DataPreparationController::class, 'index'])
            ->name('operations.data-preparation');

        Route::get('/data-preparation/search', [DataPreparationController::class, 'search'])
            ->name('operations.data-preparation.search');

        Route::post('/data-preparation/bulk-assign', [DataPreparationController::class, 'bulkAssign'])
            ->name('operations.data-preparation.bulk-assign');

        Route::post('/data-preparation/bulk-status', [DataPreparationController::class, 'bulkStatus'])
            ->name('operations.data-preparation.bulk-status');

        Route::post('/data-preparation/{voter}', [DataPreparationController::class, 'update'])
            ->name('operations.data-preparation.update');
    });
/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/


Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/user-families/{user}', function ($userId) {
            $user = \App\Models\User::findOrFail($userId);

            return response()->json($user->families_list ?? []);
        });

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('users', UserController::class);

        Route::get('/voters/import', [VoterImportController::class, 'index'])
            ->name('voters.import');

        Route::post('/voters/import', [VoterImportController::class, 'import'])
            ->name('voters.import.submit');

        Route::post('/voters/import/preview', [VoterImportController::class, 'preview'])
            ->name('voters.import.preview');

        Route::post('/voters/import/{run}/confirm', [VoterImportController::class, 'confirm'])
            ->name('voters.import.confirm');

        Route::get('/voters/import/{run}/errors', [VoterImportController::class, 'errors'])
            ->name('voters.import.errors');

        Route::post('/voters/import/status-preview', [VoterImportController::class, 'statusPreview'])
            ->name('voters.import.status.preview');

        Route::post('/voters/import/{run}/status-confirm', [VoterImportController::class, 'confirmStatusUpdate'])
            ->name('voters.import.status.confirm');

        Route::get('/user-hierarchy', [UserHierarchyController::class, 'index'])
            ->name('user-hierarchy.index');

        Route::post('/user-hierarchy/assign', [UserHierarchyController::class, 'assign'])
            ->name('user-hierarchy.assign');

        Route::post('/user-hierarchy/move', [UserHierarchyController::class, 'move'])
            ->name('user-hierarchy.move');

        Route::get('/user-families', [UserController::class, 'families'])
            ->name('user-families');

        Route::post('/user-families', [UserController::class, 'assignFamilies'])
            ->name('user-families.assign');

        Route::get('/operations/data-preparation/print-pdf', [DataPreparationController::class, 'printPdf'])
        ->name('data-preparation.print-pdf');
    });

require __DIR__.'/auth.php';
