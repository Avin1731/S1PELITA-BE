<?php

namespace App\Http\Controllers\Pusdatin;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeadlineController extends Controller
{
    /**
     * Get deadline submission untuk year tertentu (default current year)
     */
    public function index(Request $request, $year = null)
    {
        $year = $year ?? now()->year;

        $deadline = Deadline::with(['creator', 'updater'])
            ->byYear($year)
            ->byStage('submission')
            ->active()
            ->first();

        return response()->json([
            'year' => $year,
            'deadline' => $deadline->deadline_at ?? null,
            'catatan' => $deadline->catatan ?? null,
            'is_passed' => $deadline ? $deadline->isPassed() : null
        ]);
    }

    /**
     * Set/update deadline submission
     */
    public function setDeadline(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'deadline_at' => 'required|date|after:now',
            'catatan' => 'nullable|string',
        ], [
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'deadline_at.required' => 'Tanggal deadline wajib diisi.',
            'deadline_at.date' => 'Tanggal deadline harus berupa tanggal yang valid.',
            'deadline_at.after' => 'Tanggal deadline harus lebih dari waktu sekarang.',
        ]);

        DB::beginTransaction();
        try {
            // Update or create deadline untuk year dan stage ini
            // Hanya 1 record per year per stage, no history tracking needed
            $deadline = Deadline::updateOrCreate(
                [
                    'year' => $request->year,
                    'stage' => 'submission',
                ],
                [
                    'deadline_at' => $request->deadline_at,
                    'is_active' => true,
                    'catatan' => $request->catatan,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Deadline submission berhasil diatur.',
                'data' => $deadline->load(['creator', 'updater'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengatur deadline.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete/deactivate deadline
     */
    public function deleteDeadline(Request $request, $id)
    {
        $deadline = Deadline::findOrFail($id);

        // Pastikan hanya bisa hapus deadline submission
        if ($deadline->stage !== 'submission') {
            return response()->json([
                'message' => 'Hanya deadline submission yang dapat dihapus.'
            ], 403);
        }

        $deadline->update([
            'is_active' => false,
            'updated_by' => $request->user()->id
        ]);

        return response()->json([
            'message' => 'Deadline berhasil dinonaktifkan.'
        ]);
    }

    /**
     * Get active deadline submission untuk year tertentu
     */
    public function getActiveDeadline($year)
    {
        $deadline = Deadline::byYear($year)
            ->byStage('submission')
            ->active()
            ->first();

        if (!$deadline) {
            return response()->json([
                'message' => 'Tidak ada deadline submission aktif untuk tahun ini.'
            ], 404);
        }

        return response()->json([
            'data' => $deadline,
            'is_passed' => $deadline->isPassed()
        ]);
    }
}
