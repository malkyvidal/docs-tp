<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
    }

    public function test_signer_only_sees_assigned_documents(): void
    {
        $signer = User::factory()->signer()->create();
        $assigned = $this->createDocument('Asignado');
        $this->createDocument('No asignado');
        $assigned->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->actingAs($signer)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('documents/index')
                ->has('documents', 1)
                ->where('documents.0.name', 'Asignado'));
    }

    public function test_signer_sees_documents_ready_to_sign_and_signed(): void
    {
        $signer = User::factory()->signer()->create();
        $signed = $this->createDocument('Firmado', now()->subDay());
        $pending = $this->createDocument('Pendiente');

        $this->markSigned($signed, $signer);

        $pending->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->actingAs($signer)
            ->get(route('documents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 2)
                ->where('documents.0.name', 'Pendiente')
                ->where('documents.0.status', 'pending')
                ->where('documents.0.signature_order', 1)
                ->where('documents.0.total_signers', 1)
                ->where('documents.1.name', 'Firmado')
                ->where('documents.1.status', 'signed'));
    }

    public function test_signer_does_not_see_documents_when_it_is_not_their_turn(): void
    {
        $first = User::factory()->signer()->create();
        $second = User::factory()->signer()->create();
        $document = $this->createDocument('Contrato');
        $document->signers()->attach($first->id, ['signature_order' => 1]);
        $document->signers()->attach($second->id, ['signature_order' => 2]);

        $this->actingAs($second)
            ->get(route('documents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 0));

        $document->signers()->updateExistingPivot($first->id, [
            'signed' => true,
            'signed_at' => now(),
        ]);

        $this->actingAs($second)
            ->get(route('documents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 1)
                ->where('documents.0.name', 'Contrato')
                ->where('documents.0.signature_order', 2)
                ->where('documents.0.total_signers', 2));
    }

    public function test_signer_without_documents_sees_an_empty_list(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('documents', 0));
    }

    public function test_administrator_sees_all_documents(): void
    {
        $administrator = User::factory()->administrator()->create();
        $signer = User::factory()->signer()->create();
        $this->createDocument('Primero');
        $second = $this->createDocument('Segundo');
        $second->signers()->attach($signer->id, ['signature_order' => 1]);

        $this->actingAs($administrator)
            ->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('documents/index')
                ->has('documents', 2));
    }

    public function test_administrator_sees_document_as_signed_when_everyone_signed(): void
    {
        $administrator = User::factory()->administrator()->create();
        $document = $this->createDocument('Contrato');
        $first = User::factory()->signer()->create();
        $second = User::factory()->signer()->create();
        $this->markSigned($document, $first, 1);
        $this->markSigned($document, $second, 2);

        $this->actingAs($administrator)
            ->get(route('documents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.0.status', 'signed')
                ->where('documents.0.signers.0.signed', true)
                ->where('documents.0.signers.1.signed', true));
    }

    public function test_administrator_sees_document_as_pending_with_pending_signers(): void
    {
        $administrator = User::factory()->administrator()->create();
        $document = $this->createDocument('Contrato');
        $signedSigner = User::factory()->signer()->create();
        $pendingSigner = User::factory()->signer()->create();
        $this->markSigned($document, $signedSigner, 1);
        $document->signers()->attach($pendingSigner->id, ['signature_order' => 2]);

        $this->actingAs($administrator)
            ->get(route('documents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.0.status', 'pending')
                ->where('documents.0.signers.0.signed', true)
                ->where('documents.0.signers.0.name', $signedSigner->name)
                ->where('documents.0.signers.1.signed', false)
                ->where('documents.0.signers.1.name', $pendingSigner->name));
    }

    public function test_administrator_sees_document_without_signers_as_pending(): void
    {
        $administrator = User::factory()->administrator()->create();
        $this->createDocument('Sin firmantes');

        $this->actingAs($administrator)
            ->get(route('documents.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('documents.0.status', 'pending')
                ->where('documents.0.signers', []));
    }

    private function createDocument(string $name, ?DateTimeInterface $createdAt = null): Document
    {
        $document = Document::create([
            'name' => $name,
            'hash' => hash('sha256', $name.fake()->unique()->uuid()),
            'summary' => "Resumen de $name",
        ]);

        if ($createdAt !== null) {
            $document->created_at = $createdAt;
            $document->save();
        }

        return $document;
    }

    private function markSigned(Document $document, User $signer, int $order = 1): void
    {
        $document->signers()->attach($signer->id, [
            'signature_order' => $order,
            'signed' => true,
            'signed_at' => now(),
        ]);
    }
}
