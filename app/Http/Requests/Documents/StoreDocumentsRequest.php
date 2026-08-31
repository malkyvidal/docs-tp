<?php

namespace App\Http\Requests\Documents;

use App\Enums\UserRole;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreDocumentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.file' => ['required', File::types(['pdf'])->max('25mb')],
            'documents.*.signers' => ['required', 'array', 'min:1', 'max:7'],
            'documents.*.signers.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Signer->value),
            ],
        ];
    }

    /**
     * Get the custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.required' => 'Debes cargar al menos un documento.',
            'documents.min' => 'Debes cargar al menos un documento.',
            'documents.*.file.required' => 'Falta el archivo del documento.',
            'documents.*.file.mimes' => 'El documento debe ser un archivo PDF.',
            'documents.*.file.max' => 'El documento no debe pesar más de 25 MB.',
            'documents.*.signers.required' => 'Debes asignar al menos un firmante.',
            'documents.*.signers.min' => 'Debes asignar al menos un firmante.',
            'documents.*.signers.max' => 'Un documento no puede tener más de 7 firmantes.',
            'documents.*.signers.*.exists' => 'Uno de los firmantes no es válido.',
        ];
    }

    /**
     * Get the validated documents with their files and signers.
     *
     * @return array<int, array{file: UploadedFile, signers: array<int, int>}>
     */
    public function documents(): array
    {
        return $this->validated('documents');
    }

    /**
     * Reject documents whose name was already uploaded.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('documents', []) as $index => $data) {
                    $signers = $data['signers'] ?? [];

                    if (is_array($signers) && count($signers) !== count(array_unique($signers))) {
                        $validator->errors()->add(
                            "documents.$index.signers",
                            'Un documento no puede tener firmantes duplicados.',
                        );
                    }

                    $file = $this->file("documents.$index.file");

                    if ($file === null || ! $file->isValid()) {
                        continue;
                    }

                    $name = $file->getClientOriginalName();

                    if (Document::where('name', $name)->exists()) {
                        $validator->errors()->add(
                            "documents.$index.file",
                            'El documento "'.$name.'" ya fue cargado anteriormente.',
                        );
                    }
                }
            },
        ];
    }
}
