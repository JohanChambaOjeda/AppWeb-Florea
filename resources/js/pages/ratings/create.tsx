import { Head, router, usePage } from '@inertiajs/react';
import { Check, Star } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { OptionButtons } from '@/components/option-buttons';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { money } from '@/lib/format';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import ratings from '@/routes/ratings';
import type { Option } from '@/types';

type Props = {
    deal: {
        id: number;
        closedAt: string;
        what: string;
        pricePerStem: string | null;
    };
    other: { name: string; typeLabel: string };
    isBuyer: boolean;
    deliveryResults: Option[];
    rejectedRanges: Option[];
    commentMax: number;
};

const YES_NO: Option[] = [
    { value: '1', label: 'Sí' },
    { value: '0', label: 'No' },
];

export default function CreateRating({
    deal,
    other,
    isBuyer,
    deliveryResults,
    rejectedRanges,
    commentMax,
}: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    const [delivered, setDelivered] = useState<string | null>(null);
    const [onTime, setOnTime] = useState<string | null>(null);
    const [rejected, setRejected] = useState<string | null>(null);
    const [score, setScore] = useState<number | null>(null);
    const [comment, setComment] = useState('');
    const [processing, setProcessing] = useState(false);
    const [clientError, setClientError] = useState<string | null>(null);

    const submit = () => {
        if (delivered === null || onTime === null || score === null) {
            setClientError('Responde las tres preguntas de arriba.');

            return;
        }

        if (isBuyer && rejected === null) {
            setClientError('Dinos cuánto se rechazó.');

            return;
        }

        setClientError(null);
        router.post(
            ratings.store(deal.id).url,
            {
                delivered,
                on_time: onTime === '1',
                rejected_range: isBuyer ? rejected : null,
                score,
                comment: comment.trim() === '' ? null : comment.trim(),
            },
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title={`¿Cómo te fue con ${other.name}?`} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5 p-4 pb-0">
                <header className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold">
                        ¿Cómo te fue con {other.name}?
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {deal.what}
                        {deal.pricePerStem !== null &&
                            ` · ${money(deal.pricePerStem)} el tallo`}
                    </p>
                </header>

                <section className="flex flex-col gap-5 rounded-xl border bg-card p-4">
                    <div className="flex flex-col gap-2">
                        <Label className="text-base">¿Se entregó?</Label>
                        <OptionButtons
                            options={deliveryResults}
                            value={delivered}
                            onChange={setDelivered}
                        />
                        <InputError message={errors.delivered} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label className="text-base">¿Llegó a tiempo?</Label>
                        <OptionButtons
                            options={YES_NO}
                            value={onTime}
                            onChange={setOnTime}
                        />
                        <InputError message={errors.on_time} />
                    </div>

                    {isBuyer && (
                        <div className="flex flex-col gap-2">
                            <Label className="text-base">
                                ¿Cuánto se rechazó?
                            </Label>
                            <OptionButtons
                                options={rejectedRanges}
                                value={rejected}
                                onChange={setRejected}
                                size="sm"
                            />
                            <InputError message={errors.rejected_range} />
                        </div>
                    )}
                </section>

                <section className="flex flex-col gap-3 rounded-xl border bg-card p-4">
                    <Label className="text-base">En general, ¿qué tal?</Label>
                    <div className="flex justify-center gap-2">
                        {[1, 2, 3, 4, 5].map((value) => (
                            <button
                                key={value}
                                type="button"
                                aria-label={`${value} ${value === 1 ? 'estrella' : 'estrellas'}`}
                                aria-pressed={score === value}
                                onClick={() => {
                                    setScore(value);
                                    setClientError(null);
                                }}
                                className="flex size-14 items-center justify-center rounded-xl transition-colors hover:bg-accent"
                            >
                                <Star
                                    className={cn(
                                        'size-9',
                                        score !== null && value <= score
                                            ? 'fill-amber-400 text-amber-400'
                                            : 'text-muted-foreground/40',
                                    )}
                                />
                            </button>
                        ))}
                    </div>
                    <InputError message={errors.score} />
                </section>

                <div className="flex flex-col gap-2">
                    <Label htmlFor="comment" className="text-base">
                        Comentario{' '}
                        <span className="font-normal text-muted-foreground">
                            (opcional)
                        </span>
                    </Label>
                    <Textarea
                        id="comment"
                        value={comment}
                        maxLength={commentMax}
                        onChange={(e) => setComment(e.target.value)}
                        placeholder="Algo que quieras contarnos"
                        className="text-base"
                    />
                    <p className="text-xs text-muted-foreground">
                        Los comentarios los lee solo el equipo de Florea. La
                        otra empresa ve únicamente los números.
                    </p>
                    <InputError message={errors.comment} />
                </div>

                <div className="sticky bottom-0 -mx-4 border-t bg-background/95 px-4 py-3 backdrop-blur">
                    <InputError
                        message={clientError ?? undefined}
                        className="mb-2 text-center"
                    />
                    <Button
                        size="lg"
                        className="h-14 w-full text-lg"
                        disabled={processing}
                        onClick={submit}
                    >
                        {processing ? <Spinner /> : <Check />}
                        Enviar calificación
                    </Button>
                </div>
            </div>
        </>
    );
}

CreateRating.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: dashboard() },
        { title: 'Calificar', href: dashboard() },
    ],
};
