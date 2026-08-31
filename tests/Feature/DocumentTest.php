<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_is_created_with_a_unique_uuid(): void
    {
        $first = Document::create([
            'name' => 'Contrato de arrendamiento',
            'hash' => hash('sha256', 'contenido-uno'),
        ]);
        $second = Document::create([
            'name' => 'Nómina',
            'hash' => hash('sha256', 'contenido-dos'),
        ]);

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $first->uuid,
        );
        $this->assertNotSame($first->uuid, $second->uuid);
        $this->assertSame('Contrato de arrendamiento', $first->fresh()->name);
        $this->assertSame(hash('sha256', 'contenido-uno'), $first->fresh()->hash);
    }

    public function test_document_summary_is_mass_assignable_and_nullable(): void
    {
        $document = Document::create([
            'name' => 'Contrato de arrendamiento',
            'hash' => hash('sha256', 'contenido-uno'),
            'summary' => 'Resumen del contrato',
        ]);

        $this->assertSame('Resumen del contrato', $document->fresh()->summary);
        $this->assertNull(Document::create([
            'name' => 'Nómina',
            'hash' => hash('sha256', 'contenido-dos'),
        ])->fresh()->summary);
    }

    public function test_signers_are_returned_in_signature_order(): void
    {
        $document = $this->createDocument();
        $second = User::factory()->create();
        $first = User::factory()->create();

        $document->signers()->attach($first->id, ['signature_order' => 1]);
        $document->signers()->attach($second->id, ['signature_order' => 2]);

        $ordered = $document->signersInOrder();

        $this->assertSame([$first->id, $second->id], $ordered->pluck('id')->all());
    }

    public function test_signing_a_document_records_signed_flag_and_date(): void
    {
        $document = $this->createDocument();
        $signer = User::factory()->create();

        $document->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->assertFalse($document->isSignedBy($signer));
        $this->assertNull($document->signedAtFor($signer));

        $document->signers()->updateExistingPivot($signer->id, [
            'signed' => true,
            'signed_at' => now(),
        ]);

        $this->assertTrue($document->isSignedBy($signer));
        $this->assertNotNull($document->signedAtFor($signer));
    }

    public function test_document_is_fully_signed_only_when_every_signer_signed(): void
    {
        $document = $this->createDocument();
        $signedSigner = User::factory()->create();
        $pendingSigner = User::factory()->create();

        $document->signers()->attach($signedSigner->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);
        $document->signers()->attach($pendingSigner->id, [
            'signature_order' => 2,
        ]);

        $this->assertFalse($document->isFullySigned());

        $document->signers()->updateExistingPivot($pendingSigner->id, [
            'signed' => true,
            'signed_at' => now(),
        ]);

        $this->assertTrue($document->isFullySigned());
    }

    public function test_document_without_signers_is_not_fully_signed(): void
    {
        $this->assertFalse($this->createDocument()->isFullySigned());
    }

    public function test_a_signer_cannot_be_attached_twice_to_the_same_document(): void
    {
        $document = $this->createDocument();
        $signer = User::factory()->create();

        $document->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->expectException(QueryException::class);

        $document->signers()->attach($signer->id, ['signature_order' => 2]);
    }

    public function test_signature_order_must_be_unique_per_document(): void
    {
        $document = $this->createDocument();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $document->signers()->attach($first->id, ['signature_order' => 1]);

        $this->expectException(QueryException::class);

        $document->signers()->attach($second->id, ['signature_order' => 1]);
    }

    public function test_deleting_a_document_detaches_its_signers(): void
    {
        $document = $this->createDocument();
        $signer = User::factory()->create();

        $document->signers()->attach($signer->id, ['signature_order' => 1]);

        $document->delete();

        $this->assertDatabaseMissing('document_user', [
            'document_id' => $document->id,
            'user_id' => $signer->id,
        ]);
    }

    public function test_document_is_signable_by_an_assigned_pending_signer(): void
    {
        $document = $this->createDocument();
        $signer = User::factory()->create();
        $document->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->assertTrue($document->isSignableBy($signer));
    }

    public function test_document_is_not_signable_by_a_user_who_is_not_assigned(): void
    {
        $document = $this->createDocument();
        $signer = User::factory()->create();

        $this->assertFalse($document->isSignableBy($signer));
    }

    public function test_document_is_not_signable_by_a_signer_who_already_signed(): void
    {
        $document = $this->createDocument();
        $signer = User::factory()->create();
        $document->signers()->attach($signer->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);

        $this->assertFalse($document->isSignableBy($signer));
    }

    public function test_document_is_not_signable_by_a_signer_whose_predecessors_have_not_signed(): void
    {
        $document = $this->createDocument();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $document->signers()->attach($first->id, ['signature_order' => 1]);
        $document->signers()->attach($second->id, ['signature_order' => 2]);

        $this->assertFalse($document->isSignableBy($second));
    }

    public function test_document_is_signable_by_a_signer_once_the_predecessors_have_signed(): void
    {
        $document = $this->createDocument();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $document->signers()->attach($first->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);
        $document->signers()->attach($second->id, ['signature_order' => 2]);

        $this->assertTrue($document->isSignableBy($second));
    }

    public function test_user_documents_relationship(): void
    {
        $user = User::factory()->create();
        $document = $this->createDocument();

        $document->signers()->attach($user->id, [
            'signature_order' => 1,
            'signed' => true,
            'signed_at' => now(),
        ]);

        $related = $user->documents()->first();

        $this->assertTrue($related->is($document));
        $this->assertSame(1, $related->pivot->signature_order);
        $this->assertTrue($related->pivot->signed);
        $this->assertNotNull($related->pivot->signed_at);
    }

    private function createDocument(): Document
    {
        return Document::create([
            'name' => 'Documento de prueba',
            'hash' => hash('sha256', fake()->unique()->uuid()),
        ]);
    }
}
