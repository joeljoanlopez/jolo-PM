<?php

use App\Infrastructure\Http\Controllers\AuthController;
use App\Infrastructure\Http\Controllers\ProjectController;
use App\Infrastructure\Http\Controllers\RepositoryController;
use App\Infrastructure\Http\Middleware\RequireAuthentication;
use Illuminate\Support\Facades\Route;

// Todas cuelgan de /api (lo pone bootstrap/app.php).

// Públicas: la comprobación de salud y el propio login.
Route::get('/health', fn () => ['status' => 'ok']);

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::get('/me', 'me');

    Route::get('/setup', 'setupStatus');
    Route::post('/setup/code', 'setupCode');
    Route::post('/setup/password', 'setupPassword');
    Route::post('/setup/two-factor', 'setupTwoFactor');

    Route::post('/login', 'login');
    Route::post('/two-factor', 'twoFactor');
    Route::post('/logout', 'logout');

    Route::post('/recovery-codes', 'regenerateRecoveryCodes')->middleware(RequireAuthentication::class);
});

// Todo lo demás exige sesión iniciada (contraseña y segundo factor).
Route::middleware(RequireAuthentication::class)->group(function () {
    Route::get('/repositories', [RepositoryController::class, 'index']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::patch('/projects/{id}', [ProjectController::class, 'update'])->whereNumber('id');
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])->whereNumber('id');
    Route::post('/projects/{id}/sync', [ProjectController::class, 'sync'])->whereNumber('id');
    Route::get('/projects/{id}/board', [ProjectController::class, 'board'])->whereNumber('id');
});
