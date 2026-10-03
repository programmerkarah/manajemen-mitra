import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import FileText from 'lucide-react/icons/file-text';
import UploadCloud from 'lucide-react/icons/upload-cloud';
import X from 'lucide-react/icons/x';
import { useRef, useState } from 'react';

interface FileUploadProps {
    value?: File | null;
    onChange: (file: File | null) => void;
    accept?: string;
    disabled?: boolean;
    label?: string;
    helperText?: string;
    maxSizeMb?: number;
    className?: string;
    id?: string;
}

const formatSize = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

export function FileUpload({
    value = null,
    onChange,
    accept = 'application/pdf,.pdf',
    disabled = false,
    label = 'Pilih atau jatuhkan file PDF',
    helperText = 'PDF',
    maxSizeMb,
    className,
    id,
}: FileUploadProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    const applyFile = (file: File | null) => {
        setLocalError(null);

        if (!file) {
            onChange(null);
            return;
        }

        if (maxSizeMb && file.size > maxSizeMb * 1024 * 1024) {
            setLocalError(`Ukuran file maksimal ${maxSizeMb} MB.`);
            onChange(null);
            return;
        }

        if (
            accept.includes('pdf') &&
            file.type !== 'application/pdf' &&
            !file.name.toLowerCase().endsWith('.pdf')
        ) {
            setLocalError('File harus berformat PDF.');
            onChange(null);
            return;
        }

        onChange(file);
    };

    return (
        <div className={cn('space-y-2', className)}>
            <input
                ref={inputRef}
                id={id}
                type="file"
                accept={accept}
                disabled={disabled}
                className="hidden"
                onChange={(event) =>
                    applyFile(event.target.files?.[0] ?? null)
                }
            />

            {value ? (
                <div className="flex items-center gap-3 rounded-xl border border-border bg-card px-4 py-3">
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                        <FileText className="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-medium">
                            {value.name}
                        </p>
                        <p className="text-xs text-muted-foreground">
                            {formatSize(value.size)}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        disabled={disabled}
                        onClick={() => {
                            if (inputRef.current) inputRef.current.value = '';
                            applyFile(null);
                        }}
                        aria-label="Hapus file pilihan"
                    >
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            ) : (
                <button
                    type="button"
                    disabled={disabled}
                    onClick={() => inputRef.current?.click()}
                    onDragEnter={(event) => {
                        event.preventDefault();
                        if (!disabled) setDragging(true);
                    }}
                    onDragOver={(event) => {
                        event.preventDefault();
                        if (!disabled) setDragging(true);
                    }}
                    onDragLeave={(event) => {
                        event.preventDefault();
                        setDragging(false);
                    }}
                    onDrop={(event) => {
                        event.preventDefault();
                        setDragging(false);
                        if (!disabled) {
                            applyFile(event.dataTransfer.files?.[0] ?? null);
                        }
                    }}
                    className={cn(
                        'flex w-full items-center gap-3 rounded-xl border border-dashed border-border bg-muted/30 px-4 py-4 text-left transition-colors',
                        'hover:border-primary/40 hover:bg-muted/50',
                        dragging && 'border-primary bg-primary/5',
                        disabled && 'cursor-not-allowed opacity-60',
                    )}
                >
                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-background shadow-sm">
                        <UploadCloud className="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div className="min-w-0">
                        <p className="text-sm font-medium">{label}</p>
                        <p className="mt-0.5 text-xs text-muted-foreground">
                            {helperText}
                            {maxSizeMb ? ` · Maks. ${maxSizeMb} MB` : ''}
                        </p>
                    </div>
                </button>
            )}

            {localError && (
                <p className="text-xs font-medium text-destructive">
                    {localError}
                </p>
            )}
        </div>
    );
}
