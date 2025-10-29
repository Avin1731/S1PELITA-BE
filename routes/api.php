<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User; 
use App\Http\Controllers\Admin\AdminController;

Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);

Route::get('/user', function (Request $request) {
        $users = User::all();

        // return ke client dalam bentuk JSON
        return response()->json($users);
})->middleware('auth:sanctum');



Route::middleware(['auth:sanctum','role:admin'])->group(function () {
        Route::patch('/admin/users/approve/{id}', [AdminController::class, 'approveUser']);
        Route::delete('/admin/users/reject/{id}', [AdminController::class, 'rejectUser']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        Route::post('/admin/users/pusdatin', [AdminController::class, 'createPusdatin']);
});
Route::middleware(['auth:sanctum','role:provinsi,kabupaten/kota','ensuresubmissions'])->group(function () {
    Route::post('/dinas/upload/ringkasan_eksekutif', [App\Http\Controllers\Dinas\UploadController::class, 'uploadRingkasanEksekutif'])->middleware('ensuredocument:ringkasanEksekutif');
    Route::post('/dinas/upload/laporan_utama', [App\Http\Controllers\Dinas\UploadController::class, 'uploadLaporanUtama'])->middleware('ensuredocument:laporanUtama');
    Route::post('/dinas/upload/tabel_utama', [App\Http\Controllers\Dinas\UploadController::class, 'uploadTabelUtama'])->middleware('ensuredocument:tabelUtama');
    Route::post('/dinas/upload/iklh', [App\Http\Controllers\Dinas\UploadController::class, 'Iklh'])->middleware('ensuredocument:iklh');
    Route::patch('/dinas/upload/finalize_submission', [App\Http\Controllers\Dinas\UploadController::class, 'finalizeSubmission']);
});