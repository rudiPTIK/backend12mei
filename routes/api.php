<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\AgoraChatController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ConsultationSummaryController;
use App\Http\Controllers\RiasecController;
use App\Http\Controllers\ReportShareController;
use App\Http\Controllers\AggregateReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
Route::get('/test', fn () => response()->json(['message' => 'API OK!'], 200));

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| RIASEC (public + protected)
|--------------------------------------------------------------------------
*/
Route::prefix('riasec')->group(function () {
    // Public
    Route::get('questions', [RiasecController::class, 'questions']);
    Route::get('job-zones', [RiasecController::class, 'jobZones']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('results',          [RiasecController::class, 'results']);
        Route::get('careers',          [RiasecController::class, 'careers']);
        Route::get('history',          [RiasecController::class, 'history']);
        Route::get('tests/{test}',     [RiasecController::class, 'show']);
        Route::delete('tests/{test}',  [RiasecController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Semua user login (siswa & guru)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // RTC video token (controller memverifikasi peserta jadwal)
    Route::get('/jadwal/{id}/rtc-token', [JadwalController::class, 'generateRtcToken']);

    // Token Chat Agora (opsional)
    Route::get('/agora/chat-token/{channel}', [AgoraChatController::class, 'chatToken']);

    // Baca laporan 1 konsultasi — controller cek kepemilikan + visibility
    Route::get('/consultations/{id}/report', [ConsultationController::class, 'report']);

    // 🔑 Penting untuk client (siswa & guru) agar bisa menemukan CID dari jadwal
    // (controller memverifikasi bahwa user adalah siswa/konselor pada jadwal tsb)
    Route::get('/consultations/by-jadwal/{jadwal}', [ConsultationController::class, 'findByJadwal']);

    // Debug (hapus di production)
    Route::get('/debug-agora', function () {
        return response()->json([
            'app_id'      => config('services.agora.app_id'),
            'cert_length' => strlen((string) config('services.agora.app_certificate')),
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| GURU BK
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'checkrole:gurubk'])->group(function () {
    // Jadwal
    Route::post('/jadwal',            [JadwalController::class, 'store']);
    Route::get('/jadwal',             [JadwalController::class, 'index']);
    Route::get('/jadwal/dipesan',     [JadwalController::class, 'getJadwalDipesan']);
    Route::get('/jadwal/{id}/siswa',  [JadwalController::class, 'daftarsiswa']);
    Route::patch('/jadwal/{id}',      [JadwalController::class, 'update']);
    Route::delete('/jadwal/{id}',     [JadwalController::class, 'destroy']);

    // Konsultasi + Laporan
    Route::get( '/consultations',               [ConsultationController::class, 'index']);
    Route::post('/consultations',               [ConsultationController::class, 'store']);
    Route::post('/consultations/{id}/start',    [ConsultationController::class, 'start']);
    Route::post('/consultations/{id}/end',      [ConsultationController::class, 'end']);
    Route::post('/consultations/{id}/report',   [ConsultationController::class, 'storeReport']); // buat/edit isi laporan
    Route::patch('/consultations/{id}/report/visibility', [ConsultationController::class, 'setVisibility']); // sembunyikan/tampilkan utk siswa

    Route::get('/reports',                      [ConsultationController::class, 'listReports']);

    // Ringkasan (opsional)
       


});

/*
|--------------------------------------------------------------------------
| SISWA
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'checkrole:siswa'])->group(function () {
    Route::get( '/jadwal/tersedia',    [JadwalController::class, 'getJadwal']);
    Route::get( '/jadwal-saya',        [JadwalController::class, 'jadwalSaya']);
    Route::post('/jadwal/pilih/{id}',  [JadwalController::class, 'pilihJadwal']);
    Route::post('/jadwal/batal/{id}',  [JadwalController::class, 'batalJadwal']);

    Route::get( '/my-reports',         [ConsultationController::class, 'myReportsForStudent']);

    // Siswa acknowledge "Saya paham" laporan (pastikan method di controller bernama acknowledge)
    Route::post('/consultations/{id}/report/ack', [ConsultationController::class, 'acknowledge']);
});
Route::middleware('auth:sanctum')->group(function () {
    // RTC & Chat (tetap)
    Route::get('/jadwal/{id}/rtc-token', [JadwalController::class, 'generateRtcToken']);
    Route::get('/agora/chat-token/{channel}', [AgoraChatController::class, 'chatToken']);

    // Konsultasi read
    Route::get('/consultations', [ConsultationController::class, 'index']);
    Route::get('/consultations/by-jadwal/{jadwal}', [ConsultationController::class, 'findByJadwal']);
    Route::get('/consultations/{id}/report', [ConsultationController::class, 'report']);

    // Inbox share untuk Waka/Kepsek
    ;
});


Route::prefix('aggregate-reports')->middleware('auth:sanctum')->group(function () {
    // ✅ Bisa diakses semua user login. Controller yg cek owner/recipient.
    Route::get('/{report}', [AggregateReportController::class, 'show'])->whereNumber('report');

    // ✅ INBOX (tanpa checkrole, aman karena filter recipient_id di controller)
    Route::get('/inbox',               [AggregateReportController::class, 'inbox']);
    Route::get('/inbox/{share}',       [AggregateReportController::class, 'showShare'])->whereNumber('share');
    Route::patch('/inbox/{share}/ack', [AggregateReportController::class, 'ackShare'])->whereNumber('share');

    // 👩‍🏫 Khusus Guru BK saja
    Route::middleware('checkrole:gurubk')->group(function () {
        Route::post('/compose', [AggregateReportController::class, 'compose']);
        Route::get('/mine',     [AggregateReportController::class, 'mine']);
        Route::match(['PATCH','POST'], '/{report}/send', [AggregateReportController::class, 'send'])
            ->whereNumber('report');
        Route::delete('/{report}', [AggregateReportController::class, 'destroy'])
            ->whereNumber('report');
    });
});

Route::get('/whoami', fn() => auth()->user())
  ->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->group(function () {
    // whoami (sudah dipakai di app kamu)
    Route::get('/whoami', fn () => auth()->user());

    // Profil
    Route::get( '/profile',              [ProfileController::class, 'me']);
    Route::patch('/profile',             [ProfileController::class, 'update']);
    Route::patch('/profile/password',    [ProfileController::class, 'changePassword']);
    Route::post( '/profile/avatar',      [ProfileController::class, 'uploadAvatar']);
});

Route::prefix('admin')->middleware(['auth:sanctum','checkrole:admin'])->group(function () {
    // USERS CRUD
    Route::get(   '/users',            [UserController::class, 'index']);
    Route::post(  '/users',            [UserController::class, 'store']);
    Route::get(   '/users/{id}',       [UserController::class, 'show'])->whereNumber('id');
    Route::patch( '/users/{id}',       [UserController::class, 'update'])->whereNumber('id');
    Route::delete('/users/{id}',       [UserController::class, 'destroy'])->whereNumber('id');
    Route::post(  '/users/{id}/restore',[UserController::class, 'restore'])->whereNumber('id'); // opsional
});