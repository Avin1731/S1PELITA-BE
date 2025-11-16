<?php

namespace App\Http\Controllers\Dinas;

use App\Http\Controllers\Controller;
use App\Models\Files\LaporanUtama;
use App\Models\Files\RingkasanEksekutif;
use App\Models\Files\TabelUtama;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Files\Iklh;  
class UploadController extends Controller
{
    private const DLH_DISK = 'dlh';
    private const LEGACY_DISK = 'public';

    protected $DocumentFinalizer;
    public function __construct(\App\Services\DocumentFinalizer $DocumentFinalizer)
    {
        $this->DocumentFinalizer = $DocumentFinalizer;
    }
    private function deleteExistingPath(?string $path): void
    {
        if (! $path) {
            return;
        }

        foreach ([self::DLH_DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
    public function uploadRingkasanEksekutif(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:5020', 

        ],[
            'file.required' => 'File ringkasan eksekutif wajib diunggah.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ]);
        $submission = $request->submission;
        $tahun = $submission->tahun;
        $id_dinas = $submission->id_dinas;
        
        $existing = RingkasanEksekutif::where('submission_id', $submission->id)->first();
        if ($existing) {
            $this->deleteExistingPath($existing->path);
        }

        $folder = "uploads/{$tahun}/dlh_{$id_dinas}/ringkasan_eksekutif";
        $path = $request->file('file')->storeAs(
            $folder,
            "{$id_dinas}.{$tahun}.{$request->file('file')->getClientOriginalExtension()}",
            self::DLH_DISK 
        );

        if ($existing) {
            $existing->update([
                'path' => $path,
                'status' => 'draft',
            ]);
        }
        else {

            RingkasanEksekutif::create([
                    'submission_id' => $submission->id,
                    'status' => 'draft',
                    'path' => $path,
                ]);
        }
         return response()->json([
        'message' => $existing ? 'File berhasil diganti' : 'File berhasil diupload',
        'path' => $path,
        ]);
    }
    // }
    public function uploadLaporanUtama(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:5020', 

        ],[
            'file.required' => 'File laporan utama wajib diunggah.',
            'file.mimes' => 'File harus berformat PDF.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);
        $submission = $request->submission;
        $tahun = $submission->tahun;
        $id_dinas = $submission->id_dinas;
        
        $existing = LaporanUtama::where('submission_id', $submission->id)->first();
        if ($existing) {
            $this->deleteExistingPath($existing->path);
        }
    
        $folder = "uploads/{$tahun}/dlh_{$id_dinas}/laporan_utama";
        $path = $request->file('file')->storeAs(
            $folder,
            "{$id_dinas}.{$tahun}.{$request->file('file')->getClientOriginalExtension()}",
            self::DLH_DISK 
        );
    
        if ($existing) {
            $existing->update([
                'path' => $path,
                'status' => 'draft',
            ]);
        }
        else {
    
            LaporanUtama::create([
                    'submission_id' => $submission->id,
                    'status' => 'draft',
                    'path' => $path,
                ]);
        }
         return response()->json([
        'message' => $existing ? 'File berhasil diganti' : 'File berhasil diupload',
        'path' => $path,
        ]);
        // Logic for uploading Laporan Utama
    }
    public function uploadTabelUtama(Request $request)
    {   
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv|max:5020', 
            'kode_tabel' => 'required|string',
            'matra' => 'required|string',
        ],[
            'file.required' => 'File tabel utama wajib diunggah.',
            'file.mimes' => 'File harus berformat XLS, XLSX, atau CSV.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
            'kode_tabel.required' => 'Kode tabel wajib diisi.',
            'matra.required' => 'Matra wajib diisi.',
        ]);
        // Logic for uploading Tabel Utama
        $submission = $request->submission;
        $tahun = $submission->tahun;
        $id_dinas = $submission->id_dinas;

        $kode_tabel= $request->input('kode_tabel');
        $existing = TabelUtama::where(['submission_id'=> $submission->id,'kode_tabel'=> $kode_tabel])->first();
        $matra= $existing ? $existing->matra : $request->input('matra');
        
        if ($existing) {
            $this->deleteExistingPath($existing->path);
        }
        $folder = "uploads/{$tahun}/dlh_{$id_dinas}/tabel_utama/{$matra}";
        $path = $request->file('file')->storeAs(
            $folder,
            "{$id_dinas}.{$tahun}.{$kode_tabel}.{$request->file('file')->getClientOriginalExtension()}",
            self::DLH_DISK 
        );
        if ($existing) {
            $existing->update([
                'path' => $path,
                'status' => 'draft',
            ]);
        }
        else {
    
            TabelUtama::create([
                    'submission_id' => $submission->id,
                    'kode_tabel' => $kode_tabel,
                    'matra' => $matra,
                    'status' => 'draft',
                    'path' => $path,
                ]);
        }
         return response()->json([
        'message' => $existing ? 'File berhasil diganti' : 'File berhasil diupload',
        'path' => $path,
        ]);

    } 
    public function uploadIklh(Request $request)
    {   
        $request->validate([
            'indeks_kualitas_air' => 'required|numeric|min:0|max:100',
            'indeks_kualitas_udara' => 'required|numeric|min:0|max:100',
            'indeks_kualitas_lahan' => 'required|numeric|min:0|max:100',
            'indeks_kualitas_kehati' => 'required|numeric|min:0|max:100',
            'indeks_kualitas_pesisir_laut' => 'required|numeric|min:0|max:100',
        ],[
            'indeks_kualitas_air.required' => 'Indeks kualitas air wajib diisi.',
            'indeks_kualitas_air.numeric' => 'Indeks kualitas air harus berupa angka.',
            'indeks_kualitas_air.min' => 'Indeks kualitas air minimal 0.',
            'indeks_kualitas_air.max' => 'Indeks kualitas air maksimal 100.',
            'indeks_kualitas_udara.required' => 'Indeks kualitas udara wajib diisi.',
            'indeks_kualitas_udara.numeric' => 'Indeks kualitas udara harus berupa angka.',
            'indeks_kualitas_udara.min' => 'Indeks kualitas udara minimal 0.',
            'indeks_kualitas_udara.max' => 'Indeks kualitas udara maksimal 100.',
            'indeks_kualitas_lahan.required' => 'Indeks kualitas lahan wajib diisi.',
            'indeks_kualitas_lahan.numeric' => 'Indeks kualitas lahan harus berupa angka.',
            'indeks_kualitas_lahan.min' => 'Indeks kualitas lahan minimal 0.',
            'indeks_kualitas_lahan.max' => 'Indeks kualitas lahan maksimal 100.',
            'indeks_kualitas_kehati.required' => 'Indeks kualitas kehati wajib diisi.',
            'indeks_kualitas_kehati.numeric' => 'Indeks kualitas kehati harus berupa angka.',
            'indeks_kualitas_kehati.min' => 'Indeks kualitas kehati minimal 0.',
            'indeks_kualitas_kehati.max' => 'Indeks kualitas kehati maksimal 100.',
            'indeks_kualitas_pesisir_laut.required' => 'Indeks kualitas pesisir dan laut wajib diisi.',
            'indeks_kualitas_pesisir_laut.numeric' => 'Indeks kualitas pesisir dan laut harus berupa angka.',
            'indeks_kualitas_pesisir_laut.min' => 'Indeks kualitas pesisir dan laut minimal 0.',
            'indeks_kualitas_pesisir_laut.max' => 'Indeks kualitas pesisir dan laut maksimal 100.',
        ]); 
        // Logic for uploading Iklh
        $submission = $request->submission;
        $existing = Iklh::where('submission_id', $submission->id)->first();
        if($existing){
            $existing->update([
                'indeks_kualitas_air' => $request->input('indeks_kualitas_air'),
                'indeks_kualitas_udara' => $request->input('indeks_kualitas_udara'),
                'indeks_kualitas_lahan' => $request->input('indeks_kualitas_lahan'),
                'indeks_kualitas_kehati' => $request->input('indeks_kualitas_kehati'),
                'indeks_kualitas_pesisir_laut' => $request->input('indeks_kualitas_pesisir_laut'),
                'status' => 'draft',
            ]);
        } else {
            Iklh::create([
                'submission_id' => $submission->id,
                'indeks_kualitas_air' => $request->input('indeks_kualitas_air'),
                'indeks_kualitas_udara' => $request->input('indeks_kualitas_udara'),
                'indeks_kualitas_lahan' => $request->input('indeks_kualitas_lahan'),
                'indeks_kualitas_kehati' => $request->input('indeks_kualitas_kehati'),
                'indeks_kualitas_pesisir_laut' => $request->input('indeks_kualitas_pesisir_laut'),
                'status' => 'draft',
            ]);
        }
         return response()->json([
        'message' => $existing ? 'Nilai berhasil diganti' : 'Nilai berhasil diupload',

        ]);
    }
   
 
        // Logic for finalizing ringkasan
    public function finalizeSubmission(Request $request)
    {
        // Logic for finalizing submission
      
        $submission = $request->submission->load('ringkasanEksekutif', 'laporanUtama', 'tabelUtama', 'iklh');
       
        if($submission->status=='finalized' || $submission->status=='approved'){
            return response()->json([
                'message' => 'Submission tahun ini sudah difinalisasi, tidak dapat diubah.'
            ], 403);
        }
        try{
            $this->DocumentFinalizer->finalizeall([
                'ringkasanEksekutif'=>$submission->ringkasanEksekutif,
                'laporanUtama'=>$submission->laporanUtama,
                'tabelUtama'=>['document'=>$submission->tabelUtama, 'expected_count'=>2],
                'iklh'=>$submission->iklh,
            ]);
            $submission->update([
                'status'=>'finalized',
            ]);
            return response()->json([
                'message' => 'Submission berhasil difinalisasi.',
            ]);
        }catch(\Exception $e){
            $errorMessages = json_decode($e->getMessage(), true);
            return response()->json([
                'message' => 'Gagal memfinalisasi submission.',
                'errors' => $errorMessages,
            ], 400);
        }
    }
    public function finalizeOne(Request $request,$type){

        $submission = $request->submission->load($type);
        try{
            $document = $submission->$type;
            if($document instanceof \Illuminate\Support\Collection || is_array($document)){
                $modelClass = $submission->$type()->getModel()::class;
               $count= $modelClass::MIN_COUNT ?? null;
               $this->DocumentFinalizer->finalizecollection($document,$type,$count);
            }else{
                $this->DocumentFinalizer->finalize($document,$type);
            }
            return response()->json([
                'message' => "$type berhasil difinalisasi.",
            ]);
        }catch(\Exception $e){
            return response()->json([
                'message' => "Gagal memfinalisasi $type. ",
                'error' => $e->getMessage(),
            ], 400);
        }

}
}