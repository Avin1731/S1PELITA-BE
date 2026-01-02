<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Submission;
use App\Models\Region;
use App\Models\TahapanPenilaianStatus;
use App\Models\Pusdatin\RekapPenilaian;
use App\Models\Deadline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function getStats(Request $request)
    {
        $year = $request->input('year', now()->year);
        
        // User statistics
        $totalUsers = User::count();
        $pendingApproval = User::where('is_active', false)->count();
        $activeUsers = User::where('is_active', true)->count();
        
        // Users by role
        $usersByRole = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get()
            ->pluck('count', 'role');
        
        // Users by region type (untuk DLH)
        $usersByRegionType = User::where('role', 'dinas')
            ->join('dinas', 'users.dinas_id', '=', 'dinas.id')
            ->join('regions', 'dinas.region_id', '=', 'regions.id')
            ->select('regions.type', DB::raw('count(*) as count'))
            ->groupBy('regions.type')
            ->get()
            ->pluck('count', 'type');
        
        // Submission statistics
        $totalSubmissions = Submission::where('tahun', $year)->count();
        $submissionsByStatus = Submission::where('tahun', $year)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
        
        // Storage usage (dalam MB)
        $storageUsed = 0;
        $storagePath = storage_path('app/dlh');
        if (file_exists($storagePath)) {
            $storageUsed = $this->getDirSize($storagePath) / (1024 * 1024); // Convert to MB
        }
        
        // Get timeline penilaian (tahapan + deadline + statistik lolos)
        $timelinePenilaian = $this->getTimelinePenilaian($year);
        
        return response()->json([
            // Format untuk FE (flat structure)
            'total_users_aktif' => $activeUsers,
            'total_users_pending' => $pendingApproval,
            
            // Detail tambahan (optional, FE bisa pakai kalau perlu)
            'year' => $year,
            'users' => [
                'total' => $totalUsers,
                'pending_approval' => $pendingApproval,
                'active' => $activeUsers,
                'by_role' => [
                    'admin' => $usersByRole['admin'] ?? 0,
                    'pusdatin' => $usersByRole['pusdatin'] ?? 0,
                    'dinas' => $usersByRole['dinas'] ?? 0,
                ],
                'dinas_by_type' => [
                    'provinsi' => $usersByRegionType['provinsi'] ?? 0,
                    'kabupaten_kota' => $usersByRegionType['kabupaten/kota'] ?? 0,
                ],
            ],
            'submissions' => [
                'total' => $totalSubmissions,
                'by_status' => [
                    'draft' => $submissionsByStatus['draft'] ?? 0,
                    'finalized' => $submissionsByStatus['finalized'] ?? 0,
                    'approved' => $submissionsByStatus['approved'] ?? 0,
                ],
            ],
            'storage' => [
                'used_mb' => round($storageUsed, 2),
                'used_gb' => round($storageUsed / 1024, 2),
            ],
            
            // Timeline penilaian
            'timeline_penilaian' => $timelinePenilaian,
        ]);
    }
    
    /**
     * Get timeline penilaian: tahapan aktif, deadline, dan statistik lolos per tahap
     */
    private function getTimelinePenilaian($year)
    {
        // Get tahapan status
        $tahapan = TahapanPenilaianStatus::where('year', $year)->first();
        
        // Get deadline submission
        $deadlineSubmission = Deadline::where('year', $year)
            ->where('stage', 'submission')
            ->where('is_active', true)
            ->first();
        
        // Get statistik dari RekapPenilaian
        $rekap = RekapPenilaian::where('year', $year);
        $totalDinas = $rekap->count();
        
        // Statistik per tahap
        $lolosSLHD = RekapPenilaian::where('year', $year)->where('lolos_slhd', true)->count();
        $masukPenghargaan = RekapPenilaian::where('year', $year)->where('masuk_penghargaan', true)->count();
        $lolosValidasi1 = RekapPenilaian::where('year', $year)->where('lolos_validasi1', true)->count();
        $lolosValidasi2 = RekapPenilaian::where('year', $year)->where('lolos_validasi2', true)->count();
        
        // Define tahapan order untuk progress
        $tahapanOrder = [
            'submission' => 1,
            'penilaian_slhd' => 2,
            'penilaian_penghargaan' => 3,
            'validasi_1' => 4,
            'validasi_2' => 5,
            'wawancara' => 6,
            'selesai' => 7,
        ];
        
        $currentTahap = $tahapan?->tahap_aktif ?? 'submission';
        $currentOrder = $tahapanOrder[$currentTahap] ?? 1;
        
        // Build timeline dengan status per tahap
        $timeline = [
            [
                'tahap' => 'submission',
                'label' => 'Submission DLH',
                'order' => 1,
                'status' => $currentOrder > 1 ? 'completed' : ($currentOrder == 1 ? 'active' : 'pending'),
                'deadline' => $deadlineSubmission ? [
                    'tanggal' => $deadlineSubmission->deadline_at->format('Y-m-d H:i:s'),
                    'tanggal_formatted' => $deadlineSubmission->deadline_at->translatedFormat('d F Y'),
                    'is_passed' => $deadlineSubmission->isPassed(),
                ] : null,
                'statistik' => [
                    'total_submission' => Submission::where('tahun', $year)->count(),
                    'finalized' => Submission::where('tahun', $year)->where('status', 'finalized')->count(),
                ],
            ],
            [
                'tahap' => 'penilaian_slhd',
                'label' => 'Penilaian SLHD',
                'order' => 2,
                'status' => $currentOrder > 2 ? 'completed' : ($currentOrder == 2 ? 'active' : 'pending'),
                'statistik' => [
                    'total_dinilai' => $totalDinas,
                    'lolos' => $lolosSLHD,
                    'tidak_lolos' => $totalDinas - $lolosSLHD,
                ],
            ],
            [
                'tahap' => 'penilaian_penghargaan',
                'label' => 'Penilaian Penghargaan',
                'order' => 3,
                'status' => $currentOrder > 3 ? 'completed' : ($currentOrder == 3 ? 'active' : 'pending'),
                'statistik' => [
                    'total_peserta' => $lolosSLHD, // Yang lolos SLHD
                    'masuk_penghargaan' => $masukPenghargaan,
                ],
            ],
            [
                'tahap' => 'validasi_1',
                'label' => 'Validasi Tahap 1',
                'order' => 4,
                'status' => $currentOrder > 4 ? 'completed' : ($currentOrder == 4 ? 'active' : 'pending'),
                'statistik' => [
                    'total_peserta' => $masukPenghargaan,
                    'lolos' => $lolosValidasi1,
                    'tidak_lolos' => $masukPenghargaan - $lolosValidasi1,
                ],
            ],
            [
                'tahap' => 'validasi_2',
                'label' => 'Validasi Tahap 2',
                'order' => 5,
                'status' => $currentOrder > 5 ? 'completed' : ($currentOrder == 5 ? 'active' : 'pending'),
                'statistik' => [
                    'total_peserta' => $lolosValidasi1,
                    'lolos' => $lolosValidasi2,
                    'tidak_lolos' => $lolosValidasi1 - $lolosValidasi2,
                ],
            ],
            [
                'tahap' => 'wawancara',
                'label' => 'Wawancara',
                'order' => 6,
                'status' => $currentOrder > 6 ? 'completed' : ($currentOrder == 6 ? 'active' : 'pending'),
                'statistik' => [
                    'total_peserta' => $lolosValidasi2,
                ],
            ],
            [
                'tahap' => 'selesai',
                'label' => 'Penilaian Selesai',
                'order' => 7,
                'status' => $currentOrder >= 7 ? 'completed' : 'pending',
            ],
        ];
        
        return [
            'year' => $year,
            'tahap_aktif' => $currentTahap,
            'tahap_label' => $this->getTahapLabel($currentTahap),
            'pengumuman_terbuka' => $tahapan?->pengumuman_terbuka ?? false,
            'keterangan' => $tahapan?->keterangan ?? 'Menunggu proses dimulai',
            'tahap_mulai_at' => $tahapan?->tahap_mulai_at,
            'progress_percentage' => round(($currentOrder / 7) * 100),
            'timeline' => $timeline,
            'summary' => [
                'total_dinas_terdaftar' => \App\Models\Dinas::count(),
                'total_submission' => Submission::where('tahun', $year)->count(),
                'lolos_slhd' => $lolosSLHD,
                'masuk_penghargaan' => $masukPenghargaan,
                'lolos_validasi_1' => $lolosValidasi1,
                'lolos_validasi_2' => $lolosValidasi2,
            ],
        ];
    }
    
    /**
     * Get label untuk tahap
     */
    private function getTahapLabel($tahap)
    {
        $labels = [
            'submission' => 'Submission DLH',
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
     * Get recent activities
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->input('limit', 10);
        
        // Get recent user registrations
        $recentUsers = User::with(['dinas.region'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user_registration',
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_role' => $user->role,
                    'dinas_name' => $user->dinas?->nama_dinas,
                    'status' => $user->is_active ? 'approved' : 'pending',
                    'timestamp' => $user->created_at,
                ];
            });
        
        // Get recent submissions
        $recentSubmissions = Submission::with(['dinas'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($submission) {
                return [
                    'type' => 'submission',
                    'submission_id' => $submission->id,
                    'dinas_name' => $submission->dinas?->nama_dinas,
                    'year' => $submission->tahun,
                    'status' => $submission->status,
                    'timestamp' => $submission->created_at,
                ];
            });
        
        // Merge and sort by timestamp
        $activities = $recentUsers->concat($recentSubmissions)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
        
        return response()->json([
            'activities' => $activities,
            'total' => $activities->count(),
        ]);
    }
    
    /**
     * Get user detail
     */
    public function getUserDetail($id)
    {
        $user = User::with([
            'dinas.region.parent',
            'submissions' => function ($query) {
                $query->orderBy('tahun', 'desc');
            },
        ])->findOrFail($id);
        
        $region = $user->dinas?->region;
        $provinsi = null;
        $kabupatenKota = null;
        
        if ($region) {
            if ($region->type === 'provinsi') {
                $provinsi = $region->nama_region;
            } else {
                $kabupatenKota = $region->nama_region;
                $provinsi = $region->parent?->nama_region;
            }
        }
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => [
                    'name' => $user->role,
                ],
                'is_active' => $user->is_active,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'dinas' => $user->dinas ? [
                'id' => $user->dinas->id,
                'nama' => $user->dinas->nama_dinas,
                'kode' => $user->dinas->kode_dinas,
                'provinsi' => $provinsi,
                'kabupaten_kota' => $kabupatenKota,
                'type' => $region?->type,
                'kategori' => $region?->kategori,
            ] : null,
            'submissions' => $user->submissions->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'tahun' => $submission->tahun,
                    'status' => $submission->status,
                    'created_at' => $submission->created_at,
                    'updated_at' => $submission->updated_at,
                ];
            }),
            'submissions_count' => $user->submissions->count(),
        ]);
    }
    
    /**
     * Calculate directory size recursively
     */
    private function getDirSize($dir)
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->getDirSize($each);
        }
        return $size;
    }
}
