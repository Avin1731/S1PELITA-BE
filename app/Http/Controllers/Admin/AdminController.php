<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Dinas;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function approveUser($id)
{
    try {
        DB::transaction(function () use ($id) {
            $user = User::findOrFail($id);
            $dinas = $user->dinas()->lockForUpdate()->first();

            if ($dinas->status === 'terdaftar') {
                throw new \Exception('User tidak bisa diaktifkan, dinas sudah Terdaftar.');
            }

            $user->update(['is_active' => true]);
            $dinas->update(['status' => 'terdaftar']);
        });

        return response()->json(['message' => 'Berhasil Aktivasi User']);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Gagal aktivasi user',
            'error' => $e->getMessage(),
        ], 400);
    }
}

    public function rejectUser($id)
    {

        $user = User::findOrFail($id);
        if($user->is_active){
            return response()->json(['message' => 'User sudah diaktifkan, tidak bisa ditolak'], 400);
        }
        $user->delete();
        return response()->json(['message' => 'Pendaftaran user ditolak']);
    }
    public function deleteUser($id)
    {
        try{

            DB::transaction(function () use ($id) {
                
                $user = User::findOrFail($id);
                if ($user->dinas) {
                $user->dinas->update(['status' => 'belum_terdaftar']);
            }
                $user->delete();
            });
            return response()->json(['message' => 'User deleted successfully']);
        }catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'User not found'], 404);
        } catch (Throwable $e) {
        return response()->json(['message' => 'Failed to delete user', 'error' => $e->getMessage()], 500);
    }
    

}
    public function listUsers()
    {
        $users = User::with('dinas')->get();
        return response()->json($users);
    }
    public function createPusdatin(Request $request){
        try{

            $validated=$request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);
            
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'pusdatin',
                'is_active' => true,
            ]);
            
            return response()->json([
                'message' => 'Akun Pusdatin berhasil dibuat',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membuat akun Pusdatin', 'error' => $e->getMessage()], 400);
        }
    }
}