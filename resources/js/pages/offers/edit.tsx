import { Head, router, usePage } from '@inertiajs/react';
import { Check, Info } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { OptionButtons } from '@/components/option-buttons';
import { PhotoInput } from '@/components/photo-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show } from '@/routes/listings';
import offers from '@/routes/offers';
import type {
    Delivery,
    LengthRange,
    ListingDetail,
    ListingDetailItem,
    MyOffer,
    OfferItemDraft,
    Option,
    Unit,
} from '@/types';

type Props = {
    listing: ListingDetail;
    offer: MyOffer | null;
    sells: boolean;
    units: Option[];
    deliveries: Option[];
    customLength: LengthRange;
    maxPhotos: number;
    notesMax: number;
};

/** Estado inicial de cada línea: lo que ya oferté, o la línea sin marcar. */
const initialDrafts = (
    listing: ListingDetail,
    offer: MyOffer | null,
): Record<number, OfferItemDraft> =>
    Object.fromEntries(
        listing.items.map((item) => {
            const mine = offer?.items.find((o) => o.listingItemId === item.id);

            return [
                item.id,
                {
                    checked: mine !== undefined,
                    // Por defecto se ofrece lo que piden: un toque menos.
                    quantity: mine?.quantity ?? item.quantity ?? '',
                    unit: mine?.unit ?? item.unit ?? 'hb',
                    length: mine?.length ?? item.lengths[0] ?? null,
                } satisfies OfferItemDraft,
            ];
        }),
    );

