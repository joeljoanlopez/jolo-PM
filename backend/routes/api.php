<?php

use App\Infrastructure\Http\Controllers\ProjectController;
use App\Infrastructure\Http\Controllers\RepositoryController;
use Illuminate\Support\Facades\Route;

// Todas cuelgan de /api (lo pone bootstrap/app.php).

Route::get('/health', fn () => ['status' => 'ok']);

Route::get('/repositories', [RepositoryController::class, 'index']);

Route::get('/projects', [ProjectController::class, 'index']);
Route::post('/projects', [ProjectController::class, 'store']);
Route::patch('/projects/{id}', [ProjectController::class, 'update'])->whereNumber('id');
Route::delete('/projects/{id}', [ProjectController::class, 'destroy'])->whereNumber('id');
Route::post('/projects/{id}/sync', [ProjectController::class, 'sync'])->whereNumber('id');
Route::get('/projects/{id}/board', [ProjectController::class, 'board'])->whereNumber('id');
