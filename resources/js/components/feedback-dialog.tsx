import { router, usePage } from '@inertiajs/react';
import { MessageSquareWarning } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import feedback from '@/routes/feedback';

/**
 * "¿Algo no funcionó?" — el camino corto para contarnos un problema.
 *
 * Manda la pantalla de origen junto con el mensaje: en el piloto, saber
 * DÓNDE se trabó alguien vale más que lo que escriba.
 */
export function FeedbackDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const page = usePage();
    const [message, setMessage] = useState('');
    const [processing, setProcessing] = useState(false);

    const send = () => {
        if (message.trim() === '') {
            return;
        }

        router.post(
            feedback.store().url,
            { message: message.trim(), screen: page.component },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    setMessage('');
                    onOpenChange(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <MessageSquareWarning className="size-5 text-violet-600" />
                        ¿Algo no funcionó?
                    </DialogTitle>
                    <DialogDescription>
                        Cuéntanos qué pasó y lo revisamos. Nos sirve todo: algo
                        que no entendiste, un botón que no hizo nada, una idea.
                    </DialogDescription>
                </DialogHeader>

                <Textarea
                    value={message}
                    maxLength={1000}
                    autoFocus
                    onChange={(e) => setMessage(e.target.value)}
                    placeholder="Por ejemplo: no pude subir la foto desde el celular"
                    className="min-h-28 text-base"
                />

                <DialogFooter>
                    <Button
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                    >
                        Cancelar
                    </Button>
                    <Button
                        disabled={processing || message.trim() === ''}
                        onClick={send}
                    >
                        {processing ? <Spinner /> : null}
                        Enviar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
