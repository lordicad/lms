<?php

use App\Http\Controllers\Cikgu\ChapterController;
use App\Http\Controllers\Cikgu\DashboardController as CikguDashboardController;
use App\Http\Controllers\Cikgu\LessonController;
use App\Http\Controllers\Cikgu\MaterialController;
use App\Http\Controllers\Cikgu\QuizBuilderController;
use App\Http\Controllers\Cikgu\QuizController;
use App\Http\Controllers\Cikgu\QuizStatsController;
use App\Http\Controllers\Cikgu\TalentController;
use App\Http\Controllers\Cikgu\TeacherRankingController;
use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminStudentController;
use App\Http\Controllers\Admin\AdminTalentController;
use App\Http\Controllers\YoutubeConnectController;
use App\Http\Controllers\ChapterBrowseController;
use App\Http\Controllers\ContinueController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OfflineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuizAttemptController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentQuizController;
use App\Http\Controllers\SubjectBrowseController;
use App\Http\Controllers\SubjectIndexController;
use App\Http\Controllers\TahunController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Awam
|--------------------------------------------------------------------------
*/

Route::get('/', LandingController::class)->name('landing');

// Tukar bahasa antara muka (BM / EN). Terbuka kepada tetamu dan pengguna berdaftar.
Route::get('/bahasa/{locale}', LocaleController::class)->name('locale.switch');

// Tukar tema (terang / gelap). Terbuka kepada tetamu dan pengguna berdaftar.
Route::get('/tema/{theme}', ThemeController::class)->name('theme.switch');

/*
|--------------------------------------------------------------------------
| Kandungan (guru dan murid boleh melayari)
|--------------------------------------------------------------------------
| Teachers browse the same library students do, so these are auth-only. The
| student-specific actions below carry role:student.
*/

Route::middleware('auth')->group(function () {
    Route::get('/belajar/{subject:slug}/{grade}', SubjectBrowseController::class)->name('belajar.subjek');
    Route::get('/bab/{chapter}', ChapterBrowseController::class)->name('bab.show');

    Route::get('/video/{lesson}', [WatchController::class, 'show'])->name('video.show');
    Route::post('/video/{lesson}/tonton', [WatchController::class, 'markViewed'])->name('video.tonton');

    // Quiz intro. Students start an attempt from here; teachers see a preview banner.
    Route::get('/kuiz/{quiz}', [QuizAttemptController::class, 'intro'])->name('kuiz.intro');

    Route::get('/muat-turun/bahan/{material}', [DownloadController::class, 'material'])->name('muat-turun.bahan');
    Route::get('/muat-turun/kuiz/{quiz}', [DownloadController::class, 'quiz'])->name('muat-turun.kuiz');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/kata-laluan', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Murid
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/belajar', StudentDashboardController::class)->name('belajar.index');

    // Streaming-style sections (sidebar).
    Route::get('/belajar/subjek', SubjectIndexController::class)->name('subjek.index');
    Route::get('/sambung-menonton', ContinueController::class)->name('sambung.index');
    Route::get('/kegemaran', [FavouriteController::class, 'index'])->name('kegemaran.index');
    Route::get('/simpanan', [OfflineController::class, 'index'])->name('simpanan.index');
    Route::get('/kuiz-saya', StudentQuizController::class)->name('kuiz-saya.index');
    Route::get('/cari', SearchController::class)->name('cari.index');

    // Playback progress (resume + Continue Watching). Throttled: the player pings often.
    Route::post('/kemajuan/{lesson}', [ProgressController::class, 'store'])
        ->middleware('throttle:60,1')->name('kemajuan.simpan');

    // Favourites (heart toggle, ajax).
    Route::post('/kegemaran/{lesson}', [FavouriteController::class, 'store'])->name('kegemaran.simpan');
    Route::delete('/kegemaran/{lesson}', [FavouriteController::class, 'destroy'])->name('kegemaran.padam');

    // Offline: download an uploaded lesson's mp4 (YouTube lessons are rejected in the controller).
    Route::get('/muat-turun/video/{lesson}', [OfflineController::class, 'download'])->name('muat-turun.video');

    // Switch the Tahun being browsed (revision/preview), persisted in the session.
    Route::get('/tahun/{level}', TahunController::class)->whereNumber('level')->name('tahun.tukar');

    Route::post('/kuiz/{quiz}/mula', [QuizAttemptController::class, 'start'])->name('kuiz.mula');
    Route::get('/kuiz/percubaan/{attempt}', [QuizAttemptController::class, 'take'])->name('kuiz.percubaan');
    Route::post('/kuiz/percubaan/{attempt}', [QuizAttemptController::class, 'submit'])->name('kuiz.hantar');
    Route::get('/keputusan/{attempt}', [QuizAttemptController::class, 'result'])->name('keputusan.show');

    Route::get('/ranking', RankingController::class)->name('ranking.index');
});

