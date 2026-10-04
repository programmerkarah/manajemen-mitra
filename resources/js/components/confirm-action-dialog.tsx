import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AlertTriangle from 'lucide-react/icons/alert-triangle';
import LoaderCircle from 'lucide-react/icons/loader-circle';

interface ConfirmActionDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
    processing?: boolean;
    onConfirm: () => void;
}

export function ConfirmActionDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel = 'Lanjutkan',
    cancelLabel = 'Batal',
    destructive = false,
    processing = false,
    onConfirm,
}: ConfirmActionDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <div className="mb-2 flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        <AlertTriangle className="size-5" />
                    </div>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription className="leading-6">
                        {description}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter className="gap-2 sm:gap-0">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={processing}
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        type="button"
                        variant={destructive ? 'destructive' : 'default'}
                        onClick={onConfirm}
                        disabled={processing}
                    >
                        {processing && (
                            <LoaderCircle className="mr-2 size-4 animate-spin" />
                        )}
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