export default function EditOffer({
    listing,
    offer,
    sells,
    units,
    deliveries,
    customLength,
    maxPhotos,
    notesMax,
}: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    const [drafts, setDrafts] = useState(() => initialDrafts(listing, offer));
    const [price, setPrice] = useState(offer?.pricePerStem ?? '');
    const [delivery, setDelivery] = useState<Delivery | null>(
        offer?.delivery ?? null,
    );
    const [notes, setNotes] = useState(offer?.notes ?? '');
    const [keptIds, setKeptIds] = useState<number[]>(
        offer?.photos.map((photo) => photo.id) ?? [],
    );
    const [files, setFiles] = useState<File[]>([]);
    const [processing, setProcessing] = useState(false);
    const [clientError, setClientError] = useState<string | null>(null);

    const editing = offer !== null && offer.status === 'sent';
    const chosen = listing.items.filter((item) => drafts[item.id].checked);

    const patch = (itemId: number, next: Partial<OfferItemDraft>) => {
        setClientError(null);
        setDrafts((current) => ({
            ...current,
            [itemId]: { ...current[itemId], ...next },
        }));
    };

    const submit = () => {
        if (chosen.length === 0) {
            setClientError('Marca al menos una línea de la publicación.');

            return;
        }

        const incomplete = chosen.find(
            (item) =>
                drafts[item.id].quantity.trim() === '' ||
                drafts[item.id].length === null,
        );

        if (incomplete !== undefined) {
            setClientError(
                `Completa la cantidad y el largo de ${incomplete.variety}.`,
            );

            return;
        }

        if (sells && keptIds.length + files.length === 0) {
            setClientError('Sube al menos una foto de la flor de hoy.');

            return;
        }

        setClientError(null);
        router.post(
            offers.save(listing.id).url,
            {
                items: chosen.map((item) => ({
                    listing_item_id: item.id,
                    quantity: drafts[item.id].quantity,
                    unit: drafts[item.id].unit,
                    length: drafts[item.id].length,
                })),
                price_per_stem: price.trim() === '' ? null : price,
                delivery,
                notes: notes.trim() === '' ? null : notes.trim(),
                keep_photos: keptIds,
                photos: files,
            },
            {
                forceFormData: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const withdraw = () => {
        if (!window.confirm('¿Seguro que quieres retirar tu oferta?')) {
            return;
        }

        router.delete(offers.withdraw(listing.id).url);
    };

    return (
        <>
            <Head title={editing ? 'Editar mi oferta' : 'Ofertar'} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5 p-4 pb-0">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold">
                        {editing
                            ? 'Editar mi oferta'
                            : listing.kind === 'want'
                              ? 'Ofertar'
                              : 'Quiero comprar'}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {listing.company.name} · {listing.company.zone}
                    </p>
                </div>

                {offer !== null && offer.status === 'withdrawn' && (
                    <p className="flex items-start gap-2 rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100">
                        <Info className="mt-0.5 size-4 shrink-0" />
                        Habías retirado esta oferta. Si la envías otra vez,
                        volverá a aparecerle a quien publicó.
                    </p>
                )}

                {/* Líneas */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-base font-semibold">
                        ¿Qué puedes {sells ? 'dar' : 'comprar'}?
                    </h2>

                    {listing.items.map((item) => (
                        <OfferLine
                            key={item.id}
                            item={item}
                            draft={drafts[item.id]}
                            units={units}
                            customLength={customLength}
                            onPatch={(next) => patch(item.id, next)}
                        />
                    ))}

                    <InputError message={errors.items} />
                </section>

                {/* Precio y entrega */}
                <section className="flex flex-col gap-4 rounded-xl border bg-card p-4">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="price" className="text-base">
                            Precio por tallo (USD)
                            {!sells && (
                                <span className="font-normal text-muted-foreground">
                                    {' '}
                                    (opcional)
                                </span>
                            )}
                        </Label>
                        <div className="flex items-center gap-2">
                            <span className="text-lg font-medium text-muted-foreground">
                                $
                            </span>
                            <Input
                                id="price"
                                type="number"
                                inputMode="decimal"
                                step="0.01"
                                min="0.01"
                                value={price}
                                onChange={(e) => setPrice(e.target.value)}
                                placeholder="0.45"
                                className="h-12 max-w-32 text-base"
                            />
                        </div>
                        <InputError message={errors.price_per_stem} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label className="text-base">Entrega</Label>
                        <OptionButtons
                            options={deliveries}
                            value={delivery}
                            onChange={(value) =>
                                setDelivery(value as Delivery | null)
                            }
                            size="sm"
                        />
                        <InputError message={errors.delivery} />
                    </div>
                </section>

                {/* Fotos: solo quien vende */}
                {sells && (
                    <section className="flex flex-col gap-3 rounded-xl border bg-card p-4">
                        <div className="flex flex-col gap-1">
                            <Label className="text-base">Foto de hoy</Label>
                            <p className="text-sm text-muted-foreground">
                                Una foto de la flor de hoy da confianza y evita
                                sorpresas en la entrega.
                            </p>
                        </div>
                        <PhotoInput
                            existing={offer?.photos ?? []}
                            keptIds={keptIds}
                            onKeepChange={setKeptIds}
                            files={files}
                            onFilesChange={setFiles}
                            max={maxPhotos}
                        />
                        <InputError
                            message={
                                errors.photos ??
                                Object.entries(errors).find(([key]) =>
                                    key.startsWith('photos.'),
                                )?.[1]
                            }
                        />
                    </section>
                )}

                {/* Nota */}
                <div className="flex flex-col gap-2">
                    <Label htmlFor="notes" className="text-base">
                        Nota{' '}
                        <span className="font-normal text-muted-foreground">
                            (opcional)
                        </span>
                    </Label>
                    <Textarea
                        id="notes"
                        value={notes}
                        maxLength={notesMax}
                        onChange={(e) => setNotes(e.target.value)}
                        placeholder="Algo que deba saber"
                        className="text-base"
                    />
                    <InputError message={errors.notes} />
                </div>

                {/* Enviar */}
                <div className="sticky bottom-0 -mx-4 flex flex-col gap-2 border-t bg-background/95 px-4 py-3 backdrop-blur">
                    <InputError
                        message={clientError ?? undefined}
                        className="text-center"
                    />
                    <Button
                        size="lg"
                        className="h-14 w-full text-lg"
                        onClick={submit}
                        disabled={processing}
                    >
                        {processing ? <Spinner /> : <Check />}
                        {editing ? 'Guardar cambios' : 'Enviar oferta'}
                    </Button>

                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            className="flex-1"
                            onClick={() => router.get(show(listing.id).url)}
                        >
                            Cancelar
                        </Button>
                        {offer?.canWithdraw && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="flex-1 text-destructive hover:text-destructive"
                                onClick={withdraw}
                            >
                                Retirar oferta
                            </Button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function OfferLine({
    item,
    draft,
    units,
    customLength,
    onPatch,
}: {
    item: ListingDetailItem;
    draft: OfferItemDraft;
    units: Option[];
    customLength: LengthRange;
    onPatch: (next: Partial<OfferItemDraft>) => void;
}) {
    const [custom, setCustom] = useState('');

    // Los largos que acepta la línea, más el que ya ofrecí si era especial.
    const offered = draft.length;
    const choices = [
        ...item.lengths,
        ...(offered !== null && !item.lengths.includes(offered)
            ? [offered]
            : []),
    ];

    return (
        <div
            className={cn(
                'rounded-xl border bg-card transition-colors',
                draft.checked &&
                    'border-primary bg-violet-50/60 dark:bg-violet-950/30',
            )}
        >
            <button
                type="button"
                onClick={() => onPatch({ checked: !draft.checked })}
                aria-pressed={draft.checked}
                className="flex min-h-14 w-full items-center gap-3 px-4 py-3 text-left"
            >
                <span
                    className={cn(
                        'flex size-7 shrink-0 items-center justify-center rounded-md border-2',
                        draft.checked
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input bg-background',
                    )}
                >
                    {draft.checked && (
                        <Check className="size-5" strokeWidth={3} />
                    )}
                </span>
                <span className="flex min-w-0 flex-col">
                    <span className="text-base font-medium">
                        {item.variety}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        Piden {item.lengths.join(' / ')} cm
                        {item.quantity !== null &&
                            ` · ${item.quantity} ${item.unitLabel}`}
                    </span>
                </span>
            </button>

            {draft.checked && (
                <div className="flex flex-col gap-3 px-4 pb-4">
                    <div className="flex flex-col gap-2">
                        <p className="text-sm font-medium text-muted-foreground">
                            Cuánto ofreces
                        </p>
                        <div className="flex flex-wrap items-center gap-2">
                            <Input
                                type="number"
                                inputMode="decimal"
                                step="0.01"
                                min="0.01"
                                value={draft.quantity}
                                onChange={(e) =>
                                    onPatch({ quantity: e.target.value })
                                }
                                aria-label={`Cantidad de ${item.variety}`}
                                className="h-11 max-w-24 text-base"
                            />
                            <OptionButtons
                                options={units}
                                value={draft.unit}
                                onChange={(unit) =>
                                    onPatch({ unit: unit as Unit })
                                }
                                size="sm"
                            />
                        </div>
                    </div>

                    <div className="flex flex-col gap-2">
                        <p className="text-sm font-medium text-muted-foreground">
                            Largo que ofreces
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {choices.map((length) => (
                                <button
                                    key={length}
                                    type="button"
                                    onClick={() => onPatch({ length })}
                                    aria-pressed={draft.length === length}
                                    className={cn(
                                        'h-11 min-w-14 rounded-lg border-2 px-3 text-base font-semibold tabular-nums transition-colors',
                                        draft.length === length
                                            ? 'border-sky-500 bg-sky-500 text-white'
                                            : 'border-input bg-background',
                                    )}
                                >
                                    {length}
                                </button>
                            ))}
                        </div>
                        <div className="flex gap-2">
                            <Input
                                type="number"
                                inputMode="numeric"
                                min={customLength.min}
                                max={customLength.max}
                                value={custom}
                                onChange={(e) => setCustom(e.target.value)}
                                placeholder="Otro largo"
                                aria-label={`Otro largo para ${item.variety}`}
                                className="h-11 max-w-32 text-base"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                className="h-11"
                                disabled={custom === ''}
                                onClick={() => {
                                    const value = Number(custom);

                                    if (
                                        Number.isInteger(value) &&
                                        value >= customLength.min &&
                                        value <= customLength.max
                                    ) {
                                        onPatch({ length: value });
                                        setCustom('');
                                    }
                                }}
                            >
                                Usar
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

EditOffer.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: dashboard() },
        { title: 'Ofertar', href: dashboard() },
    ],
};
