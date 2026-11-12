import { router } from '@inertiajs/react';
import { useState } from 'react';
import { OptionButtons } from '@/components/option-buttons';
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
import offers from '@/routes/offers';
import type { Option } from '@/types';

/**
 * Aceptar una oferta cierra el trato, y un trato tiene plazo de pago.
 *
 * Quien paga es siempre la poscosecha (regla 8), así que:
 *
 * - En un "Busco" suyo, quien acepta ES la poscosecha: elige el plazo aquí.
 * - En un "Disponible" de un productor, el plazo ya venía en la oferta de la
 *   poscosecha; quien acepta es el productor y solo lo confirma.
 *
 * En los dos casos el productor sabe el plazo antes de que el trato exista.
 */
export function AcceptOfferDialog({
    offerId,
    companyName,
    /** Si quien acepta es quien paga y por tanto elige el plazo. */
    choosesTerm,
    /** El plazo que ya venía en la oferta, cuando no se elige aquí. */
    agreedTerm,
    paymentTerms,
    open,
    onOpenChange,
}: {
    offerId: number;
    companyName: string;
    choosesTerm: boolean;
    agreedTerm: string | null;
    paymentTerms: Option[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const [term, setTerm] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const accept = () => {
        if (choosesTerm && term === null) {
            setError('Elige en cuánto tiempo vas a pagar.');

            return;
        }

        setError(null);
        router.post(
            offers.accept(offerId).url,
            choosesTerm ? { payment_term: term } : {},
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => {
                    setProcessing(false);
                    onOpenChange(false);
                },
            },
        );
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>
                        Aceptar la oferta de {companyName}
                    </DialogTitle>
                    <DialogDescription>
                        Se cierra el trato y se le muestra tu celular para
                        coordinar la entrega.
                    </DialogDescription>
                </DialogHeader>

                {choosesTerm ? (
                    <div className="flex flex-col gap-2">
                        <p className="text-base font-medium">¿Cuándo pagas?</p>
                        <p className="text-sm text-muted-foreground">
                            Queda registrado. Es lo que el productor va a
                            responder cuando lo califique.
                        </p>
                        <OptionButtons
                            options={paymentTerms}
                            value={term}
                            onChange={setTerm}
                            size="sm"
                        />
                        {error !== null && (
                            <p className="text-sm text-destructive">{error}</p>
                        )}
                    </div>
                ) : (
                    agreedTerm !== null && (
                        <p className="rounded-xl border bg-muted/50 p-3 text-sm">
                            Pago acordado:{' '}
                            <strong className="font-semibold">
                                {agreedTerm}
                            </strong>
                        </p>
                    )
                )}

                <DialogFooter className="gap-2 sm:gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={processing}
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="button"
                        onClick={accept}
                        disabled={processing}
                    >
                        {processing && <Spinner />}
                        Aceptar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
