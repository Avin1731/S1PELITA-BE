<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Dinas;
use App\Models\Region;
use Illuminate\Support\Str;
class DinasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $region=Region::all();
        foreach($region as $reg){
            $namadinas='Dinas Lingkungan Hidup';
            if($reg->type=='provinsi'){
                $namadinas.=' Provinsi '.$reg->nama_region;
            }else{
                $namadinas.=' '.$reg->nama_region;
            }  
            Dinas::create([
                'region_id' => $reg->id,
                'nama_dinas' => $namadinas,
                'kode_dinas' => Str::uuid(), 
                'status' => 'belum_terdaftar',
            ]);
}
}
}