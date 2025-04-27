<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\AgoraChatController;

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
  // RTC (video) token untuk jadwal tertentu
  Route::get('/jadwal/{id}/rtc-token', [JadwalController::class, 'generateRtcToken']);

  Route::get('/agora/chat-token',[AgoraChatController::class, 'chatToken']);

});