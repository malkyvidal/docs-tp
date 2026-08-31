import { Head, router, usePage } from '@inertiajs/react';
import { FileDown, FileText, FolderOpen, PenLine } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import documentsRoutes from '@/routes/documents';

type Signer = {
    id: number;
    name: string;
    signed: boolean;
};

type DocumentItem = {
    id: number;
    uuid: string;
    name: string;
    summary: string | null;
    created_at: string;
    status: 'signed' | 'pending';
    signers?: Signer[];
    signature_order?: number;
    total_signers?: number;
};

type Props = {
    documents: DocumentItem[];
};

function SignedBadge() {
    return (
        <Badge
            variant="outline"
            className="text-emerald-600 dark:text-emerald-500"
        >
            Firmado
        </Badge>
    );
}

function PendingBadge() {
    return (
        <Badge variant="outline" className="text-amber-600 dark:text-amber-500">
            Pendiente
        </Badge>
    );
}

function SignDialog({
    document,
    onOpenChange,
}: {
    document: DocumentItem;
    onOpenChange: (open: boolean) => void;
}) {
    const [processing, setProcessing] = useState(false);

    function sign() {
        setProcessing(true);

        router.post(
            documentsRoutes.sign.url({ document: document.id }),
            {},
            {
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <Dialog open onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Firmar documento</DialogTitle>
                    <DialogDescription>
                        Vas a firmar “{document.name}”. Al confirmar, tu firma
                        quedará registrada de forma permanente y el documento
                        dejará de estar disponible para ti.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button
                        variant="outline"
                        disabled={processing}
                        onClick={() => onOpenChange(false)}
                    >
                        Cancelar
                    </Button>
                    <Button
                        disabled={processing}
                        onClick={sign}
                        data-test="confirm-sign-button"
                    >
                        {processing ? 'Firmando…' : 'Confirmar firma'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function DocumentList({ documents }: Props) {
    const { auth } = usePage().props;
    const isAdministrator = auth.user?.role === 'administrator';
    const [signingDocument, setSigningDocument] = useState<DocumentItem | null>(
        null,
    );

    return (
        <TooltipProvider>
            <div className="space-y-6">
                <Head title="Documentos" />

                <Heading
                    variant="small"
                    title="Documentos"
                    description={
                        isAdministrator
                            ? 'Todos los documentos y su estado de firma'
                            : 'Los documentos que tienes listos para firmar'
                    }
                />

                {documents.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 rounded-xl border py-16 text-center">
                        <FolderOpen className="size-10 text-muted-foreground" />
                        <p className="text-sm text-muted-foreground">
                            No hay documentos disponibles.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border shadow-sm">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-muted-foreground">
                                    <th className="px-6 py-3 font-medium">
                                        Documento
                                    </th>
                                    <th className="px-6 py-3 font-medium">
                                        Fecha
                                    </th>
                                    {isAdministrator && (
                                        <th className="px-6 py-3 font-medium">
                                            Firmantes
                                        </th>
                                    )}
                                    <th className="px-6 py-3 text-right font-medium">
                                        Estado
                                    </th>
                                    <th className="px-6 py-3 text-right font-medium">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {documents.map((document) => {
                                    const total = document.signers?.length ?? 0;
                                    const signedCount =
                                        document.signers?.filter(
                                            (signer) => signer.signed,
                                        ).length ?? 0;
                                    const pendingSigners =
                                        document.signers?.filter(
                                            (signer) => !signer.signed,
                                        ) ?? [];
                                    const pendingLabel =
                                        pendingSigners.length > 0
                                            ? `Faltan por firmar: ${pendingSigners
                                                  .map((signer) => signer.name)
                                                  .join(', ')}`
                                            : 'Sin firmantes asignados';

                                    return (
                                        <tr
                                            key={document.id}
                                            className="border-b last:border-b-0"
                                        >
                                            <td className="px-6 py-4">
                                                <div className="flex items-start gap-3">
                                                    <FileText className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                                    <div>
                                                        <p className="font-medium break-all">
                                                            {document.name}
                                                        </p>
                                                        {document.summary && (
                                                            <p className="mt-0.5 line-clamp-2 max-w-md text-xs text-muted-foreground">
                                                                {
                                                                    document.summary
                                                                }
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-muted-foreground">
                                                {document.created_at}
                                            </td>
                                            {isAdministrator && (
                                                <td className="px-6 py-4 whitespace-nowrap text-muted-foreground">
                                                    {signedCount}/{total}
                                                </td>
                                            )}
                                            <td className="px-6 py-4 text-right">
                                                {isAdministrator ? (
                                                    document.status ===
                                                    'signed' ? (
                                                        <SignedBadge />
                                                    ) : (
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <span className="cursor-help">
                                                                    <PendingBadge />
                                                                </span>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                {pendingLabel}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    )
                                                ) : document.status ===
                                                  'signed' ? (
                                                    <SignedBadge />
                                                ) : (
                                                    <span className="flex flex-col items-end gap-1">
                                                        <PendingBadge />
                                                        {typeof document.signature_order ===
                                                            'number' && (
                                                            <span className="text-xs text-muted-foreground">
                                                                Firmante{' '}
                                                                {
                                                                    document.signature_order
                                                                }{' '}
                                                                de{' '}
                                                                {
                                                                    document.total_signers
                                                                }
                                                            </span>
                                                        )}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {isAdministrator ? (
                                                        document.status ===
                                                            'signed' && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <a
                                                                    href={documentsRoutes.download.url(
                                                                        {
                                                                            document:
                                                                                document.id,
                                                                        },
                                                                    )}
                                                                    data-test="download-document-button"
                                                                >
                                                                    <FileDown className="size-4" />
                                                                    Descargar
                                                                </a>
                                                            </Button>
                                                        )
                                                    ) : (
                                                        <>
                                                            {document.status ===
                                                            'signed' ? (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    asChild
                                                                >
                                                                    <a
                                                                        href={documentsRoutes.download.url(
                                                                            {
                                                                                document:
                                                                                    document.id,
                                                                            },
                                                                        )}
                                                                        data-test="download-document-button"
                                                                    >
                                                                        <FileDown className="size-4" />
                                                                        Descargar
                                                                    </a>
                                                                </Button>
                                                            ) : (
                                                                <>
                                                                    <Button
                                                                        variant="outline"
                                                                        size="sm"
                                                                        asChild
                                                                    >
                                                                        <a
                                                                            href={documentsRoutes.download.url(
                                                                                {
                                                                                    document:
                                                                                        document.id,
                                                                                },
                                                                            )}
                                                                            target="_blank"
                                                                            rel="noreferrer"
                                                                            data-test="view-document-button"
                                                                        >
                                                                            <FileDown className="size-4" />
                                                                            Ver
                                                                        </a>
                                                                    </Button>
                                                                    <Button
                                                                        size="sm"
                                                                        onClick={() =>
                                                                            setSigningDocument(
                                                                                document,
                                                                            )
                                                                        }
                                                                        data-test="sign-document-button"
                                                                    >
                                                                        <PenLine className="size-4" />
                                                                        Firmar
                                                                    </Button>
                                                                </>
                                                            )}
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}

                {signingDocument && (
                    <SignDialog
                        document={signingDocument}
                        onOpenChange={(open) => {
                            if (!open) {
                                setSigningDocument(null);
                            }
                        }}
                    />
                )}
            </div>
        </TooltipProvider>
    );
}

DocumentList.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Documentos',
            href: documentsRoutes.index.url(),
        },
    ],
};
