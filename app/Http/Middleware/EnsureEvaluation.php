<?php

namespace App\Http\Middleware;

use App\Models\Pusdatin\PenilaianPenghargaan;
use App\Models\Pusdatin\PenilaianSLHD;
use App\Models\Pusdatin\Validasi_1;
use App\Models\Pusdatin\Validasi_2;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEvaluation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    protected $logService;
    public function __construct(\App\Services\LogService $logService)
    {
        $this->logService = $logService;
    }
    public function handle(Request $request, Closure $next, $activity, $type): Response
    {
        $year=$request->route('year');
        $table = match($type){
            'penilaian_slhd' => PenilaianSLHD::class,
            'penilaian_penghargaan' => PenilaianPenghargaan::class,
            'validasi_1'=>Validasi_1::class,
            'validasi_2'=>Validasi_2::class,
            default => throw new \InvalidArgumentException("Invalid evaluation type"),
        };
        $stage=(new $table)->getTable();
        $existing=$table::where(['year'=>$year,'is_finalized'=>true])->exists();
        if($existing ){
            $this->logService->log([
                'year' => $year,
                'actor_id' => $request->user()->id,
                'stage' => $stage,
                'activity_type' => $activity,
                'document_type' => null,
                'status' => 'failed',
                'catatan' => "Gagal $activity pada $type untuk tahun $year karena sudah difinalisasi.",
            ]);
            
            return response()->json([
                'message' => "$type untuk tahun $year sudah difinalisasi, tidak dapat unggah ulang."
            ], 403);
        }




        return $next($request);
    }
}
