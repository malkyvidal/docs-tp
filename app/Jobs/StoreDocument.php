<?php

namespace App\Jobs;

use App\Agents\DocumentSummaryAgent;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StoreDocument implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [1, 5, 15];

    public int $uniqueFor = 3600;

    /**
     * Create a new job instance.
     *
     * @param  array<int, int>  $signers
     */
    public function __construct(
        public string $temporaryPath,
        public string $originalName,
        public string $hash,
        public int $size,
        public int $uploadedBy,
        public array $signers,
    ) {
    }

    /**
     * The unique lock used to prevent processing the same document twice.
     */
    public function uniqueId(): string
    {
        return $this->originalName;
    }

    /**
     * Execute the job.
     */
    public function handle(DocumentSummaryAgent $summaryAgent): void
    {
        $disk = Storage::disk('local');

        $finalPath = 'documents/'.Str::uuid().'.'.pathinfo($this->originalName, PATHINFO_EXTENSION);

        $disk->copy($this->temporaryPath, $finalPath);

        $document = Document::create([
            'name' => $this->originalName,
            'hash' => $this->hash,
            'path' => $finalPath,
            'size' => $this->size,
            'uploaded_by' => $this->uploadedBy,
        ]);

        $document->signers()->attach(
            collect($this->signers)
                ->mapWithKeys(fn (int $userId, int $order) => [
                    $userId => ['signature_order' => $order + 1],
                ])
                ->all(),
        );

        try {
            $summary = $summaryAgent->summarize($disk->path($finalPath));
            Log::info($summary);
            $document->update(['summary' => $summary]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar el resumen del documento', [
                'document_id' => $document->id,
                'name' => $this->originalName,
                'error' => $exception->getMessage(),
            ]);
        }

        $disk->delete($this->temporaryPath);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Storage::disk('local')->delete($this->temporaryPath);

        Log::error('No se pudo guardar el documento', [
            'name' => $this->originalName,
            'error' => $exception?->getMessage(),
        ]);
    }
}
