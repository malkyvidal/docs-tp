<?php

namespace App\Http\Controllers\Documents;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\StoreDocumentsRequest;
use App\Jobs\StoreDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DocumentController extends Controller
{
    /**
     * List the documents visible to the current user.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $documents = $user->isAdministrator()
            ? $this->documentsForAdministrator()
            : $this->documentsForSigner($user);

        return Inertia::render('documents/index', [
            'documents' => $documents,
        ]);
    }

    /**
     * All documents with their signers and overall signing status.
     *
     * @return array<int, array<string, mixed>>
     */
    private function documentsForAdministrator(): array
    {
        return Document::query()
            ->with('signers')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Document $document): array {
                $signers = $document->signers;
                $pendingSigners = $signers->filter(
                    fn (User $signer) => ! (bool) $signer->pivot->signed,
                );

                return [
                    'id' => $document->id,
                    'uuid' => $document->uuid,
                    'name' => $document->name,
                    'summary' => $document->summary,
                    'created_at' => $document->created_at?->format('d/m/Y'),
                    'status' => $signers->isNotEmpty() && $pendingSigners->isEmpty()
                        ? 'signed'
                        : 'pending',
                    'signers' => $signers->map(fn (User $signer) => [
                        'id' => $signer->id,
                        'name' => $signer->name,
                        'signed' => (bool) $signer->pivot->signed,
                    ])->values(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * The documents the given signer can sign or has already signed.
     *
     * @return array<int, array<string, mixed>>
     */
    private function documentsForSigner(User $user): array
    {
        return $user->documents()
            ->with(['signers' => fn ($query) => $query->orderByPivot('signature_order')])
            ->orderByDesc('documents.created_at')
            ->get()
            ->filter(fn (Document $document) => $document->isSignableBy($user) || $document->isSignedBy($user))
            ->map(function (Document $document) use ($user): array {
                $assignment = $document->signers->firstWhere('id', $user->id);
                $signed = $document->isSignedBy($user);

                return [
                    'id' => $document->id,
                    'uuid' => $document->uuid,
                    'name' => $document->name,
                    'summary' => $document->summary,
                    'created_at' => $document->created_at?->format('d/m/Y'),
                    'status' => $signed ? 'signed' : 'pending',
                    'signature_order' => $assignment?->pivot->signature_order,
                    'total_signers' => $document->signers->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Download the signed PDF document.
     *
     * Signers may only download documents they have already signed.
     * Administrators may download any document that is fully signed.
     */
    public function download(Request $request, Document $document): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();

        $hasSigned = $document->isSignedBy($user);

        abort_unless(
            ($user->isAdministrator() && $document->isFullySigned()) || $hasSigned,
            403,
        );

        return Storage::disk('local')->download($document->path, $document->name);
    }

    /**
     * Show the document upload form.
     */
    public function create(): Response
    {
        return Inertia::render('admin/documents/create', [
            'signers' => User::query()
                ->where('role', UserRole::Signer)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Queue the uploaded documents for asynchronous processing.
     */
    public function store(StoreDocumentsRequest $request): RedirectResponse
    {
        foreach ($request->documents() as $data) {
            $file = $data['file'];

            $temporaryPath = $file->store('temp', 'local');

            if ($temporaryPath === false) {
                throw new RuntimeException('No se pudo guardar el archivo temporal del documento.');
            }

            $disk = Storage::disk('local');

            $hash = hash_file('sha256', $disk->path($temporaryPath));

            if ($hash === false) {
                $disk->delete($temporaryPath);
                throw new RuntimeException('No se pudo calcular el hash del documento.');
            }

            $size = $file->getSize();

            StoreDocument::dispatch(
                temporaryPath: $temporaryPath,
                originalName: $file->getClientOriginalName(),
                hash: $hash,
                size: $size === false ? 0 : $size,
                uploadedBy: $request->user()->id,
                signers: array_values(array_unique($data['signers'])),
            )->afterCommit();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Documentos encolados para procesamiento.')]);

        return to_route('documents.create');
    }
}
