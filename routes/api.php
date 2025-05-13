<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\AgoraChatController;
use App\Http\Controllers\RiasecController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//autentikasi (tanpa middleware, bisa diakses semua orang)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']) ->middleware('auth:sanctum');

//route membutuhkan autentikasi
Route::middleware(['auth:sanctum', 'checkrole:gurubk'])->group(function () {
  Route::post('/jadwal', [JadwalController::class, 'store']); // tambah jadwal
  Route::get('/jadwal/dipesan', [JadwalController::class, 'getJadwalDipesan']);
  Route::get('/jadwal', [JadwalController::class, 'index']);
  Route::delete('/jadwal/{id}', [JadwalController::class, 'destroy']);
  Route::patch('/jadwal/{id}', [JadwalController::class, 'update']);
  Route::get('/riasec-results', [RiasecController::class, 'guruResults']);

});


Route::middleware(['auth:sanctum', 'checkrole:siswa'])->group(function () {
  // Melihat semua jadwal yang tersedia
  Route::get('/jadwal/tersedia', [JadwalController::class, 'getJadwal']);
  Route::get('/jadwal-saya', [JadwalController::class, 'jadwalSaya']);
  // Siswa memilih jadwal tertentu (pakai ID)
  Route::post('/jadwal/pilih/{id}', [JadwalController::class, 'pilihJadwal']);
  Route::post('/jadwal/batal/{id}', [JadwalController::class, 'batalJadwal']);


  // Melihat jadwal yang sudah dipilih oleh siswa itu sendiri
  Route::get('/jadwal-saya', [JadwalController::class, 'jadwalSaya']);
});
Route::get('/test', function () {
  return response()->json(['message' => 'API OK!'], 200);
});


Route::middleware('auth:sanctum')->group(function () {
  // Token RTC video
  Route::get('/jadwal/{id}/rtc-token', 
      [JadwalController::class, 'generateRtcToken']
  );

  // Token Chat Agora, channel jadi bagian path
  Route::get(
      '/agora/chat-token/{channel}', 
      [AgoraChatController::class, 'chatToken']
  );
});

// sebelum route auth
Route::get('/debug-agora', function () {
  return response()->json([
      'app_id'     => config('services.agora.app_id'),
      'app_cert'   => config('services.agora.app_certificate'),
      'cert_length'=> strlen(config('services.agora.app_certificate')),
  ]);
});
// Semua route di bawah /api/riasec
Route::prefix('riasec')
    ->middleware('auth:sanctum')     // semua harus login
    ->group(function () {
        
        // ------------------
        // Untuk Siwa (role checkrole:siswa)
        // ------------------
        Route::middleware('checkrole:siswa')->group(function() {
            Route::get('questions',    [RiasecController::class,'questions']);
            Route::post('responses',   [RiasecController::class,'storeResponses']);
            Route::get('results',      [RiasecController::class,'results']);
            Route::get('history',      [RiasecController::class,'history']);
        });

       
    });


    