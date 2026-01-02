<?php

namespace App\Console\Commands;

use App\Models\Pusdatin\Parsed\Validasi2Parsed;
use App\Models\Pusdatin\Validasi2;
use Illuminate\Console\Command;

class BulkUpdateValidasi2Checklist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validasi2:bulk-checklist 
                            {--year= : Tahun penilaian}
                            {--wtp=true : Nilai Kriteria_WTP (true/false)}
                            {--kasus=true : Nilai Kriteria_Kasus_Hukum (true/false)}
                            {--catatan= : Catatan untuk semua dinas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bulk update checklist Validasi 2 untuk dinas yang lolos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->option('year') ?? now()->year;
        $wtp = filter_var($this->option('wtp'), FILTER_VALIDATE_BOOLEAN);
        $kasus = filter_var($this->option('kasus'), FILTER_VALIDATE_BOOLEAN);
        $catatan = $this->option('catatan');

        // Check if Validasi2 exists and is finalized
        $validasi2 = Validasi2::where('year', $year)->first();
        
        if (!$validasi2) {
            $this->error("Validasi 2 untuk tahun {$year} tidak ditemukan!");
            return 1;
        }

        // if (!$validasi2->is_finalized) {
        //     $this->error("Validasi 2 untuk tahun {$year} belum difinalisasi!");
        //     return 1;
        // }

        // Get all Validasi2Parsed records
        $records = Validasi2Parsed::where('validasi_2_id', $validasi2->id)->get();

        if ($records->isEmpty()) {
            $this->warn("Tidak ada data Validasi 2 untuk tahun {$year}");
            return 0;
        }

        $this->info("Akan mengupdate {$records->count()} dinas untuk tahun {$year}");
        $this->info("Kriteria WTP: " . ($wtp ? 'Ya' : 'Tidak'));
        $this->info("Kriteria Kasus Hukum: " . ($kasus ? 'Ya' : 'Tidak'));
        
        if ($catatan) {
            $this->info("Catatan: {$catatan}");
        }

        if (!$this->confirm('Lanjutkan?')) {
            $this->info('Dibatalkan');
            return 0;
        }

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($records as $record) {
            try {
                $updateData = [
                    'Kriteria_WTP' => $wtp,
                    'Kriteria_Kasus_Hukum' => $kasus,
                ];

                if ($catatan) {
                    $updateData['catatan'] = $catatan;
                }

                $record->update($updateData);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "ID {$record->id} ({$record->nama_dinas}): {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Berhasil: {$successCount}");
        
        if ($errorCount > 0) {
            $this->error("❌ Gagal: {$errorCount}");
            $this->newLine();
            $this->error("Detail error:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }

        return 0;
    }
}
