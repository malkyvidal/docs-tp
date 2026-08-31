<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SignatureController extends Controller
{
    /**
     * Record the current user's signature on the given document.
     */
    public function store(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        abort_unless($document->isSignableBy($user), 403);

        $document->signers()->updateExistingPivot($user->id, [
            'signed' => true,
            'signed_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Documento firmado correctamente.')]);

        return back();
    }
}
