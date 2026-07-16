<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Student\LearnController;
use App\Http\Controllers\Api\Student\LessonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Consumed by the Flutter mobile app. Authentication is token-based
| (Laravel Sanctum) rather than the cookie sessions used by the web app.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

/*
| Student content surface (browse library, watch videos, save progress).
| All token-protected; the "active grade" is an optional ?grade=<level> query param.
*/
Route::middleware('auth:sanctum')->prefix('student')->group(function () {
    Route::get('dashboard', [LearnController::class, 'dashboard']);
    Route::get('subjects', [LearnController::class, 'subjects']);
    Route::get('subjects/{subject:slug}/chapters', [LearnController::class, 'subjectChapters']);
    Route::get('chapters/{chapter}', [LearnController::class, 'chapter']);

    Route::get('lessons/{lesson}', [LessonController::class, 'show']);
    Route::post('lessons/{lesson}/viewed', [LessonController::class, 'markViewed']);
    Route::post('lessons/{lesson}/progress', [LessonController::class, 'saveProgress']);
});
