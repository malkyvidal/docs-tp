<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $document = $this->createDocument();

        $this->post(route('documents.sign', $document))->assertRedirect(route('login'));
    }

    public function test_signer_can_sign_a_document_assigned_to_them(): void
    {
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->actingAs($signer)
            ->post(route('documents.sign', $document))
            ->assertRedirect();

        $assignment = $document->signers()->wherePivot('user_id', $signer->id)->firstOrFail()->pivot;

        $this->assertTrue($assignment->signed);
        $this->assertNotNull($assignment->signed_at);
    }

    public function test_signer_cannot_sign_a_document_they_are_not_assigned_to(): void
    {
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();

        $this->actingAs($signer)
            ->post(route('documents.sign', $document))
            ->assertForbidden();
    }

    public function test_signer_cannot_sign_a_document_twice(): void
    {
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($signer->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);

        $this->actingAs($signer)
            ->post(route('documents.sign', $document))
            ->assertForbidden();
    }

    public function test_signer_cannot_sign_before_the_preceding_signers(): void
    {
        $first = User::factory()->signer()->create();
        $second = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($first->id, ['signature_order' => 1]);
        $document->signers()->attach($second->id, ['signature_order' => 2]);

        $this->actingAs($second)
            ->post(route('documents.sign', $document))
            ->assertForbidden();
    }

    public function test_signer_can_sign_once_the_preceding_signers_have_signed(): void
    {
        $first = User::factory()->signer()->create();
        $second = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($first->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);
        $document->signers()->attach($second->id, ['signature_order' => 2]);

        $this->actingAs($second)
            ->post(route('documents.sign', $document))
            ->assertRedirect();

        $this->assertTrue($document->signers()->wherePivot('user_id', $second->id)->firstOrFail()->pivot->signed);
    }

    public function test_signer_can_download_a_document_they_have_signed(): void
    {
        Storage::fake('local');
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($signer->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);
        Storage::disk('local')->put($document->path, 'contenido del documento');

        $this->actingAs($signer)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_signer_cannot_download_a_document_they_have_not_signed(): void
    {
        Storage::fake('local');
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($signer->id, ['signature_order' => 1]);
        Storage::disk('local')->put($document->path, 'contenido del documento');

        $this->actingAs($signer)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_unassigned_signer_cannot_download_a_document(): void
    {
        Storage::fake('local');
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();

        $this->actingAs($signer)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    public function test_administrator_can_download_a_fully_signed_document(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($signer->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);
        Storage::disk('local')->put($document->path, 'contenido del documento');

        $this->actingAs($administrator)
            ->get(route('documents.download', $document))
            ->assertOk();
    }

    public function test_administrator_cannot_download_a_pending_document(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $signer = User::factory()->signer()->create();
        $document = $this->createDocument();
        $document->signers()->attach($signer->id, ['signature_order' => 1]);
        Storage::disk('local')->put($document->path, 'contenido del documento');

        $this->actingAs($administrator)
            ->get(route('documents.download', $document))
            ->assertForbidden();
    }

    private function createDocument(): Document
    {
        return Document::create([
            'name' => 'Contrato de prueba.pdf',
            'hash' => hash('sha256', fake()->unique()->uuid()),
            'path' => 'documents/'.fake()->unique()->uuid().'.pdf',
        ]);
    }
}
