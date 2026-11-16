<?php

use App\Infrastructure\Http\Controllers\AuthController;
use App\Infrastructure\Http\Controllers\ProjectController;
use App\Infrastructure\Http\Controllers\RepositoryController;
use App\Infrastructure\Http\Controllers\SetupController;
use App\Infrastructure\Http\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;

// Todas cuelgan de /api (lo pone bootstrap/app.php).

Route::get('/health', fn () => ['status' => 'ok']);

// Sin sesión: configuración inicial y login (E10).
Route::prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/two-factor', [AuthController::class, 'twoFactor']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/recovery-codes', [AuthController::class, 'regenerateRecoveryCodes'])
        ->middleware(Authenticate::class);

    Route::get('/setup', [SetupController::class, 'show']);
    Route::post('/setup/code', [SetupController::class, 'code']);
    Route::post('/setup/password', [SetupController::class, 'password']);
    Route::post('/setup/complete', [SetupController::class, 'complete']);
});

// Todo lo demás exige sesión autenticada: 401 si no.
Route::middleware(Authenticate::class)->group(function () {
    Route::get('/repositories', [RepositoryController::class, 'index']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::patch('/projects/{id}', [ProjectController::class, 'update'])->whereNumber('id');
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])->whereNumber('id');
    Route::post('/projects/{id}/sync', [ProjectController::class, 'sync'])->whereNumber('id');
    Route::get('/projects/{id}/board', [ProjectController::class, 'board'])->whereNumber('id');
});
