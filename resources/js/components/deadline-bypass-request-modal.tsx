import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertTriangle, Send } from 'lucide-react';
import React from 'react';

interface DeadlineRequestContext {
    rule_key?: string;
    kegiatan_id?: number | null;
    periode_alokasi_id?: number | null;
    year?: number | null;
    month?: number | null;
    route_name?: string | null;
    http_method?: string | null;
    target_url?: string | null;
}

interface DeadlineBlockedPayload {
    message?: string;
    rule_key?: string;
    request_context?: DeadlineRequestContext;
}

const DEADLINE_BYPASS_REQUEST_API_URL = '/deadline-bypass/request';

export function DeadlineBypassRequestModal() {
    const { flash } = usePage<SharedData>().props;
    const blockedPayload =
        (flash.deadline_blocked as DeadlineBlockedPayload | undefined) ?? null;

    const [open, setOpen] = React.useState(Boolean(blockedPayload));
    const [reason, setReason] = React.useState('');
    const [processing, setProcessing] = React.useState(false);
    const [infoMessage, setInfoMessage] = React.useState<string | null>(null);

    React.useEffect(() => {
        if (blockedPayload) {
            setOpen(true);
            setReason('');
            setInfoMessage(null);
        }
    }, [blockedPayload]);

    const getCsrfToken = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');

        return meta?.getAttribute('content') ?? '';
    };

    const handleSubmitRequest = async () => {
        if (!blockedPayload) {
            return;
        }

        if (reason.trim().length < 10) {
            setInfoMessage('Alasan request minimal 10 karakter.');

            return;
        }

        setProcessing(true);
        setInfoMessage(null);

        try {
            const requestContext = blockedPayload.request_context ?? {};

            const response = await fetch(DEADLINE_BYPASS_REQUEST_API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    rule_key:
                        requestContext.rule_key ?? blockedPayload.rule_key,
                    kegiatan_id: requestContext.kegiatan_id ?? null,
                    periode_alokasi_id:
                        requestContext.periode_alokasi_id ?? null,
                    year: requestContext.year ?? null,
                    month: requestContext.month ?? null,
                    route_name: requestContext.route_name ?? null,
                    http_method: requestContext.http_method ?? null,
                    target_url: requestContext.target_url ?? null,
                    reason: reason.trim(),
                }),
            });

            const payload = (await response.json().catch(() => null)) as {
                message?: string;
                success?: boolean;
            } | null;

            if (!response.ok || payload?.success === false) {
                setInfoMessage(
                    payload?.message ??
                        'Request bypass gagal dikirim. Silakan coba lagi.',
                );

                return;
            }

            setInfoMessage(
                payload?.message ??
                    'Request bypass berhasil dikirim dan menunggu persetujuan admin.',
            );
            setOpen(false);
        } catch {
            setInfoMessage('Request bypass gagal dikirim. Silakan coba lagi.');
        } finally {
            setProcessing(false);
        }
    };

    if (!blockedPayload) {
        return null;
    }

    return (
        <>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-amber-600" />
                            Melewati Batas Waktu
                        </DialogTitle>
                        <DialogDescription>
                            {blockedPayload.message ??
                                'Aksi ditolak karena melewati batas waktu.'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">
                            Alasan request ke admin
                        </p>
                        <Textarea
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                            placeholder="Tuliskan alasan kenapa aksi ini perlu dibuka sementara..."
                            rows={4}
                        />
                        <p className="text-xs text-muted-foreground">
                            Request akan masuk ke halaman Pengaturan Sistem
                            admin untuk disetujui atau ditolak.
                        </p>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                            disabled={processing}
                        >
                            Batal
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSubmitRequest}
                            disabled={processing}
                        >
                            <Send className="mr-2 h-4 w-4" />
                            {processing
                                ? 'Mengirim...'
                                : 'Kirim Request Bypass'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {infoMessage && (
                <div className="fixed right-4 bottom-4 z-[60] max-w-sm rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 shadow-lg dark:border-blue-800 dark:bg-blue-950/70 dark:text-blue-100">
                    {infoMessage}
                </div>
            )}
        </>
    );
}
