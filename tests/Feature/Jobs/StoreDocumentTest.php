<?php

namespace Tests\Feature\Jobs;

use App\Jobs\StoreDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use RuntimeException;
use Tests\TestCase;

class StoreDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creates_document_stores_file_and_attaches_signers(): void
    {
        Storage::fake('local');
        Prism::fake([TextResponseFake::make()->withText('Resumen de prueba')]);

        $administrator = User::factory()->administrator()->create();
        $firstSigner = User::factory()->signer()->create();
        $secondSigner = User::factory()->signer()->create();

        $temporaryPath = 'temp/contrato.pdf';
        Storage::disk('local')->put($temporaryPath, 'contenido del documento');

        $hash = hash_file('sha256', Storage::disk('local')->path($temporaryPath));

        StoreDocument::dispatchSync(
            temporaryPath: $temporaryPath,
            originalName: 'contrato.pdf',
            hash: $hash,
            size: 21,
            uploadedBy: $administrator->id,
            signers: [$firstSigner->id, $secondSigner->id],
        );

        $document = Document::firstOrFail();

        $this->assertSame('contrato.pdf', $document->name);
        $this->assertSame($hash, $document->hash);
        $this->assertSame(21, $document->size);
        $this->assertSame($administrator->id, $document->uploaded_by);
        $this->assertNull($document->summary);
        $this->assertStringStartsWith('documents/', $document->path);
        Storage::disk('local')->assertExists($document->path);

        $this->assertDatabaseHas('document_user', [
            'document_id' => $document->id,
            'user_id' => $firstSigner->id,
            'signed' => false,
            'signature_order' => 1,
        ]);
        $this->assertDatabaseHas('document_user', [
            'document_id' => $document->id,
            'user_id' => $secondSigner->id,
            'signed' => false,
            'signature_order' => 2,
        ]);

        Storage::disk('local')->assertMissing($temporaryPath);
    }

    public function test_job_generates_and_stores_the_document_summary(): void
    {
        Storage::fake('local');
        $prism = Prism::fake([TextResponseFake::make()->withText('Resumen de prueba')]);

        $administrator = User::factory()->administrator()->create();

        $temporaryPath = 'temp/contrato.pdf';
        Storage::disk('local')->put($temporaryPath, file_get_contents(base_path('tests/Fixtures/contrato.pdf')));

        $hash = hash_file('sha256', Storage::disk('local')->path($temporaryPath));

        StoreDocument::dispatchSync(
            temporaryPath: $temporaryPath,
            originalName: 'contrato.pdf',
            hash: $hash,
            size: 634,
            uploadedBy: $administrator->id,
            signers: [],
        );

        $this->assertSame('Resumen de prueba', Document::firstOrFail()->summary);
        $prism->assertCallCount(1);
    }

    public function test_failed_job_deletes_the_temporary_file(): void
    {
        Storage::fake('local');

        $temporaryPath = 'temp/roto.pdf';
        Storage::disk('local')->put($temporaryPath, 'contenido');

        $job = new StoreDocument(
            temporaryPath: $temporaryPath,
            originalName: 'roto.pdf',
            hash: hash('sha256', 'contenido'),
            size: 7,
            uploadedBy: User::factory()->administrator()->create()->id,
            signers: [],
        );

        $job->failed(new RuntimeException('Fallo simulado'));

        Storage::disk('local')->assertMissing($temporaryPath);
    }
}
