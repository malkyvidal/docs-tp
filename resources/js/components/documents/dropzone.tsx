import { UploadCloud } from 'lucide-react';
import { useRef, useState } from 'react';

import { cn } from '@/lib/utils';

export const MAX_DOCUMENT_SIZE = 25 * 1024 * 1024;

export type RejectedDocument = {
    name: string;
    reason: string;
};

export function DocumentDropzone({
    onFiles,
    onRejected,
    disabled = false,
}: {
    onFiles: (files: File[]) => void;
    onRejected: (rejected: RejectedDocument[]) => void;
    disabled?: boolean;
}) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [isDragging, setIsDragging] = useState(false);

    function inspect(files: FileList | null): void {
        if (!files) {
            return;
        }

        const accepted: File[] = [];
        const rejected: RejectedDocument[] = [];

        Array.from(files).forEach((file) => {
            const isPdf =
                file.type === 'application/pdf' ||
                file.name.toLowerCase().endsWith('.pdf');

            if (!isPdf) {
                rejected.push({
                    name: file.name,
                    reason: 'Debe ser un archivo PDF.',
                });
            } else if (file.size > MAX_DOCUMENT_SIZE) {
                rejected.push({
                    name: file.name,
                    reason: 'No debe pesar más de 25 MB.',
                });
            } else {
                accepted.push(file);
            }
        });

        if (accepted.length > 0) {
            onFiles(accepted);
        }

        if (rejected.length > 0) {
            onRejected(rejected);
        }
    }

    return (
        <div
            role="button"
            tabIndex={0}
            aria-label="Zona para arrastrar y soltar documentos PDF"
            onClick={() => inputRef.current?.click()}
            onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    inputRef.current?.click();
                }
            }}
            onDragEnter={(event) => {
                event.preventDefault();
                setIsDragging(true);
            }}
            onDragOver={(event) => event.preventDefault()}
            onDragLeave={(event) => {
                event.preventDefault();
                setIsDragging(false);
            }}
            onDrop={(event) => {
                event.preventDefault();
                setIsDragging(false);

                if (!disabled) {
                    inspect(event.dataTransfer.files);
                }
            }}
            className={cn(
                'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed px-6 py-14 text-center transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                isDragging
                    ? 'border-primary bg-primary/5'
                    : 'border-border hover:border-primary/60 hover:bg-accent/50',
                disabled && 'pointer-events-none opacity-50',
            )}
        >
            <input
                ref={inputRef}
                type="file"
                accept=".pdf,application/pdf"
                multiple
                className="hidden"
                disabled={disabled}
                onChange={(event) => {
                    inspect(event.target.files);
                    event.target.value = '';
                }}
            />
            <UploadCloud className="size-10 text-muted-foreground" />
            <p className="text-sm font-medium">
                Arrastra y suelta tus documentos PDF aquí
            </p>
            <p className="text-xs text-muted-foreground">
                o haz clic para seleccionarlos · Máximo 25 MB por archivo
            </p>
        </div>
    );
}
