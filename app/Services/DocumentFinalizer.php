<?php

namespace App\Services;
use Illuminate\Support\Facades\DB;
class DocumentFinalizer
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function finalizeall( array $documents)
    {
        DB::transaction(function () use ($documents) {
            $errors =[];
            foreach($documents as $filetype=>$document){
                try{
        
                    $this->finalize($document,$filetype);
                } catch(\Exception $e){
                    $errors[]=$e->getMessage();
                }
            }
            if(!empty($errors)){
                throw new \Exception(json_encode($errors,JSON_UNESCAPED_UNICODE));
            }
            
        }); 
    }
    public function finalize($document,string $filetype)
    {
        if(!$document){
            throw new \Exception("Dokumen $filetype tidak ditemukan untuk difinalisasi.mohon upload terlebih dahulu.");

        }
        if($document->status === "rejected"){
            throw new \Exception("Dokumen $filetype ditolak, tidak dapat difinalisasi. Mohon perbaiki dokumen sesuai catatan admin.");
        }
        if(!in_array($document->status, ['finalized','approved'])){
            $document->update([
                'status'=>'finalized',
            ]);
        }
    }

}
