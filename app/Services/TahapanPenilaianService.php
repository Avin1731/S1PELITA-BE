<?php

namespace App\Services;

use App\Models\Pusdatin\RekapPenilaian;
use App\Models\TahapanPenilaianStatus;
use Illuminate\Support\Facades\Log;

class TahapanPenilaianService
{
    /**
     * Update tahap setelah finalize
     */
    public function updateSetelahFinalize($stage, $year): void
    {
        try {
            $tahapanStatus = TahapanPenilaianStatus::firstOrCreate(
                ['year' => $year],
                [
                    'tahap_aktif' => 'submission',
                    'pengumuman_terbuka' => false,
                    'tahap_mulai_at' => now()
                ]
            );

            // Map stage deadline ke tahap berikutnya
            $mapping = [
                'submission' => [
                    'tahap' => 'penilaian_slhd',
                    'pengumuman' => false,
                    'keterangan' => 'Tahap submission selesai. Menunggu penilaian SLHD.'
                ],
                'penilaian_slhd' => [
                    'tahap' => 'penilaian_penghargaan',
                    'pengumuman' => true,
                    'keterangan' => 'Hasil penilaian SLHD sudah tersedia untuk dilihat.'
                ],
                'penilaian_penghargaan' => [
                    'tahap' => 'validasi_1',
                    'pengumuman' => true,
                    'keterangan' => 'Hasil penilaian penghargaan sudah tersedia untuk dilihat.'
                ],
                'validasi_1' => [
                    'tahap' => 'validasi_2',
                    'pengumuman' => true,
                    'keterangan' => 'Hasil validasi tahap 1 sudah tersedia untuk dilihat.'
                ],
                'validasi_2' => [
                    'tahap' => 'wawancara',
                    'pengumuman' => true,
                    'keterangan' => 'Hasil validasi tahap 2 sudah tersedia. Menunggu tahap wawancara.'
                ],
                'wawancara' => [
                    'tahap' => 'selesai',
                    'pengumuman' => true,
                    'keterangan' => 'Penilaian selesai. Hasil final sudah tersedia untuk dilihat.'
                ]
            ];

            if (isset($mapping[$stage])) {
                $config = $mapping[$stage];
                
                $tahapanStatus->update([
                    'tahap_aktif' => $config['tahap'],
                    'pengumuman_terbuka' => $config['pengumuman'],
                    'keterangan' => $config['keterangan'],
                    'tahap_mulai_at' => now(),
                    'tahap_selesai_at' => now()
                ]);

                Log::info("Tahapan penilaian diupdate", [
                    'year' => $year,
                    'stage' => $stage,
                    'tahap_baru' => $config['tahap'],
                    'pengumuman' => $config['pengumuman']
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Gagal update tahapan penilaian setelah finalize", [
                'stage' => $stage,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update tahap setelah unfinalize (kembali ke tahap sebelumnya)
     */
    public function updateSetelahUnfinalize($stage, $year): void
    {
        try {
            $tahapanStatus = TahapanPenilaianStatus::where('year', $year)->first();

            if (!$tahapanStatus) {
                return;
            }

            // Map stage yang di-unfinalize ke tahap yang harus dikembalikan
            // Pengumuman mengikuti status tahap yang dikembalikan (tahap aktif kembali)
            $mapping = [
                'submission' => null, // Tidak ada tahap sebelumnya
                'penilaian_slhd' => [
                    'tahap' => 'submission',
                    'pengumuman' => false, // Submission belum ada pengumuman
                    'keterangan' => 'Penilaian SLHD dibatalkan. Kembali ke tahap submission.'
                ],
                'penilaian_penghargaan' => [
                    'tahap' => 'penilaian_slhd',
                    'pengumuman' => true, // SLHD aktif kembali, pengumuman tetap terbuka
                    'keterangan' => 'Penilaian penghargaan dibatalkan. Kembali ke tahap penilaian SLHD.'
                ],
                'validasi_1' => [
                    'tahap' => 'penilaian_penghargaan',
                    'pengumuman' => true, // Penghargaan aktif kembali, pengumuman tetap terbuka
                    'keterangan' => 'Validasi 1 dibatalkan. Kembali ke tahap penilaian penghargaan.'
                ],
                'validasi_2' => [
                    'tahap' => 'validasi_1',
                    'pengumuman' => true, // Validasi 1 aktif kembali, pengumuman tetap terbuka
                    'keterangan' => 'Validasi 2 dibatalkan. Kembali ke tahap validasi 1.'
                ],
                'wawancara' => [
                    'tahap' => 'validasi_2',
                    'pengumuman' => true, // Validasi 2 aktif kembali, pengumuman tetap terbuka
                    'keterangan' => 'Wawancara dibatalkan. Kembali ke tahap validasi 2.'
                ]
            ];
            
            if (isset($mapping[$stage])) {
                $config = $mapping[$stage];
                
                // Delete cascade records sebelum reset rekap
                $this->deleteCascadeRecords($stage, $year);
                
                $tahapanStatus->update([
                    'tahap_aktif' => $config['tahap'],
                    'pengumuman_terbuka' => $config['pengumuman'], // Ikuti konfigurasi tahap yang dikembalikan
                    'keterangan' => $config['keterangan'],
                    'tahap_selesai_at' => null
                ]);
                
                // Reset penilaian sesuai tahap yang di-unfinalize
                $this->resetPenilaianTahap($stage, $year);

                Log::info("Tahapan penilaian dikembalikan setelah unfinalize", [
                    'year' => $year,
                    'stage' => $stage,
                    'tahap_dikembalikan' => $config['tahap'],
                    'pengumuman_terbuka' => $config['pengumuman']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Gagal update tahapan penilaian setelah unfinalize", [
                'stage' => $stage,
                'year' => $year,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reset penilaian sesuai tahap yang di-unfinalize
     */
    public function resetPenilaianTahap(string $stage, int $year)
    {
        $stageOrder = [
            'submission',
            'penilaian_slhd',
            'penilaian_penghargaan',
            'validasi_1',
            'validasi_2',
            'wawancara',
        ];

        $stageFields = [
            'submission' => [],

            'penilaian_slhd' => [
                'nilai_slhd',
                'lolos_slhd',
            ],

            'penilaian_penghargaan' => [
                'nilai_penghargaan',
                'masuk_penghargaan',
            ],

            'validasi_1' => [
                'nilai_iklh',
                'total_skor_validasi1',
                'lolos_validasi1',
            ],

            'validasi_2' => [
                'kriteria_wtp',
                'kriteria_kasus_hukum',
                'lolos_validasi2',
                'peringkat',
            ],

            'wawancara' => [
                'nilai_wawancara',
                'lolos_wawancara',
                'total_skor_final',
                'peringkat_final',
            ],
        ];

        // Mapping status_akhir setelah unfinalize suatu tahap
        // Key = stage yang di-unfinalize, Value = status_akhir yang harus di-set
        $statusAfterUnfinalize = [
            'penilaian_slhd'        => 'menunggu_penilaian_slhd',
            'penilaian_penghargaan' => 'menunggu_penilaian_penghargaan',           // Kembali ke setelah lolos SLHD
            'validasi_1'            => 'menunggu_validasi1',    // Kembali ke setelah lolos penghargaan
            'validasi_2'            => 'menunggu_validasi2',      // Kembali ke setelah lolos validasi1
            'wawancara'             => 'menunggu_wawancara',      // Kembali ke setelah lolos validasi2
        ];

        $currentIndex = array_search($stage, $stageOrder);

        if ($currentIndex === false) {
            throw new \InvalidArgumentException('Stage tidak valid');
        }

        // Field yang harus di-reset (SEMUA setelah stage ini)
        $fieldsToReset = [];

        for ($i = $currentIndex - 1; $i < count($stageOrder); $i++) {
            $nextStage = $stageOrder[$i];
            $fieldsToReset = array_merge(
                $fieldsToReset,
                $stageFields[$nextStage]
            );
        }

        if (empty($fieldsToReset)) {
            return;
        }

        // Tentukan default reset
        $resetData = [];
        foreach ($fieldsToReset as $field) {
            if (str_starts_with($field, 'lolos') || str_starts_with($field, 'masuk')) {
                $resetData[$field] = false;
            } else {
                $resetData[$field] = null;
            }
        }

        // Set status_akhir sesuai mapping
        $newStatus = $statusAfterUnfinalize[$stage] ?? 'menunggu_penilaian_slhd';
        $resetData['status_akhir'] = $newStatus;

        RekapPenilaian::where('year', $year)->update($resetData);
    }


    /**
     * Toggle pengumuman untuk tahap saat ini
     */
    public function togglePengumuman($year, $terbuka = true, $keterangan = null): bool
    {
        try {
            $tahapanStatus = TahapanPenilaianStatus::where('year', $year)->first();

            if (!$tahapanStatus) {
                return false;
            }

            $tahapanStatus->setPengumuman($terbuka, $keterangan);

            Log::info("Pengumuman toggled", [
                'year' => $year,
                'terbuka' => $terbuka,
                'tahap' => $tahapanStatus->tahap_aktif
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Gagal toggle pengumuman", [
                'year' => $year,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get status tahapan untuk year tertentu
     */
    public function getStatusTahapan($year)
    {
        return TahapanPenilaianStatus::firstOrCreate(
            ['year' => $year],
            [
                'tahap_aktif' => 'submission',
                'pengumuman_terbuka' => false,
                'tahap_mulai_at' => now()
            ]
        );
    }

    /**
     * Delete cascade records saat unfinalize
     */
    private function deleteCascadeRecords(string $stage, int $year): void
    {
        switch ($stage) {
            case 'penilaian_slhd':
                // Delete template file
                \Storage::disk('templates')->delete("penilaian/template_penilaian_penghargaan_{$year}.xlsx");
                
                // Delete PenilaianPenghargaan (cascade to Validasi1 & Validasi2 via FK)
                \App\Models\Pusdatin\PenilaianPenghargaan::where('year', $year)->delete();
                
                // Delete Wawancara (no FK cascade)
                \App\Models\Pusdatin\Wawancara::where('year', $year)->delete();
                break;

            case 'penilaian_penghargaan':
                // Delete Validasi1 (cascade to Validasi2 via FK)
                \App\Models\Pusdatin\Validasi1::where('year', $year)->delete();
                
                // Delete Wawancara (no FK cascade)
                \App\Models\Pusdatin\Wawancara::where('year', $year)->delete();
                break;

            case 'validasi_1':
                // Delete Validasi2
                \App\Models\Pusdatin\Validasi2::where('year', $year)->delete();
                
                // Delete Wawancara (no FK cascade)
                \App\Models\Pusdatin\Wawancara::where('year', $year)->delete();
                break;

            case 'validasi_2':
                // Delete Wawancara (no FK cascade)
                \App\Models\Pusdatin\Wawancara::where('year', $year)->delete();
                break;

            case 'wawancara':
                // Wawancara reset handled by resetFinalScores in controller
                break;
        }

        Log::info("Deleted cascade records for stage unfinalize", [
            'stage' => $stage,
            'year' => $year
        ]);
    }
}
