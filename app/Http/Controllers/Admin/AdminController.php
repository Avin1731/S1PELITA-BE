<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dinas;
use App\Models\ActivityLog; 
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log Laravel untuk debugging

class AdminController extends Controller
{
    // =================================================================
    // ACTIONS USER (APPROVE / REJECT / DELETE / CREATE)
    // =================================================================

    public function approveUser($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $user = User::findOrFail($id);
                // Cek relasi dinas dengan aman
                $dinas = $user->dinas()->first(); 

                $targetName = $user->email;

                if ($dinas) {
                    if ($dinas->status === 'terdaftar') {
                        throw new \Exception('User tidak bisa diaktifkan, dinas sudah Terdaftar.');
                    }
                    $dinas->update(['status' => 'terdaftar']);
                    $targetName = $dinas->nama_dinas;
                }

                $user->update(['is_active' => true]);

                // [LOG] Wrap in safeLog agar tidak membatalkan transaksi utama jika log error
                $this->safeLog('approve_user', "Menyetujui akun: {$targetName}", $user);
            });

            return response()->json(['message' => 'Berhasil Aktivasi User']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal aktivasi user', 'error' => $e->getMessage()], 400);
        }
    }

    public function rejectUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $email = $user->email; 

            if($user->is_active){
                return response()->json(['message' => 'User sudah diaktifkan, tidak bisa ditolak'], 400);
            }
            
            $user->delete();

            // [LOG]
            $this->safeLog('reject_user', "Menolak pendaftaran user: {$email}", null, ['deleted_email' => $email]);

            return response()->json(['message' => 'Pendaftaran user ditolak']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menolak user', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Simpan data sebelum dihapus untuk keperluan log
            $email = $user->email;
            $role = $user->role;

            // Database Transaction hanya untuk proses hapus data inti
            DB::transaction(function () use ($user) {
                // Update status dinas jika ada
                if ($user->dinas) {
                    $user->dinas->update(['status' => 'belum_terdaftar']);
                }
                // Hapus User
                $user->delete();
            });

            // [LOG] Log dilakukan DILUAR transaksi delete agar jika log error, user TETAP TERHAPUS
            // Ini mencegah Error 500 di frontend
            $this->safeLog(
                'delete_user', 
                "Menghapus user {$role}: {$email}", 
                null, 
                ['deleted_email' => $email, 'role' => $role]
            );

            return response()->json(['message' => 'User berhasil dihapus']);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        } catch (Throwable $e) {
            // Catat error asli ke file log server
            Log::error("GAGAL HAPUS USER: " . $e->getMessage());
            
            return response()->json([
                'message' => 'Gagal menghapus user', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createPusdatin(Request $request){
        try {
            // 1. Validasi Input
            $validated = $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'name' => 'nullable|string|max:255',
                'nomor_telepon' => 'nullable|string|max:20',
            ]);
            
            // 2. Simpan ke Database
            $user = User::create([
                'name' => $validated['name'] ?? null,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'nomor_telepon' => $validated['nomor_telepon'] ?? null,
                'role' => 'pusdatin',
                'is_active' => true,
            ]);

            // 3. Catat Log (Gunakan safeLog agar aman)
            $this->safeLog('create_pusdatin', "Membuat akun Pusdatin baru: {$user->email} ({$user->name})", $user);
            
            return response()->json([
                'message' => 'Akun Pusdatin berhasil dibuat',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error("GAGAL CREATE PUSDATIN: " . $e->getMessage());
            return response()->json(['message' => 'Gagal membuat akun Pusdatin', 'error' => $e->getMessage()], 400);
        }
    }

    // =================================================================
    // HELPER: SAFE LOGGING (ANTI ERROR 500)
    // =================================================================
    
    /**
     * Mencatat log tanpa melempar error ke user jika gagal.
     * Jika tabel activity_logs error, fungsi ini hanya akan menulis ke laravel.log
     */
    private function safeLog($action, $description, $subject = null, $properties = null)
    {
        try {
            // Pastikan class ada & bisa diakses
            if (class_exists(ActivityLog::class)) {
                ActivityLog::record($action, $description, $subject, $properties);
            }
        } catch (\Exception $e) {
            // PENTING: Jika log gagal, jangan crash aplikasi. Cukup catat errornya di backend.
            // Cek file storage/logs/laravel.log untuk melihat pesan ini jika tabel log kosong.
            Log::error("ACTIVITY LOG ERROR [{$action}]: " . $e->getMessage());
        }
    }

    // =================================================================
    // DATA FETCHING (SYSTEM LOGS & USERS)
    // =================================================================

    public function getSystemLogs(Request $request)
    {
        try {
            $limit = $request->input('limit', 50);
            $page = $request->input('page', 1);

            // Cek apakah tabel activity_logs ada (cegah error 500 di frontend jika migrasi belum jalan)
            try {
                $logs = ActivityLog::with(['user.dinas'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($limit, ['*'], 'page', $page);
            } catch (\Exception $e) {
                // Jika tabel belum dimigrasi/error query, kembalikan array kosong agar UI tidak rusak
                Log::error("GET SYSTEM LOGS ERROR: " . $e->getMessage());
                return response()->json([
                    'data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1
                ]);
            }

            $transformed = $logs->getCollection()->map(function ($log) {
                $actorName = 'System / Deleted User';
                $actorRole = 'system';
                $email = '-';

                if ($log->user) {
                    $email = $log->user->email;
                    $actorRole = $log->user->role; 

                    if ($log->user->role === 'admin') $actorName = 'Administrator';
                    elseif ($log->user->role === 'pusdatin') $actorName = 'Tim Pusdatin';
                    elseif ($log->user->dinas) $actorName = $log->user->dinas->nama_dinas;
                    else $actorName = $log->user->email;
                }

                $targetStr = '-';
                // Ambil target dari relation subject
                if ($log->subject_type === 'App\Models\User' && $log->subject) {
                    $targetStr = $log->subject->dinas ? $log->subject->dinas->nama_dinas : $log->subject->email;
                } 
                // Atau ambil dari backup properties jika subject sudah dihapus
                elseif (isset($log->properties['deleted_email'])) {
                    $targetStr = $log->properties['deleted_email'];
                }

                return [
                    'id' => $log->id,
                    'user' => $actorName,
                    'email' => $email,
                    'role' => $actorRole,
                    'action' => $this->formatLogAction($log->action),
                    'target' => $targetStr,
                    'time' => $log->created_at->toISOString(),
                    'status' => 'success',
                    'catatan' => $log->description,
                    'year' => $log->created_at->format('Y'),
                ];
            });

            return response()->json([
                'data' => $transformed,
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage()
            ]);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function listUsers()
    {
        $users = User::with('dinas')->get();
        return response()->json($users);
    }

    public function showUser(Request $request, $role, $status){
        $perPage = $request->input('per_page', 15);
        
        if ($role === 'all') {
            $isActive = ($status === 'approved' || $status === '1' || $status === 1);
            $data = $this->queryUsers($request, null, $isActive, $perPage);
            return response()->json($this->transformUserData($data));
        }

        if ($role === 'admin') {
            $isActive = ($status === 'approved' || $status === '1' || $status === 1);
            $data = User::where('role', 'admin')
                ->where('is_active', $isActive)
                ->when($request->search, function($q, $s) { $q->where('email', 'like', "%{$s}%"); })
                ->orderBy('created_at', 'desc')->paginate($perPage);

            $data->getCollection()->transform(function ($user) {
                $user->province_name = '-'; $user->regency_name = '-'; $user->display_name = 'Administrator';
                return $user;
            });
            return response()->json($data);
        }

        if($role !='pusdatin'){
            $data = User::with('dinas.region.parent')
            ->whereNotIn('role', ['admin', 'pusdatin'])
            ->where('role', $role == "kabupaten" ? "kabupaten/kota" : $role)  
            ->where('is_active', $status)
            ->when($request->search, function($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhereHas('dinas', function($dq) use ($search) {
                          $dq->where('nama_dinas', 'like', "%{$search}%")
                             ->orWhere('kode_dinas', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('created_at', 'desc')->paginate($perPage);
            return response()->json($this->transformUserData($data));

        } elseif($role=='pusdatin'){
            $isActive = $status === 'approved' || $status === true || $status === '1';
            $data = User::where('role', 'pusdatin')
            ->where('is_active', $isActive)
            ->when($request->search, function($q, $s) { $q->where('email', 'like', "%{$s}%"); })
            ->orderBy('created_at', 'desc')->paginate($perPage);
            
            $data->getCollection()->transform(function ($user) {
                $user->province_name = '-'; $user->regency_name = '-'; $user->display_name = 'Tim Pusdatin';
                return $user;
            });
            return response()->json($data);
        }
    }

    private function transformUserData($data) {
        $data->getCollection()->transform(function ($user) {
            $user->province_name = '-'; $user->regency_name = '-'; $user->display_name = $user->email;
            if ($user->role === 'admin') $user->display_name = 'Administrator';
            elseif ($user->role === 'pusdatin') $user->display_name = 'Tim Pusdatin';
            elseif ($user->dinas) {
                $user->display_name = $user->dinas->nama_dinas;
                if ($user->dinas->region) {
                    $region = $user->dinas->region;
                    if ($region->type === 'provinsi') $user->province_name = $region->nama_wilayah ?? $region->nama_region;
                    else {
                        $user->regency_name = $region->nama_wilayah ?? $region->nama_region;
                        $user->province_name = $region->parent ? ($region->parent->nama_wilayah ?? $region->parent->nama_region) : null;
                    }
                }
            }
            return $user;
        });
        return $data;
    }

    private function queryUsers(Request $request, $role, $isActive, $perPage) {
        $query = User::with('dinas.region.parent')->where('is_active', $isActive)
            ->when($request->search, function($q, $search) {
                return $q->where(function($subQ) use ($search) {
                    $subQ->where('email', 'like', "%{$search}%")
                         ->orWhereHas('dinas', function($dq) use ($search) {
                             $dq->where('nama_dinas', 'like', "%{$search}%")
                                ->orWhere('kode_dinas', 'like', "%{$search}%");
                         });
                });
            })->orderBy('created_at', 'desc');
        return $query->paginate($perPage);
    }

    public function trackingHistoryPusdatin(Request $request, $year = null, $pusdatin_id = null) {
        return $this->getSystemLogs($request);
    }

    private function formatLogAction($actionSlug) {
        $map = [
            'create_pusdatin' => 'Membuat Akun Pusdatin',
            'delete_user' => 'Menghapus User',
            'approve_user' => 'Menyetujui User',
            'reject_user' => 'Menolak User',
            'login' => 'Login', 'logout' => 'Logout',
        ];
        return $map[$actionSlug] ?? ucwords(str_replace('_', ' ', $actionSlug));
    }
}