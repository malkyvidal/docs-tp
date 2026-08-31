<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $hash
 * @property string $path
 * @property int $size
 * @property string|null $summary
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'hash', 'path', 'size', 'uploaded_by', 'summary'])]
class Document extends Model
{
    use HasUuids;

    /**
     * The UUID column that uniquely identifies the document.
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * The administrator that uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * The users that sign this document.
     */
    public function signers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(DocumentUser::class)
            ->withPivot(['signed', 'signed_at', 'signature_order'])
            ->withTimestamps();
    }

    /**
     * Get the signers ordered by their signature order.
     */
    public function signersInOrder()
    {
        return $this->signers()->orderByPivot('signature_order')->get();
    }

    /**
     * Determine whether the given user can currently sign this document.
     *
     * A user can sign when they are assigned to the document, have not signed
     * yet, and every preceding signer has already signed.
     */
    public function isSignableBy(User $user): bool
    {
        $signers = $this->signers;

        $assignment = $signers->firstWhere('id', $user->id);

        if ($assignment === null || (bool) $assignment->pivot->signed) {
            return false;
        }

        return $signers
            ->filter(fn (User $signer) => $signer->pivot->signature_order < $assignment->pivot->signature_order)
            ->every(fn (User $signer) => (bool) $signer->pivot->signed);
    }

    /**
     * Determine whether the given user has signed this document.
     */
    public function isSignedBy(User $user): bool
    {
        return $this->signers()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('signed', true)
            ->exists();
    }

    /**
     * Get the date on which the given user signed this document.
     */
    public function signedAtFor(User $user): ?Carbon
    {
        $signedAt = $this->signers()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('signed', true)
            ->value('document_user.signed_at');

        return $signedAt !== null ? Carbon::parse($signedAt) : null;
    }

    /**
     * Determine whether every signer has signed this document.
     */
    public function isFullySigned(): bool
    {
        return $this->signers()->exists() && $this->signers()->wherePivot('signed', false)->doesntExist();
    }
}
