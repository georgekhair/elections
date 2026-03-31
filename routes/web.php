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
/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
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
        Route::get('/field/targets', [TargetController::class, 'index'])
            ->name('field.targets');
    });

    Route::middleware(['auth', 'role:supervisor|delegate'])
        ->prefix('field')
        ->group(function () {

            Route::get('/tasks', [TaskInboxController::class, 'index'])
                ->name('field.tasks.inbox');

        });

    Route::get('/targets', [\App\Http\Controllers\Field\TargetController::class, 'index'])
        ->name('field.targets');

    Route::post('/voters/{voter}/vote', function ($voterId) {

        $voter = \App\Models\Voter::findOrFail($voterId);

        $voter->update([
            'is_voted' => true,
            'voted_at' => now(),
            'voted_by' => auth()->id()
        ]);

        return response()->json(['success'=>true]);
    });

    Route::post('/voters/{voter}/contacted', function ($voterId) {

        // لاحقاً: logging

        return response()->json(['success'=>true]);
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

        Route::get('/data-preparation', [DataPreparationController::class, 'index'])
            ->name('operations.data-preparation');

        Route::get('/data-validation', [DataValidationController::class, 'index'])
            ->name('operations.data-validation');

        Route::get('/data-preparation/search', [DataPreparationController::class, 'search'])
            ->name('operations.data-preparation.search');

        Route::post('/data-preparation/bulk-assign', [DataPreparationController::class, 'bulkAssign'])
            ->name('operations.data-preparation.bulk-assign');

        Route::post('/data-preparation/bulk-status', [DataPreparationController::class, 'bulkStatus'])
            ->name('operations.data-preparation.bulk-status');

        Route::post('/data-preparation/{voter}', [DataPreparationController::class, 'update'])
            ->name('operations.data-preparation.update');

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
    });

require __DIR__.'/auth.php';
