<?php

namespace App\Services;

use App\Models\Pusdatin\PenilaianSLHD;
use App\Models\Pusdatin\PenilaianPenghargaan;
use App\Models\Pusdatin\Parsed\PenilaianSLHD_Parsed;
use App\Models\Pusdatin\Parsed\PenilaianPenghargaan_Parsed;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Spatie\SimpleExcel\SimpleExcelReader;

class PenilaianParsingService
{
    protected ExcelService $excelService;
    protected SLHDService $slhdService;

    public function __construct(ExcelService $excelService, SLHDService $slhdService)
    {
        $this->excelService = $excelService;
        $this->slhdService = $slhdService;
    }

    /**
     * Parse Penilaian SLHD Excel file
     */
    public function parsePenilaianSLHD(PenilaianSLHD $batch): void
    {
        $batch->update(['status' => 'parsing']);

        $map = [
            'id_dinas' => 'int',
            'Bab_1' => 'int',
            'Jumlah_Pemanfaatan_Pelayanan_Laboratorium' => 'int',
            'Daya_Dukung_dan_Daya_Tampung_Lingkungan_Hidup' => 'int',
            'Kajian_Lingkungan_Hidup_Strategis' => 'int',
            'Keanekaragaman_Hayati' => 'int',
            'Kualitas_Air' => 'int',
            'Laut_Pesisir_dan_Pantai' => 'int',
            'Kualitas_Udara' => 'int',
            'Pengelolaan_Sampah_dan_Limbah' => 'int',
            'Lahan_dan_Hutan' => 'int',
            'Perubahan_Iklim' => 'int',
            'Risiko_Bencana' => 'int',
            'Penetapan_Isu_Prioritas' => 'int',
            'Bab_3' => 'int',
            'Bab_4' => 'int',
            'Bab_5' => 'int'
        ];

        $rowToInsert = [];

        try {
            $filepath = Storage::disk('pusdatin')->path($batch->file_path);
            Log::info("Parsing SLHD file at path: " . $filepath);

            $rows = $this->excelService->import($filepath);

            // Eager load semua dinas sekali untuk performance
            $allDinas = \App\Models\Dinas::all()->keyBy('id');

            foreach ($rows as $row) {
                $errors = [];
                $data = [
                    'penilaian_slhd_id' => $batch->id,
                ];

                foreach ($map as $field => $type) {
                    $data[$field] = safe($field, fn() => validateValue($row[$field] ?? null, $type), $errors);
                }

                // Skip row kosong
                if (!isset($data['id_dinas']) || $data['id_dinas'] === null) {
                    continue;
                }

                // Validasi nama dinas dari database
                $dinas = $allDinas->get($data['id_dinas']);
                if ($dinas) {
                    $data['nama_dinas'] = $dinas->nama_dinas;
                } else {
                    $errors['id_dinas'] = "Dinas dengan ID {$data['id_dinas']} belum terdaftar di sistem.";
                    $data['nama_dinas'] = $row['nama_dinas'] ?? null;
                }

                $data['status'] = empty($errors) ? 'parsed_ok' : 'parsed_error';
                $data['error_messages'] = empty($errors) ? null : json_encode($errors);
                $data['created_at'] = now();
                $data['updated_at'] = now();
                $data['Total_Skor'] = safe('Total_Skor', fn() => $this->slhdService->calculate($row), $errors);

                $rowToInsert[] = $data;
            }

            if (!empty($rowToInsert)) {
                PenilaianSLHD_Parsed::insert($rowToInsert);
            }

            $batch->update(['status' => 'parsed_ok']);
            Log::info("Parsing SLHD completed: " . count($rowToInsert) . " rows");

        } catch (\Throwable $e) {
            Log::error("Fatal parsing error SLHD: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $batch->update([
                'status' => 'parsed_failed',
                'error_messages' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Parse Penilaian Penghargaan Excel file
     */
    public function parsePenilaianPenghargaan(PenilaianPenghargaan $batch): void
    {
        $batch->update(['status' => 'parsing']);

        $bobot = [
            'Adipura' => 0.35,
            'Adiwiyata' => 0.15,
            'Proklim' => 0.19,
            'Proper' => 0.21,
            'Kalpataru' => 0.1,
        ];

        $rowToInsert = [];

        try {
            $filepath = Storage::disk('pusdatin')->path($batch->file_path);
            Log::info("Parsing penghargaan file: " . $filepath);

            // Eager load semua dinas sekali untuk performance
            $allDinas = \App\Models\Dinas::all()->keyBy('id');

            SimpleExcelReader::create($filepath)
                ->noHeaderRow()
                ->skip(2)
                ->getRows()
                ->each(function (array $rowValues) use ($batch, &$rowToInsert, $bobot, $allDinas) {
                    $errors = [];

                    $data = [
                        'penilaian_penghargaan_id' => $batch->id,
                    ];

                    $data['id_dinas'] = safe('id_dinas', fn() => validateValue($rowValues[0] ?? null, 'int'), $errors);

                    // Skip row kosong
                    if (!isset($data['id_dinas']) || $data['id_dinas'] === null) {
                        return;
                    }

                    $data['Adipura_Jumlah_Wilayah'] = safe('Adipura_Jumlah_Wilayah', fn() => validateValue($rowValues[2] ?? null, 'int'), $errors);
                    $data['Adipura_Skor_Max'] = safe('Adipura_Skor_Max', fn() => validateValue($rowValues[3] ?? null, 'int'), $errors);
                    $data['Adipura_Skor'] = safe('Adipura_Skor', fn() => validateValue($rowValues[4] ?? null, 'int'), $errors);

                    $data['Adiwiyata_Jumlah_Sekolah'] = safe('Adiwiyata_Jumlah_Sekolah', fn() => validateValue($rowValues[5] ?? null, 'int'), $errors);
                    $data['Adiwiyata_Skor_Max'] = safe('Adiwiyata_Skor_Max', fn() => validateValue($rowValues[6] ?? null, 'int'), $errors);
                    $data['Adiwiyata_Skor'] = safe('Adiwiyata_Skor', fn() => validateValue($rowValues[7] ?? null, 'int'), $errors);

                    $data['Proklim_Jumlah_Desa'] = safe('Proklim_Jumlah_Desa', fn() => validateValue($rowValues[8] ?? null, 'int'), $errors);
                    $data['Proklim_Skor_Max'] = safe('Proklim_Skor_Max', fn() => validateValue($rowValues[9] ?? null, 'int'), $errors);
                    $data['Proklim_Skor'] = safe('Proklim_Skor', fn() => validateValue($rowValues[10] ?? null, 'int'), $errors);

                    $data['Proper_Jumlah_Perusahaan'] = safe('Proper_Jumlah_Perusahaan', fn() => validateValue($rowValues[11] ?? null, 'int'), $errors);
                    $data['Proper_Skor_Max'] = safe('Proper_Skor_Max', fn() => validateValue($rowValues[12] ?? null, 'int'), $errors);
                    $data['Proper_Skor'] = safe('Proper_Skor', fn() => validateValue($rowValues[13] ?? null, 'int'), $errors);

                    $data['Kalpataru_Jumlah_Penerima'] = safe('Kalpataru_Jumlah_Penerima', fn() => validateValue($rowValues[14] ?? null, 'int'), $errors);
                    $data['Kalpataru_Skor_Max'] = safe('Kalpataru_Skor_Max', fn() => validateValue($rowValues[15] ?? null, 'int'), $errors);
                    $data['Kalpataru_Skor'] = safe('Kalpataru_Skor', fn() => validateValue($rowValues[16] ?? null, 'int'), $errors);

                    // Hitung persentase
                    $data['Adipura_Persentase'] = safe('Adipura_Persentase', fn() => $data['Adipura_Skor_Max'] > 0 ? ($data['Adipura_Skor'] / $data['Adipura_Skor_Max']) * 100 : 0, $errors);
                    $data['Adiwiyata_Persentase'] = safe('Adiwiyata_Persentase', fn() => $data['Adiwiyata_Skor_Max'] > 0 ? ($data['Adiwiyata_Skor'] / $data['Adiwiyata_Skor_Max']) * 100 : 0, $errors);
                    $data['Proklim_Persentase'] = safe('Proklim_Persentase', fn() => $data['Proklim_Skor_Max'] > 0 ? ($data['Proklim_Skor'] / $data['Proklim_Skor_Max']) * 100 : 0, $errors);
                    $data['Proper_Persentase'] = safe('Proper_Persentase', fn() => $data['Proper_Skor_Max'] > 0 ? ($data['Proper_Skor'] / $data['Proper_Skor_Max']) * 100 : 0, $errors);
                    $data['Kalpataru_Persentase'] = safe('Kalpataru_Persentase', fn() => $data['Kalpataru_Skor_Max'] > 0 ? ($data['Kalpataru_Skor'] / $data['Kalpataru_Skor_Max']) * 100 : 0, $errors);

                    // Hitung Total Skor
                    $data['Total_Skor'] = safe(
                        'Total_Skor',
                        fn() => ($data['Adipura_Persentase'] * $bobot['Adipura']) +
                                ($data['Adiwiyata_Persentase'] * $bobot['Adiwiyata']) +
                                ($data['Proklim_Persentase'] * $bobot['Proklim']) +
                                ($data['Proper_Persentase'] * $bobot['Proper']) +
                                ($data['Kalpataru_Persentase'] * $bobot['Kalpataru']),
                        $errors
                    );

                    // Validasi nama dinas dari database
                    $dinas = $allDinas->get($data['id_dinas']);
                    if ($dinas) {
                        $data['nama_dinas'] = $dinas->nama_dinas;
                    } else {
                        $errors['id_dinas'] = "Dinas dengan ID {$data['id_dinas']} belum terdaftar di sistem.";
                        $data['nama_dinas'] = $rowValues[1] ?? null;
                    }

                    $data['status'] = empty($errors) ? 'parsed_ok' : 'parsed_error';
                    $data['error_messages'] = empty($errors) ? null : json_encode($errors);
                    $data['created_at'] = now();
                    $data['updated_at'] = now();

                    $rowToInsert[] = $data;
                });

            if (!empty($rowToInsert)) {
                PenilaianPenghargaan_Parsed::insert($rowToInsert);
            }

            $batch->update(['status' => 'parsed_ok']);
            Log::info("Parsing penghargaan completed: " . count($rowToInsert) . " rows");

        } catch (\Throwable $e) {
            Log::error("Fatal parsing error penghargaan: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $batch->update([
                'status' => 'parsed_failed',
                'error_messages' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate Template Penilaian Penghargaan dari hasil SLHD
     */
    public function generateTemplatePenghargaan(PenilaianSLHD $penilaianSLHD): string
    {
        $parsed = $penilaianSLHD->penilaianSLHDParsed()->get();
        $path = "penilaian/template_penilaian_penghargaan_{$penilaianSLHD->year}.xlsx";

        $multiplier = [
            'adiwiyata' => 4,
            'proklim' => 100,
            'proper' => 3,
            'kaltaparu' => 3,
            'adipura' => 90,
        ];

        $eligible = [];

        foreach ($parsed as $row) {
            $data = $row->toArray();
            if ($this->slhdService->passesSLHD($data)) {
                $eligible[] = [
                    'id_dinas' => $data['id_dinas'],
                    'year' => $penilaianSLHD->year,
                    'nama_dinas' => $data['nama_dinas'],
                    'skor_total' => $this->slhdService->calculate($data),
                ];
            }
        }

        $this->excelService->generateTemplatePenilaianPenghargaan($eligible, $multiplier, $path, 'templates');

        Log::info("Generated template penghargaan: {$path}, eligible: " . count($eligible));

        return $path;
    }
}
