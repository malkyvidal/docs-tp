import { Head, Link, useForm } from '@inertiajs/react';
import { FileText, Trash2 } from 'lucide-react';
import { useState } from 'react';

import DocumentController from '@/actions/App/Http/Controllers/Documents/DocumentController';
import { DocumentDropzone } from '@/components/documents/dropzone';
import type { RejectedDocument } from '@/components/documents/dropzone';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type Signer = {
    id: number;
    name: string;
};

type Props = {
    signers: Signer[];
};

type DocumentDraft = {
    file: File;
    signers: number[];
};

const MAX_SIGNERS = 7;

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
}

export default function CreateDocument({ signers }: Props) {
    const { data, setData, post, processing, errors, progress, reset } =
        useForm<{
            documents: DocumentDraft[];
        }>({
            documents: [],
        });
    const [rejectedFiles, setRejectedFiles] = useState<RejectedDocument[]>([]);

    function addDocuments(files: File[]) {
        setRejectedFiles([]);
        setData((previous) => ({
            ...previous,
            documents: [
                ...previous.documents,
                ...files.map((file) => ({ file, signers: [] })),
            ],
        }));
    }

    function removeDocument(index: number) {
        setData((previous) => ({
            ...previous,
            documents: previous.documents.filter((_, i) => i !== index),
        }));
    }

    function toggleSigner(index: number, signerId: number) {
        setData((previous) => ({
            ...previous,
            documents: previous.documents.map((document, i) => {
                if (i !== index) {
                    return document;
                }

                const selected = document.signers.includes(signerId)
                    ? document.signers.filter((id) => id !== signerId)
                    : document.signers.length >= MAX_SIGNERS
                      ? document.signers
                      : [...document.signers, signerId];

                return { ...document, signers: selected };
            }),
        }));
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        post(DocumentController.store.url(), {
            onSuccess: () => {
                setRejectedFiles([]);
                reset();
            },
        });
    }

    return (
        <>
            <Head title="Subir documentos" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Subir documentos"
                    description="Carga los documentos PDF y asigna los firmantes de cada uno"
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-2">
                        <DocumentDropzone
                            onFiles={addDocuments}
                            onRejected={setRejectedFiles}
                            disabled={processing}
                        />
                        <InputError
                            className="mt-2"
                            message={errors.documents}
                        />

                        {rejectedFiles.length > 0 && (
                            <ul className="space-y-1 rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                                {rejectedFiles.map((file) => (
                                    <li key={file.name}>
                                        <span className="font-medium">
                                            {file.name}:
                                        </span>{' '}
                                        {file.reason}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {data.documents.length > 0 && (
                        <div className="space-y-4">
                            <p className="text-sm text-muted-foreground">
                                Asigna uno o más firmantes (máximo {MAX_SIGNERS}
                                ) a cada documento.
                            </p>

                            {data.documents.map((document, index) => (
                                <Card key={`${document.file.name}-${index}`}>
                                    <CardContent className="space-y-4">
                                        <div className="flex items-start justify-between gap-4">
                                            <div className="flex items-start gap-3">
                                                <FileText className="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                                                <div>
                                                    <p className="text-sm font-medium break-all">
                                                        {document.file.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatSize(
                                                            document.file.size,
                                                        )}
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground hover:text-destructive"
                                                onClick={() =>
                                                    removeDocument(index)
                                                }
                                                aria-label={`Eliminar ${document.file.name}`}
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>

                                        <div className="space-y-2">
                                            <div className="flex items-center justify-between">
                                                <Label>Firmantes</Label>
                                                <Badge variant="secondary">
                                                    {document.signers.length}/
                                                    {MAX_SIGNERS}
                                                </Badge>
                                            </div>

                                            <div className="grid gap-2 sm:grid-cols-2">
                                                {signers.map((signer) => {
                                                    const checked =
                                                        document.signers.includes(
                                                            signer.id,
                                                        );
                                                    const disabled =
                                                        !checked &&
                                                        document.signers
                                                            .length >=
                                                            MAX_SIGNERS;

                                                    return (
                                                        <label
                                                            key={signer.id}
                                                            className="flex cursor-pointer items-center gap-2 rounded-md border p-2 text-sm transition-colors hover:bg-accent/50 disabled:cursor-not-allowed disabled:opacity-50"
                                                        >
                                                            <Checkbox
                                                                checked={
                                                                    checked
                                                                }
                                                                disabled={
                                                                    disabled
                                                                }
                                                                onCheckedChange={() =>
                                                                    toggleSigner(
                                                                        index,
                                                                        signer.id,
                                                                    )
                                                                }
                                                            />
                                                            {signer.name}
                                                        </label>
                                                    );
                                                })}
                                            </div>

                                            <InputError
                                                className="mt-2"
                                                message={
                                                    errors[
                                                        `documents.${index}.file`
                                                    ] ??
                                                    errors[
                                                        `documents.${index}.signers`
                                                    ]
                                                }
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}

                    {progress && (
                        <div className="space-y-1">
                            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-primary transition-all"
                                    style={{ width: `${progress.percentage}%` }}
                                />
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Subiendo documentos… {progress.percentage}%
                            </p>
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <Button
                            type="submit"
                            disabled={processing || data.documents.length === 0}
                            data-test="upload-documents-button"
                        >
                            {processing ? 'Subiendo…' : 'Subir documentos'}
                        </Button>
                        <Link
                            href={dashboard()}
                            className="text-sm text-muted-foreground hover:text-foreground"
                        >
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </>
    );
}

CreateDocument.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Subir documentos',
            href: DocumentController.create.url(),
        },
    ],
};
