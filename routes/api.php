<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User; 
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Dinas\UploadController;
use App\Http\Controllers\Pusdatin\PenilaianSLHD_Controller;
use App\http\Controllers\Pusdatin\PenilaianPenghargaan_Controller;

Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\Auth\AuthController::class, 'register']);

// Route::get('pusdatin/penilaian/penghargaan/template/{year}',  [PenilaianPenghargaan_Controller::class, 'downloadTemplate']);

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
Route::middleware(['auth:sanctum', 'role:provinsi,kabupaten/kota', 'ensuresubmissions'])
->prefix('dinas/upload')
->group(function () {
    Route::post('/ringkasan-eksekutif', [UploadController::class, 'uploadRingkasanEksekutif'])->middleware('ensuredocument:ringkasanEksekutif');
    Route::post('/laporan-utama', [UploadController::class, 'uploadLaporanUtama'])->middleware('ensuredocument:laporanUtama');
    Route::post('/tabel-utama', [UploadController::class, 'uploadTabelUtama'])->middleware('ensuredocument:tabelUtama');
    Route::post('/iklh', [UploadController::class, 'uploadIklh'])->middleware('ensuredocument:iklh');
    
        Route::patch('/finalize-submission', [UploadController::class, 'finalizeSubmission']);
        Route::patch('/finalize/{type}', [UploadController::class, 'finalizeOne'])
            ->where('type', 'ringkasanEksekutif|laporanUtama|tabelUtama|iklh')
            ->middleware('ensuredocument');
        });
        
Route::middleware(['auth:sanctum', 'role:pusdatin'])->prefix('pusdatin/review')->group(function () {
    Route::get('/{year?}', [App\Http\Controllers\Pusdatin\ReviewController::class, 'index']);
    Route::get('/submission/{submission}', [App\Http\Controllers\Pusdatin\ReviewController::class, 'show']);
    Route::post('/submission/{submission}/{documentType}', [App\Http\Controllers\Pusdatin\ReviewController::class, 'reviewDocument'])
    ->where('documentType', 'ringkasanEksekutif|laporanUtama|iklh');
    
});     

Route::middleware(['auth:sanctum', 'role:pusdatin'])->prefix('pusdatin/penilaian')->group(function () {
    Route::prefix('slhd')->controller(PenilaianSLHD_Controller::class)->group(function (){
    Route::get('/test', 'tes');
    Route::get('/template',  'downloadTemplate');
    Route::post('/upload/{year}', 'uploadPenilaianSLHD')->middleware('ensureevaluation:upload,penilaian_slhd');
    Route::patch('/finalize/{year}/{penilaianSLHD}', 'finalizePenilaianSLHD')->middleware('ensureevaluation:finalize,penilaian_slhd');
    Route::get('/parsed/{penilaianSLHD}',  'getAllParsedPenilaianSLHD');
    Route::get('/status/{penilaianSLHD}',  'status');
    Route::patch('/unfinalize/{year}', 'unfinalized');
    
});

Route::prefix('penghargaan')->controller(App\Http\Controllers\Pusdatin\PenilaianPenghargaan_Controller::class)->group(function (){
    Route::get('/template/{year}',  'downloadTemplate');
    Route::post('/upload/{year}', 'uploadPenilaianPenghargaan')->middleware('ensureevaluation:upload,penilaian_penghargaan');
    Route::post('/finalize/{year}', 'finalizePenilaianPenghargaan')->middleware('ensureevaluation:finalize,penilaian_penghargaan');    
    });

});