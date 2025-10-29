<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jawaTengah = Region::create([
            'nama_region' => 'Jawa Tengah',
            'type' => 'provinsi',
        ]);

        $jawaBarat = Region::create([
            'nama_region' => 'Jawa Barat',
            'type' => 'provinsi',
        ]);

        // Tambah kabupaten di bawah Jawa Tengah
    

        Region::create([
            'nama_region' => 'Kabupaten Magelang',
            'type' => 'kabupaten/kota',
            'parent_id' => $jawaTengah->id,
            'kategori' => 'kabupaten_sedang',
        ]);

        // Tambah kabupaten di bawah Jawa Barat
        Region::create([
            'nama_region' => 'Kota Bandung',
            'type' => 'kabupaten/kota',
            'parent_id' => $jawaBarat->id,
            'kategori' => 'kota_besar',
        ]);
    }
}
