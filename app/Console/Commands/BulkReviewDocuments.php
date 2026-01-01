<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Submission;
use App\Services\ReviewService;

class BulkReviewDocuments extends Command
{
    protected $signature = 'review:bulk 
                            {type : Document type (iklh, ringkasanEksekutif, laporanUtama, lampiran)}
                            {status : Status to set (approved, needs_revision)}
                            {--year= : Filter by year}
                            {--catatan= : Admin notes}';

    protected $description = 'Bulk review documents for testing';

    public function handle(ReviewService $reviewService)
    {
        $type = $this->argument('type');
        $status = $this->argument('status');
        $year = $this->option('year');
        $catatan = $this->option('catatan') ?? 'Bulk reviewed for testing';

        // Map camelCase to relationship names
        $typeMap = [
            'iklh' => 'iklh',
            'ringkasanEksekutif' => 'ringkasanEksekutif',
            'laporanUtama' => 'laporanUtama',
            'lampiran' => 'lampiran',
        ];

        if (!isset($typeMap[$type])) {
            $this->error("Invalid document type. Use: iklh, ringkasanEksekutif, laporanUtama, lampiran");
            return 1;
        }

        $relation = $typeMap[$type];

        // Get submissions with finalized documents
        $query = Submission::with($relation)
            ->whereHas($relation, function ($q) {
                $q->where('status', 'finalized');
            });

        if ($year) {
            $query->where('tahun', $year);
        }

        $submissions = $query->get();

        if ($submissions->isEmpty()) {
            $this->warn("No finalized {$type} documents found.");
            return 0;
        }

        $this->info("Found {$submissions->count()} documents to review...");

        $bar = $this->output->createProgressBar($submissions->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($submissions as $submission) {
            try {
                $reviewService->evaluateDocument(
                    $submission,
                    $relation,
                    [
                        'status' => $status,
                        'catatan_admin' => $catatan
                    ],
                    1 // Admin user ID
                );
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for submission {$submission->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        
        $this->info("✓ Success: {$success}");
        if ($failed > 0) {
            $this->error("✗ Failed: {$failed}");
        }

        return 0;
    }
}
