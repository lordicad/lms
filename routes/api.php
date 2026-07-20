<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Student\FavouriteController;
use App\Http\Controllers\Api\Student\LearnController;
use App\Http\Controllers\Api\Student\LessonController;
use App\Http\Controllers\Api\Student\QuizController;
use App\Http\Controllers\Api\Student\RankingController;
use App\Http\Controllers\Api\Teacher\ChapterController as TeacherChapterController;
use App\Http\Controllers\Api\Teacher\ContentController as TeacherContentController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
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

    Route::get('favourites', [FavouriteController::class, 'index']);
    Route::post('lessons/{lesson}/favourite', [FavouriteController::class, 'toggle']);

    // Interactive quiz flow: list -> intro -> start attempt -> submit -> result.
    Route::get('quizzes', [QuizController::class, 'list']);
    Route::get('quizzes/{quiz}', [QuizController::class, 'intro']);
    Route::post('quizzes/{quiz}/start', [QuizController::class, 'start']);
    Route::post('attempts/{attempt}/submit', [QuizController::class, 'submit']);
    Route::get('attempts/{attempt}/result', [QuizController::class, 'result']);

    Route::get('ranking', RankingController::class);
});

/*
| Teacher surface. Token-protected; the controllers enforce the teacher role.
*/
Route::middleware('auth:sanctum')->prefix('teacher')->group(function () {
    Route::get('dashboard', TeacherDashboardController::class);

    Route::get('content/videos', [TeacherContentController::class, 'videos']);
    Route::get('content/materials', [TeacherContentController::class, 'materials']);
    Route::get('content/quizzes', [TeacherContentController::class, 'quizzes']);

    Route::post('content/videos/{lesson}/publish', [TeacherContentController::class, 'toggleVideo']);
    Route::post('content/quizzes/{quiz}/publish', [TeacherContentController::class, 'toggleQuiz']);

    Route::delete('content/videos/{lesson}', [TeacherContentController::class, 'deleteVideo']);
    Route::delete('content/materials/{material}', [TeacherContentController::class, 'deleteMaterial']);
    Route::delete('content/quizzes/{quiz}', [TeacherContentController::class, 'deleteQuiz']);

    // Bab management + the Subject/Tahun options for teacher pickers.
    Route::get('options', [TeacherChapterController::class, 'options']);
    Route::get('chapters', [TeacherChapterController::class, 'index']);
    Route::post('chapters', [TeacherChapterController::class, 'store']);
    Route::put('chapters/{chapter}', [TeacherChapterController::class, 'update']);
    Route::delete('chapters/{chapter}', [TeacherChapterController::class, 'destroy']);
});