/*
|--------------------------------------------------------------------------
| Cikgu
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:teacher'])
    ->prefix('cikgu')
    ->name('cikgu.')
    ->group(function () {
        Route::get('/', CikguDashboardController::class)->name('dashboard');

        Route::post('video/{lesson}/terbit', [LessonController::class, 'togglePublish'])->name('video.terbit');
        Route::resource('video', LessonController::class)
            ->parameters(['video' => 'lesson'])
            ->except(['show']);

        Route::resource('bahan', MaterialController::class)
            ->parameters(['bahan' => 'material'])
            ->except(['show']);

        // Step 1 of quiz creation is picking the mode (file or interactive).
        Route::get('kuiz/mod', [QuizController::class, 'mode'])->name('kuiz.mod');
        Route::resource('kuiz', QuizController::class)->parameters(['kuiz' => 'quiz']);

        Route::get('kuiz/{quiz}/soalan', [QuizBuilderController::class, 'edit'])->name('kuiz.soalan');
        Route::put('kuiz/{quiz}/soalan', [QuizBuilderController::class, 'update'])->name('kuiz.soalan.simpan');
        Route::get('kuiz/{quiz}/statistik', QuizStatsController::class)->name('kuiz.statistik');

        // Adding a Bab happens inline on the index page, so there is no separate create screen.
        Route::resource('bab', ChapterController::class)
            ->parameters(['bab' => 'chapter'])
            ->only(['index', 'store', 'edit', 'update', 'destroy']);

        Route::get('ranking', TeacherRankingController::class)->name('ranking');

        // The teacher's own talent signal (four sub-scores + headline + per-lesson breakdown).
        Route::get('bakat', TalentController::class)->name('bakat');
    });

/*
|--------------------------------------------------------------------------
| Pengesahan pemilikan YouTube (OAuth) — guru sahaja
|--------------------------------------------------------------------------
| The redirect URI registered in Google Cloud is /oauth/youtube/callback, so these live at the
| document root rather than under /cikgu. Read-only youtube.readonly scope; no tokens stored.
*/

Route::middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/oauth/youtube/redirect', [YoutubeConnectController::class, 'redirect'])->name('oauth.youtube.redirect');
    Route::get('/oauth/youtube/callback', [YoutubeConnectController::class, 'callback'])->name('oauth.youtube.callback');
    Route::delete('/oauth/youtube/{channel}', [YoutubeConnectController::class, 'disconnect'])->name('oauth.youtube.disconnect');
});

/*
|--------------------------------------------------------------------------
| Admin (MOE) — pengawasan sahaja, dicipta melalui CLI
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Utama — the read-only platform overview.
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        // Pengguna — CRUD for teacher and student accounts.
        Route::get('pengguna', [AdminUserController::class, 'index'])->name('pengguna');
        Route::get('pengguna/baru', [AdminUserController::class, 'create'])->name('pengguna.create');
        Route::post('pengguna', [AdminUserController::class, 'store'])->name('pengguna.store');
        Route::get('pengguna/{user}/sunting', [AdminUserController::class, 'edit'])->name('pengguna.edit');
        Route::put('pengguna/{user}', [AdminUserController::class, 'update'])->name('pengguna.update');
        Route::delete('pengguna/{user}', [AdminUserController::class, 'destroy'])->name('pengguna.destroy');
        Route::post('pengguna/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('pengguna.status');

        Route::view('tetapan', 'admin.tetapan')->name('tetapan');

        // Admin's own profile, in the admin shell (the shared profile.* endpoints do the saving).
        Route::view('profil', 'admin.profil')->name('profil');

        Route::get('bakat', [AdminTalentController::class, 'index'])->name('bakat');
        // 'eksport' must precede the {teacher} wildcard so it is not swallowed by model binding.
        Route::get('bakat/eksport', [AdminTalentController::class, 'export'])->name('bakat.export');
        Route::get('bakat/{teacher}', [AdminTalentController::class, 'show'])->name('bakat.show');

        Route::get('murid', [AdminStudentController::class, 'index'])->name('murid');

        // Deactivate/reactivate a teacher's sign-in. POST, not GET: it changes state.
        Route::post('guru/{teacher}/status', [AdminTalentController::class, 'toggleStatus'])->name('guru.status');

        // Kandungan: oversight of every teacher's library. Read-only.
        Route::get('kandungan/video', [AdminContentController::class, 'video'])->name('kandungan.video');
        Route::get('kandungan/bahan', [AdminContentController::class, 'material'])->name('kandungan.bahan');
        Route::get('kandungan/kuiz', [AdminContentController::class, 'quiz'])->name('kandungan.kuiz');
    });

/*
|--------------------------------------------------------------------------
| JSON untuk dropdown bersandar (Subjek -> Tahun -> Bab)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->get('/api/bab', [ChapterController::class, 'lookup'])->name('api.bab');
Route::middleware('auth')->get('/api/bab-video', [LessonController::class, 'lookup'])->name('api.bab.video');

require __DIR__.'/auth.php';
