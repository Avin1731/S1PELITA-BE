<?php

namespace App\Http\Controllers\Pusdatin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateTemplatePenilaianPenghargaan;
use App\Models\Pusdatin\PenilaianSLHD;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Phiki\Grammar\Injections\Path;
use App\Jobs\ParsePenilaianSLHDJob;
use App\Models\Pusdatin\Parsed\PenilaianSLHD_Parsed;
use App\Services\ExcelService;
use App\services\SLHDService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Log;

class PenilaianSLHD_Controller extends Controller
{
    private const SLHD_TEMPLATE_PATH = 'penilaian/template_penilaian_slhd.xlsx';
    private const STORAGE_DISK = 'pusdatin';
    protected $logService;
    protected $excelService;
    protected $slhdService;

    public function deleteExistingPath($filePath){
        $disk = Storage::disk(self::STORAGE_DISK);
        if(!$filePath) return;
        if($disk->exists($filePath)){
            $disk->delete($filePath);
        }
    }
     
    public function __construct(LogService $logService, ExcelService $excelService,SLHDService $slhdService)
    {
        $this->logService = $logService;
        $this->excelService = $excelService;
        $this->slhdService = $slhdService;

        
    }
    public function tes(Request $request){
        return response()->json(['message' => 'Test successful','id'=>$request->user()->id]);
    }
    public function downloadTemplate()
    {
        $disk = Storage::disk('templates');

        if (! $disk->exists(self::SLHD_TEMPLATE_PATH)) {
            return response()->json([
                'message' => 'Template penilaian SLHD belum tersedia.',
                'path' => self::SLHD_TEMPLATE_PATH
            ], 404);
        }

        $templatePath = $disk->path(self::SLHD_TEMPLATE_PATH);

        @ob_clean();

        return Storage::disk('templates')->download(self::SLHD_TEMPLATE_PATH, 'template_penilaian_slhd.xlsx',[
             'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="laporan_penilaian.pdf"',
        ]);

    }
    public function uploadPenilaianSLHD(Request $request,$year){

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // Maksimal 10MB
        ],[
            'file.required' => 'File penilaian SLHD harus diunggah.',
            'file.file' => 'Yang diunggah harus berupa file.',
            'file.mimes' => 'Format file harus berupa xlsx atau xls.',
            'file.max' => 'Ukuran file maksimal 10MB.',
        ]);
        
        $year =$year?? now()->year();
        $file = $request->file('file');
        $path = 'penilaian/slhd/'.$year;

        $fileName = 'penilaian_slhd_'.$year.'_'.now()->format('YmdHis').'.'.$file->getClientOriginalExtension();
        
        
        $existing= PenilaianSLHD::where('year',$year)->first();
        
        
        $filePath = $file->storeAs($path, $fileName, self::STORAGE_DISK);
        $penilaian = PenilaianSLHD::Create(
            [
                'year' => $year,
                'uploaded_by' => $request->user()->id,
                'status' => 'uploaded',
                'file_path' => $filePath,
                'uploaded_at' => now(),
                'is_finalized' => false,
                'catatan' => $request->catatan??null,
            ]
        );
        
        $this->logService->log([
            'year' => $year,
            'actor_id' => $request->user()->id,
            'stage' => 'penilaian_slhd',
            'activity_type' => 'upload',
            'document_type' => null,
            'catatan' => $request->catatan ?? null,
            'status' =>'success'

        ]);
        dispatch(new ParsePenilaianSLHDJob($penilaian))->onQueue('penilaian_slhd_parsing');
        return response()->json([
            'message' =>  'Penilaian SLHD berhasil diunggah.',
            'data' => $penilaian
        ], 200);
}
    public function getPenilaianSLHD(Request $request,$year){
        $penilaian = PenilaianSLHD::where('year',$year)->orderByDesc('created_at')->get();
        if(!$penilaian){
            return response()->json([
                'message' => 'Penilaian SLHD untuk tahun '.$year.' tidak ditemukan.'
            ],404);
        }
        return response()->json([
            'data' => $penilaian
        ],200);
    }

    public function getAllParsedPenilaianSLHD(Request $request,PenilaianSLHD $penilaianSLHD){

        return response()->json(
            [
               "data" => $penilaianSLHD->penilaianSLHDParsed
            ]
            );
        
       
    }
    public function status(PenilaianSLHD $penilaianSLHD){
        return response()->json(["status"=>$penilaianSLHD->status],200);

    }

    public function finalizePenilaianSLHD(Request $request,$year,PenilaianSLHD $penilaianSLHD){
        $penilaianSLHD->update(
            [
                'is_finalized' => true,
                'finalized_at' => now(),
                'status' => 'finalized',
                'catatan' => $request->catatan ?? null,
            ]
            );
        $this->logService->log([
            'year' => $penilaianSLHD->year,
            'actor_id' => $request->user()->id,
            'stage' => 'penilaian_slhd',
            'activity_type' => 'finalize',
            'document_type' => null,
            'catatan' => $request->catatan ?? null,
            'status' =>'success'
        ]);
        dispatch(new GenerateTemplatePenilaianPenghargaan($penilaianSLHD))->onQueue('generate_templates_penghargaan');
        return response()->json([
            'message' => 'Penilaian SLHD untuk tahun '.$penilaianSLHD->year.' berhasil difinalisasi.',
            'data' => $penilaianSLHD
        ],200);
        
    }

    public function  unfinalized(
    Request $request,$year
    ){
        $penilaianSLHD= PenilaianSLHD::where(['year'=>$year,'status'=>'finalized'])->first();
        if(!$penilaianSLHD){
            return response()->json([
                'message' => 'Penilaian SLHD untuk tahun '.$year.' tidak ditemukan.'
            ],404);
        }
        $penilaianSLHD->update(
            [
                'is_finalized' => false,
                'finalized_at' => null,
                'status' => 'parsed_ok',
                'catatan' => $request->catatan ?? null,
            ]
            );
        $this->logService->log([
            'year' => $penilaianSLHD->year,
            'actor_id' => $request->user()->id,
            'stage' => 'penilaian_slhd',
            'activity_type' => 'reopen',
            'document_type' => null,
            'catatan' => $request->catatan ?? null,
            'status' =>'success'
        ]);
        return response()->json([
            'message' => 'Penilaian SLHD untuk tahun '.$penilaianSLHD->year.' berhasil dibuka kembali.',
            'data' => $penilaianSLHD
        ],200);
    }
}
