<?php

namespace Tests\Feature\Admin;

use App\Jobs\StoreDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrator;

    protected User $firstSigner;

    protected User $secondSigner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->administrator()->create();
        $this->firstSigner = User::factory()->signer()->create();
        $this->secondSigner = User::factory()->signer()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('documents.create'))->assertRedirect(route('login'));
        $this->post(route('documents.store'), [])->assertRedirect(route('login'));
    }

    public function test_signers_cannot_access_the_document_upload(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->get(route('documents.create'))
            ->assertForbidden();

        $this->actingAs($signer)
            ->post(route('documents.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_administrators_can_view_the_document_upload_page(): void
    {
        $names = collect([$this->firstSigner->name, $this->secondSigner->name])
            ->sort()
            ->values();

        $this->actingAs($this->administrator)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/documents/create')
                ->has('signers', 2)
                ->where('signers.0.name', $names[0])
                ->where('signers.1.name', $names[1]));
    }

    public function test_administrator_can_upload_documents_with_signers(): void
    {
        Queue::fake();

        Storage::fake('local');

        $file = UploadedFile::fake()->create('contrato.pdf', 200, 'application/pdf');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => $file,
                        'signers' => [$this->firstSigner->id, $this->secondSigner->id],
                    ],
                ],
            ])
            ->assertRedirect(route('documents.create'));

        Queue::assertPushed(StoreDocument::class, function (StoreDocument $job) use ($file) {
            return $job->originalName === 'contrato.pdf'
                && $job->size === $file->getSize()
                && $job->uploadedBy === $this->administrator->id
                && $job->signers === [$this->firstSigner->id, $this->secondSigner->id]
                && hash_file('sha256', Storage::disk('local')->path($job->temporaryPath)) === $job->hash;
        });

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame(1, count(Storage::disk('local')->allFiles('temp')));
    }

    public function test_administrator_can_upload_multiple_documents_at_once(): void
    {
        Queue::fake();

        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->createWithContent('primero.pdf', 'contenido del primer documento'),
                        'signers' => [$this->firstSigner->id],
                    ],
                    [
                        'file' => UploadedFile::fake()->createWithContent('segundo.pdf', 'contenido del segundo documento'),
                        'signers' => [$this->secondSigner->id],
                    ],
                ],
            ])
            ->assertRedirect(route('documents.create'));

        Queue::assertPushed(StoreDocument::class, 2);

        $signersByDocument = Queue::pushed(StoreDocument::class)
            ->mapWithKeys(fn (StoreDocument $job) => [$job->originalName => $job->signers])
            ->all();

        $this->assertSame([$this->firstSigner->id], $signersByDocument['primero.pdf']);
        $this->assertSame([$this->secondSigner->id], $signersByDocument['segundo.pdf']);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_documents_must_be_pdfs(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('nota.txt', 100, 'text/plain'),
                        'signers' => [$this->firstSigner->id],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_documents_must_not_exceed_25_megabytes(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('pesado.pdf', 26 * 1024, 'application/pdf'),
                        'signers' => [$this->firstSigner->id],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_every_document_requires_at_least_one_signer(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                        'signers' => [],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.signers');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_documents_cannot_have_more_than_seven_signers(): void
    {
        Storage::fake('local');

        $signers = User::factory()->count(8)->signer()->create()->pluck('id')->all();

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                        'signers' => $signers,
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.signers');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_documents_cannot_have_duplicate_signers(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                        'signers' => [$this->firstSigner->id, $this->firstSigner->id],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.signers');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_signers_must_exist(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                        'signers' => [999999],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.signers.0');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_signers_must_have_the_signer_role(): void
    {
        Storage::fake('local');

        $adminAsSigner = User::factory()->administrator()->create();

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                        'signers' => [$adminAsSigner->id],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.signers.0');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_already_uploaded_name_is_rejected(): void
    {
        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->createWithContent('contrato.pdf', 'contenido del primer documento'),
                        'signers' => [$this->firstSigner->id, $this->secondSigner->id],
                    ],
                ],
            ])
            ->assertRedirect(route('documents.create'));

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->createWithContent('contrato.pdf', 'contenido diferente'),
                        'signers' => [$this->firstSigner->id, $this->secondSigner->id],
                    ],
                ],
            ])
            ->assertSessionHasErrors('documents.0.file');

        $this->assertDatabaseCount('documents', 1);
    }

    public function test_same_content_with_different_name_is_allowed(): void
    {
        Queue::fake();

        Storage::fake('local');

        $this->actingAs($this->administrator)
            ->post(route('documents.store'), [
                'documents' => [
                    [
                        'file' => UploadedFile::fake()->createWithContent('contrato_a.pdf', 'mismo contenido'),
                        'signers' => [$this->firstSigner->id],
                    ],
                    [
                        'file' => UploadedFile::fake()->createWithContent('contrato_b.pdf', 'mismo contenido'),
                        'signers' => [$this->secondSigner->id],
                    ],
                ],
            ])
            ->assertRedirect(route('documents.create'));

        Queue::assertPushed(StoreDocument::class, 2);
    }

    public function test_at_least_one_document_is_required(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('documents.store'), ['documents' => []])
            ->assertSessionHasErrors('documents');

        $this->assertDatabaseCount('documents', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'documents' => [
                [
                    'file' => UploadedFile::fake()->create('contrato.pdf', 100, 'application/pdf'),
                    'signers' => [$this->firstSigner->id, $this->secondSigner->id],
                ],
            ],
        ], $overrides);
    }
}
