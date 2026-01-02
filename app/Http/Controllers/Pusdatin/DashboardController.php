<?php

namespace App\Http\Controllers\Pusdatin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Submission;
use App\Models\PusdatinLog;
use App\Models\TahapanPenilaianStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get statistik utama dashboard pusdatin
     */
    public function getStats(Request $request)
    {
        
        $year = $request->get('year', date('Y'));

        // ===== 4 CARD ATAS =====
        
        // 1. Total DLH terdaftar
        $totalDlh = Dinas::count();

        // 2. Total Pengajuan Buku 1 (Ringkasan Eksekutif yang difinalisasi)
        $totalPengajuanBuku1 = Submission::where('tahun', $year)
            ->whereHas('ringkasanEksekutif', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();

        // 3. Total Pengajuan Buku 2 (Laporan Utama yang difinalisasi)
        $totalPengajuanBuku2 = Submission::where('tahun', $year)
            ->whereHas('laporanUtama', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();
        
        // 3b. Total Pengajuan Buku 3 (Lampiran yang difinalisasi)
        $totalPengajuanBuku3 = Submission::where('tahun', $year)
            ->whereHas('lampiran', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();

        // 4. Total Pengajuan IKLH (yang difinalisasi)
        $totalPengajuanIklh = Submission::where('tahun', $year)
            ->whereHas('iklh', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();

        // ===== STATUS TAHAP AKTIF =====
        $tahapan = TahapanPenilaianStatus::where('year', $year)->first();
        $tahapAktif = $tahapan?->tahap_aktif ?? 'submission';
        $tahapLabel = $this->getTahapLabel($tahapAktif);

        // ===== 8 CARD PROGRESS (HIJAU) =====
        
        // Card 1-3: Dokumen Stats (Approved vs Finalized)
        $buku1Finalized = Submission::where('tahun', $year)
            ->whereHas('ringkasanEksekutif', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();
        $buku1Approved = Submission::where('tahun', $year)
            ->whereHas('ringkasanEksekutif', fn($q) => $q->where('status', 'approved'))
            ->count();

        $buku2Finalized = Submission::where('tahun', $year)
            ->whereHas('laporanUtama', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();
        $buku2Approved = Submission::where('tahun', $year)
            ->whereHas('laporanUtama', fn($q) => $q->where('status', 'approved'))
            ->count();
        
        $buku3Finalized = Submission::where('tahun', $year)
            ->whereHas('lampiran', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();
        $buku3Approved = Submission::where('tahun', $year)
            ->whereHas('lampiran', fn($q) => $q->where('status', 'approved'))
            ->count();

        $iklhFinalized = Submission::where('tahun', $year)
            ->whereHas('iklh', fn($q) => $q->whereIn('status', ['finalized', 'approved']))
            ->count();
        $iklhApproved = Submission::where('tahun', $year)
            ->whereHas('iklh', fn($q) => $q->where('status', 'approved'))
            ->count();

        // ===== STATS DARI REKAP PENILAIAN =====
        $rekap = \App\Models\Pusdatin\RekapPenilaian::where('year', $year);
        $rekapTotal = (clone $rekap)->count();
        
        // Card 4: SLHD Stats - dari RekapPenilaian
        $slhd = \App\Models\Pusdatin\PenilaianSLHD::where(['year' => $year, 'status' => 'finalized'])->first();
        $slhdTotal = $rekapTotal;
        $slhdLolos = \App\Models\Pusdatin\RekapPenilaian::where('year', $year)
            ->where('lolos_slhd', true)
            ->count();

        // Card 5: Penghargaan Stats - dari RekapPenilaian
        $penghargaan = \App\Models\Pusdatin\PenilaianPenghargaan::where(['year' => $year, 'status' => 'finalized'])->first();
        $penghargaanTotal = $slhdLolos; // Yang masuk penghargaan = yang lolos SLHD
        $penghargaanLolos = \App\Models\Pusdatin\RekapPenilaian::where('year', $year)
            ->where('masuk_penghargaan', true)
            ->count();

        // Card 6: Validasi 1 Stats - dari RekapPenilaian
        $validasi1 = \App\Models\Pusdatin\Validasi1::where('year', $year)->first();
        $validasi1Total = $penghargaanLolos; // Yang masuk validasi 1 = yang lolos penghargaan
        $validasi1Lolos = \App\Models\Pusdatin\RekapPenilaian::where('year', $year)
            ->where('lolos_validasi1', true)
            ->count();

        // Card 7: Validasi 2 Stats - dari RekapPenilaian
        $validasi2 = \App\Models\Pusdatin\Validasi2::where('year', $year)->first();
        $validasi2Total = $validasi1Lolos; // Yang masuk validasi 2 = yang lolos validasi 1
        $validasi2Lolos = \App\Models\Pusdatin\RekapPenilaian::where('year', $year)
            ->where('lolos_validasi2', true)
            ->count();

        // Card 8: Wawancara Stats
        $wawancaraTotal = \App\Models\Pusdatin\Wawancara::where('year', $year)->count();
        $wawancaraDinilai = \App\Models\Pusdatin\Wawancara::where('year', $year)
            ->whereNotNull('nilai_wawancara')
            ->count();

        // Rata-rata Nilai SLHD (untuk card tambahan jika perlu)
        $avgNilaiSLHD = null;
        if ($slhd) {
            $avgNilaiSLHD = \App\Models\Pusdatin\Parsed\PenilaianSLHD_Parsed::where('penilaian_slhd_id', $slhd->id)
                ->avg('total_skor');
        }

        return response()->json([
            // 4 Card Atas
            'summary' => [
                'total_dlh' => $totalDlh,
                'total_pengajuan_buku1' => $totalPengajuanBuku1,
                'total_pengajuan_buku2' => $totalPengajuanBuku2,
                'total_pengajuan_buku3' => $totalPengajuanBuku3,
                'total_pengajuan_iklh' => $totalPengajuanIklh,
                'rata_rata_nilai' => $avgNilaiSLHD ? number_format($avgNilaiSLHD, 2) : null,
            ],
            
            // Status Tahap Aktif
            'tahap' => [
                'aktif' => $tahapAktif,
                'label' => $tahapLabel,
                'pengumuman_terbuka' => $tahapan?->pengumuman_terbuka ?? false,
                'keterangan' => $tahapan?->keterangan ?? 'Menunggu proses dimulai',
            ],
            
            // Card Progress
            'progress' => [
                // Card 1-4: Dokumen
                'buku1' => [
                    'label' => 'Ringkasan Eksekutif (Buku 1)',
                    'approved' => $buku1Approved,
                    'finalized' => $buku1Finalized,
                    'percentage' => $buku1Finalized > 0 ? round(($buku1Approved / $buku1Finalized) * 100) : 0,
                    'is_finalized' => $buku1Finalized > 0,
                ],
                'buku2' => [
                    'label' => 'Laporan Utama (Buku 2)',
                    'approved' => $buku2Approved,
                    'finalized' => $buku2Finalized,
                    'percentage' => $buku2Finalized > 0 ? round(($buku2Approved / $buku2Finalized) * 100) : 0,
                    'is_finalized' => $buku2Finalized > 0,
                ],
                'buku3' => [
                    'label' => 'Lampiran (Buku 3)',
                    'approved' => $buku3Approved,
                    'finalized' => $buku3Finalized,
                    'percentage' => $buku3Finalized > 0 ? round(($buku3Approved / $buku3Finalized) * 100) : 0,
                    'is_finalized' => $buku3Finalized > 0,
                ],
                'iklh' => [
                    'label' => 'IKLH',
                    'approved' => $iklhApproved,
                    'finalized' => $iklhFinalized,
                    'percentage' => $iklhFinalized > 0 ? round(($iklhApproved / $iklhFinalized) * 100) : 0,
                    'is_finalized' => $iklhFinalized > 0,
                ],
                // Card 5-9: Penilaian
                'slhd' => [
                    'label' => 'Tahap 1 (SLHD)',
                    'lolos' => $slhdLolos,
                    'total' => $slhdTotal,
                    'percentage' => $slhdTotal > 0 ? round(($slhdLolos / $slhdTotal) * 100) : 0,
                    'is_finalized' => $slhd?->is_finalized ?? false,
                ],
                'penghargaan' => [
                    'label' => 'Tahap 2 (Penghargaan)',
                    'lolos' => $penghargaanLolos,
                    'total' => $penghargaanTotal,
                    'percentage' => $penghargaanTotal > 0 ? round(($penghargaanLolos / $penghargaanTotal) * 100) : 0,
                    'is_finalized' => $penghargaan?->is_finalized ?? false,
                ],
                'validasi1' => [
                    'label' => 'Tahap 3 (Validasi 1)',
                    'lolos' => $validasi1Lolos,
                    'total' => $validasi1Total,
                    'percentage' => $validasi1Total > 0 ? round(($validasi1Lolos / $validasi1Total) * 100) : 0,
                    'is_finalized' => $validasi1?->is_finalized ?? false,
                ],
                'validasi2' => [
                    'label' => 'Tahap 4 (Validasi 2)',
                    'lolos' => $validasi2Lolos,
                    'total' => $validasi2Total,
                    'percentage' => $validasi2Total > 0 ? round(($validasi2Lolos / $validasi2Total) * 100) : 0,
                    'is_finalized' => $validasi2?->is_finalized ?? false,
                ],
                'wawancara' => [
                    'label' => 'Tahap 5 (Wawancara)',
                    'dinilai' => $wawancaraDinilai,
                    'total' => $wawancaraTotal,
                    'percentage' => $wawancaraTotal > 0 ? round(($wawancaraDinilai / $wawancaraTotal) * 100) : 0,
                    'is_finalized' => $tahapan?->tahap_aktif === 'selesai',
                ],
            ],
            
            // Legacy (backward compatibility)
            'total_dlh' => $totalDlh,
            'buku1_upload' => $buku1Finalized,
            'buku1_approved' => $buku1Approved,
            'buku2_upload' => $buku2Finalized,
            'buku2_approved' => $buku2Approved,
            'iklh_upload' => $iklhFinalized,
            'iklh_approved' => $iklhApproved,
            'avg_nilai_slhd' => $avgNilaiSLHD ? number_format($avgNilaiSLHD, 2) : 'Belum tersedia',
        ]);
    }
    
    /**
     * Get label untuk tahap
     */
    private function getTahapLabel($tahap)
    {
        $labels = [
            'submission' => 'Penerimaan Data',
            'penilaian_slhd' => 'Penilaian SLHD',
            'penilaian_penghargaan' => 'Penilaian Penghargaan',
            'validasi_1' => 'Validasi Tahap 1',
            'validasi_2' => 'Validasi Tahap 2',
            'wawancara' => 'Wawancara',
            'selesai' => 'Penilaian Selesai',
        ];
        
        return $labels[$tahap] ?? $tahap;
    }

    /**
     * Get status tahapan penilaian untuk dashboard
     */
    public function getTahapanProgress(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $tahapan = TahapanPenilaianStatus::where('year', $year)->first();

        if (!$tahapan) {
            return response()->json([
                'tahap_aktif' => 'submission',
                'progress' => [],
            ]);
        }

        // Mapping tahapan ke progress
        $stages = [
            'submission' => [
                'name' => 'Penerimaan Data',
                'status' => 'SLHD Provinsi',
                'detail' => 'Pengumpulan dokumen SLHD',
                'completed' => in_array($tahapan->tahap_aktif, ['penilaian_slhd', 'penilaian_penghargaan', 'validasi_1', 'validasi_2', 'wawancara']),
            ],
            'penilaian_slhd' => [
                'name' => 'Tahap 1 (Penilaian SLHD)',
                'status' => 'SLHD Kab/Kota',
                'detail' => 'Penilaian dokumen SLHD',
                'completed' => in_array($tahapan->tahap_aktif, ['penilaian_penghargaan', 'validasi_1', 'validasi_2', 'wawancara']),
            ],
            'penilaian_penghargaan' => [
                'name' => 'Tahap 2 (Penghargaan)',
                'status' => 'Penilaian',
                'detail' => 'Penilaian dokumen penghargaan',
                'completed' => in_array($tahapan->tahap_aktif, ['validasi_1', 'validasi_2', 'wawancara']),
            ],
            'validasi_1' => [
                'name' => 'Tahap 3 (Validasi 1)',
                'status' => 'Validasi Awal',
                'detail' => 'Validasi dokumen tahap 1',
                'completed' => in_array($tahapan->tahap_aktif, ['validasi_2', 'wawancara']),
            ],
            'validasi_2' => [
                'name' => 'Tahap 4 (Validasi 2)',
                'status' => 'Validasi Lanjutan',
                'detail' => 'Validasi dokumen tahap 2',
                'completed' => in_array($tahapan->tahap_aktif, ['wawancara']),
            ],
            'wawancara' => [
                'name' => 'Tahap 5 (Wawancara)',
                'status' => 'Final',
                'detail' => 'Wawancara dan penilaian akhir',
                'completed' => false,
            ],
        ];

        $progress = [];
        foreach ($stages as $key => $stage) {
            $isActive = $tahapan->tahap_aktif === $key;
            $progress[] = [
                'stage' => $stage['name'],
                'status' => $stage['status'],
                'detail' => $stage['detail'],
                'is_completed' => $stage['completed'],
                'is_active' => $isActive,
                'progress' => $stage['completed'] ? 100 : ($isActive ? 50 : 0),
            ];
        }

        return response()->json([
            'tahap_aktif' => $tahapan->tahap_aktif,
            'progress' => $progress,
        ]);
    }

    /**
     * Get notifikasi dan pengumuman
     */
    public function getNotifications(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $tahapan = TahapanPenilaianStatus::where('year', $year)->first();

        $announcement = null;
        $notification = null;

        if ($tahapan && $tahapan->pengumuman_terbuka) {
            $announcement = $tahapan->keterangan ?? 'Pengumuman dibuka untuk tahun ' . $year;
        }

        // Cek notifikasi deadline terdekat
        $nearestDeadline = DB::table('deadlines')
            ->where('tahun', $year)
            ->where('tanggal_akhir', '>=', now())
            ->orderBy('tanggal_akhir', 'asc')
            ->first();

        if ($nearestDeadline) {
            $daysLeft = now()->diffInDays($nearestDeadline->tanggal_akhir);
            $notification = "Deadline {$nearestDeadline->stage} dalam {$daysLeft} hari";
        }

        return response()->json([
            'announcement' => $announcement,
            'notification' => $notification,
        ]);
    }

    /**
     * Get aktivitas terkini
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->get('limit', 10);

        $activities = PusdatinLog::with(['dinas:id,nama_dinas'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'nama_dlh' => $log->dinas->nama_dinas ?? '-',
                    'status' => $this->mapStatus($log->activity_type),
                    'tanggal' => $log->created_at->format('d-m-Y'),
                    'aksi' => $log->activity_type,
                ];
            });

        return response()->json($activities);
    }

    /**
     * Map activity type ke status badge
     */
    private function mapStatus($activityType)
    {
        $statusMap = [
            'upload' => 'valid',
            'finalize' => 'valid',
            'approve' => 'valid',
            'reject' => 'menunggu validasi',
            'revision' => 'menunggu validasi',
        ];

        return $statusMap[$activityType] ?? 'valid';
    }

    /**
     * Get progress stats untuk semua tahapan penilaian
     */
    public function getProgressStats(Request $request)
    {
        $year = $request->get('year', date('Y'));

        // Get tahapan status
        $tahapan = TahapanPenilaianStatus::where('year', $year)->first();

        // Get total DLH (Kabupaten/Kota)
        $totalDlh = Dinas::count();

        // SLHD Stats - ambil yang sudah finalized (record aktif/dipilih)
        $slhd = \App\Models\Pusdatin\PenilaianSLHD::where(['year' => $year, 'status' => 'finalized'])->first();
        $slhdStats = [
            'is_finalized' => $slhd ? $slhd->is_finalized : false,
            'finalized' => $slhd 
                ? \App\Models\Pusdatin\Parsed\PenilaianSLHD_Parsed::where('penilaian_slhd_id', $slhd->id)->count()
                : 0,
        ];

        // Penghargaan Stats - ambil yang sudah finalized (record aktif/dipilih)
        $penghargaan = \App\Models\Pusdatin\PenilaianPenghargaan::where(['year' => $year, 'status' => 'finalized'])->first();
        $penghargaanStats = [
            'is_finalized' => $penghargaan ? $penghargaan->is_finalized : false,
            'finalized' => $penghargaan
                ? \App\Models\Pusdatin\Parsed\PenilaianPenghargaan_Parsed::where('penilaian_penghargaan_id', $penghargaan->id)->count()
                : 0,
        ];

        // Validasi 1 Stats - lolos dari RekapPenilaian
        $validasi1 = \App\Models\Pusdatin\Validasi1::where('year', $year)->first();
        $validasi1Stats = [
            'is_finalized' => $validasi1 ? $validasi1->is_finalized : false,
            'processed' => $validasi1 
                ? \App\Models\Pusdatin\Parsed\Validasi1Parsed::where('validasi_1_id', $validasi1->id)->count()
                : 0,
            'lolos' => \App\Models\Pusdatin\RekapPenilaian::where('year', $year)
                ->where('lolos_validasi1', true)
                ->count(),
        ];

        // Validasi 2 Stats - lolos dari RekapPenilaian, checked dari parsed table
        $validasi2 = \App\Models\Pusdatin\Validasi2::where('year', $year)->first();
        $validasi2Stats = [
            'is_finalized' => $validasi2 ? $validasi2->is_finalized : false,
            'processed' => $validasi2
                ? \App\Models\Pusdatin\Parsed\Validasi2Parsed::where('validasi_2_id', $validasi2->id)->count()
                : 0,
            'checked' => $validasi2
                ? \App\Models\Pusdatin\Parsed\Validasi2Parsed::where('validasi_2_id', $validasi2->id)
                    ->where(function($q) {
                        // Sudah dicentang jika salah satu kriteria true/false (bukan null)
                        $q->whereNotNull('Kriteria_WTP')
                          ->orWhereNotNull('Kriteria_Kasus_Hukum');
                    })
                    ->count()
                : 0,
            'lolos' => \App\Models\Pusdatin\RekapPenilaian::where('year', $year)
                ->where('lolos_validasi2', true)
                ->count(),
        ];

        // Wawancara Stats
        $wawancaraStats = [
            'is_finalized' => false,
            'processed' => \App\Models\Pusdatin\Wawancara::where('year', $year)->count(),
            'with_nilai' => \App\Models\Pusdatin\Wawancara::where('year', $year)
                ->whereNotNull('nilai_wawancara')
                ->count(),
        ];

        // Check if wawancara is finalized (check from tahapan or wawancara table)
        if ($tahapan && $tahapan->tahap_aktif === 'selesai') {
            $wawancaraStats['is_finalized'] = true;
        }

        return response()->json([
            'data' => [
                'slhd' => $slhdStats,
                'penghargaan' => $penghargaanStats,
                'validasi1' => $validasi1Stats,
                'validasi2' => $validasi2Stats,
                'wawancara' => $wawancaraStats,
                'total_dlh' => $totalDlh,
                'tahap_aktif' => $tahapan ? $tahapan->tahap_aktif : 'submission',
            ]
        ]);
    }
}
