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
                    if(is_array($document) && isset($document['document'])){
                        $this->finalizecollection(
                            $document['document'],
                            $filetype,
                            $document['expected_count']
                        );
                        continue;

                    }
                    elseif(is_array($document) || $document instanceof \Illuminate\Support\Collection){
                        $this->finalizecollection($document,$filetype);
                        continue;
                    }else{
                        $this->finalize($document,$filetype);
                    }
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
        //bisa tambah logic tar kalau mau make finalize masing masing model
        if($document->status === "rejected"){
            throw new \Exception("Dokumen $filetype ditolak, tidak dapat difinalisasi. Mohon perbaiki dokumen sesuai catatan admin.");
        }
        if(!in_array($document->status, ['finalized','approved'])){
            $document->update([
                'status'=>'finalized',
            ]);
        }
    }
    public function finalizecollection($documents,string $filetype,$expected_count=null)

    {   
    
        if ($documents->isEmpty()|| !$documents){
            throw new \Exception("Dokumen $filetype tidak ditemukan untuk difinalisasi.mohon upload terlebih dahulu.");
        }
        if($expected_count !=null && $documents->count() != $expected_count){
            $count= $documents->count();
            throw new \Exception("Jumlah dokumen $filetype tidak sesuai untuk difinalisasi. Diperlukan $expected_count file, ditemukan {$count} file.");
        }
        foreach($documents as $document){
            $kodetabel=$document->kode_tabel;
            $this->finalize($document,"$filetype :tabel:$kodetabel");
        }

    }


}
