import { Head, router, usePage } from '@inertiajs/react';
import { Check, Copy, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import ListingController from '@/actions/App/Http/Controllers/ListingController';
import InputError from '@/components/input-error';
import { LengthPicker } from '@/components/length-picker';
import { OptionButtons } from '@/components/option-buttons';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { VarietyPicker } from '@/components/variety-picker';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import listings from '@/routes/listings';
import type {
    LengthRange,
    ListingItemDraft,
    ListingKind,
    Option,
    Unit,
    Variety,
} from '@/types';

type Prefill = {
    items: {
        variety_id: number;
        lengths: number[];
        quantity: number | null;
        unit: Unit | null;
    }[];
    destination: string | null;
    cut_point: string | null;
    notes: string | null;
};

type Props = {
    kind: ListingKind;
    duplicatedFrom: number | null;
    prefill: Prefill | null;
    varieties: Variety[];
    standardLengths: number[];
    customLength: LengthRange;
    units: Option[];
    destinations: Option[];
    cutPoints: Option[];
    notesMax: number;
    neededByDays: number;
};

const emptyItem = (): ListingItemDraft => ({
    variety_id: null,
    lengths: [],
    quantity: '',
    unit: 'hb',
});

const isoDate = (date: Date): string =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

const today = (): string => isoDate(new Date());

const tomorrow = (): string => {
    const date = new Date();
    date.setDate(date.getDate() + 1);

    return isoDate(date);
};

const maxDate = (days: number): string => {
    const date = new Date();
    date.setDate(date.getDate() + days);

    return isoDate(date);
};

export default function CreateListing({
    kind: initialKind,
    duplicatedFrom,
    prefill,
    varieties,
    standardLengths,
    customLength,
    units,
    destinations,
    cutPoints,
    notesMax,
    neededByDays,
}: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    // Regla 8: el tipo lo decide la empresa; aqui ya no se elige.
    const kind = initialKind;
    const [items, setItems] = useState<ListingItemDraft[]>(() =>
        prefill?.items.length
            ? prefill.items.map((item) => ({
                  variety_id: item.variety_id,
                  lengths: item.lengths,
                  quantity: item.quantity !== null ? String(item.quantity) : '',
                  unit: item.unit ?? 'hb',
              }))
            : [emptyItem()],
    );
    const [neededBy, setNeededBy] = useState<string>(today());
    const [pickingDate, setPickingDate] = useState(false);
    const [destination, setDestination] = useState<string | null>(
        prefill?.destination ?? null,
    );
    const [cutPoint, setCutPoint] = useState<string | null>(
        prefill?.cut_point ?? null,
    );
    const [notes, setNotes] = useState(prefill?.notes ?? '');
    const [processing, setProcessing] = useState(false);
    const [clientError, setClientError] = useState<string | null>(null);

    const isWant = kind === 'want';

    const patchItem = (index: number, patch: Partial<ListingItemDraft>) => {
        setClientError(null);
        setItems((current) =>
            current.map((item, i) =>
                i === index ? { ...item, ...patch } : item,
            ),
        );
    };

    const addItem = () => setItems((current) => [...current, emptyItem()]);

    const removeItem = (index: number) =>
        setItems((current) => current.filter((_, i) => i !== index));

    const submit = () => {
        const incomplete = items.findIndex((item) => item.variety_id === null);

        if (incomplete !== -1) {
            setClientError('Elige la variedad de cada línea.');

            return;
        }

        const noLengths = items.findIndex((item) => item.lengths.length === 0);

        if (noLengths !== -1) {
            setClientError('Elige al menos un largo en cada línea.');

            return;
        }

        setClientError(null);
        router.post(
            ListingController.store().url,
            {
                kind,
                needed_by: neededBy,
                destination,
                cut_point: cutPoint,
                notes: notes.trim() === '' ? null : notes.trim(),
                items: items.map((item) => ({
                    variety_id: item.variety_id,
                    lengths: item.lengths,
                    quantity:
                        item.quantity.trim() === '' ? null : item.quantity,
                    unit: item.unit,
                })),
            },
            {
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Nueva publicación" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5 p-4 pb-0">
                {/*
                    Regla 8: el tipo ya no se elige, lo decide la empresa.
                    Donde había dos botones ahora solo se enuncia qué se está
                    publicando, para que nadie se quede dudando.
                */}
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold">
                        {isWant ? 'Nuevo pedido' : 'Nueva publicación'}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {isWant
                            ? 'Dinos qué flor te falta para completar.'
                            : 'Dinos qué flor tienes disponible hoy.'}
                    </p>
                    {duplicatedFrom !== null && (
                        <p className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Copy className="size-4" />
                            Copiada de una publicación tuya. Revisa la fecha.
                        </p>
                    )}
                </div>

                {/* Líneas */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-base font-semibold">
                        {isWant ? '¿Qué necesitas?' : '¿Qué tienes?'}
                    </h2>

                    {items.map((item, index) => (
                        <ItemCard
                            key={index}
                            index={index}
                            item={item}
                            varieties={varieties}
                            usedVarieties={items
                                .filter((_, i) => i !== index)
                                .map((other) => other.variety_id)
                                .filter((id): id is number => id !== null)}
                            standardLengths={standardLengths}
                            customLength={customLength}
                            units={units}
                            canRemove={items.length > 1}
                            errors={errors}
                            onPatch={(patch) => patchItem(index, patch)}
                            onRemove={() => removeItem(index)}
                        />
                    ))}

                    <Button
                        type="button"
                        variant="outline"
                        className="h-12 text-base"
                        onClick={addItem}
                    >
                        <Plus />
                        Agregar variedad
                    </Button>

                    <InputError message={errors.items} />
                </section>

                {/* Datos generales */}
                <section className="flex flex-col gap-4 rounded-xl border bg-card p-4">
                    <div className="flex flex-col gap-2">
                        <Label className="text-base">
                            {isWant ? '¿Para cuándo?' : '¿Hasta cuándo?'}
                        </Label>
                        <div className="flex flex-wrap gap-2">
                            <DateButton
                                active={!pickingDate && neededBy === today()}
                                onClick={() => {
                                    setNeededBy(today());
                                    setPickingDate(false);
                                }}
                            >
                                Hoy
                            </DateButton>
                            <DateButton
                                active={!pickingDate && neededBy === tomorrow()}
                                onClick={() => {
                                    setNeededBy(tomorrow());
                                    setPickingDate(false);
                                }}
                            >
                                Mañana
                            </DateButton>
                            <DateButton
                                active={pickingDate}
                                onClick={() => setPickingDate(true)}
                            >
                                Elegir fecha
                            </DateButton>
                        </div>
                        {pickingDate && (
                            <Input
                                type="date"
                                value={neededBy}
                                min={today()}
                                max={maxDate(neededByDays)}
                                onChange={(e) => setNeededBy(e.target.value)}
                                className="h-12 max-w-52 text-base"
                            />
                        )}
                        <InputError message={errors.needed_by} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label className="text-base">
                            Destino{' '}
                            <span className="font-normal text-muted-foreground">
                                (opcional)
                            </span>
                        </Label>
                        <OptionButtons
                            options={destinations}
                            value={destination}
                            onChange={setDestination}
                            clearable
                            size="sm"
                        />
                        <InputError message={errors.destination} />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Label className="text-base">
                            Punto de corte{' '}
                            <span className="font-normal text-muted-foreground">
                                (opcional)
                            </span>
                        </Label>
                        <OptionButtons
                            options={cutPoints}
                            value={cutPoint}
                            onChange={setCutPoint}
                            clearable
                            size="sm"
                        />
                        <InputError message={errors.cut_point} />
                    </div>

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
                            placeholder="Algo más que deban saber"
                            className="min-h-20 text-base"
                        />
                        <p className="text-right text-xs text-muted-foreground tabular-nums">
                            {notes.length}/{notesMax}
                        </p>
                        <InputError message={errors.notes} />
                    </div>
                </section>

                {/* Guardar */}
                <div className="sticky bottom-0 -mx-4 border-t bg-background/95 px-4 py-3 backdrop-blur">
                    <InputError
                        message={clientError ?? errors.kind}
                        className="mb-2 text-center"
                    />
                    <Button
                        size="lg"
                        className="h-14 w-full text-lg"
                        onClick={submit}
                        disabled={processing}
                    >
                        <Check />
                        {isWant ? 'Publicar pedido' : 'Publicar disponible'}
                    </Button>
                    <p className="mt-2 text-center text-xs text-muted-foreground">
                        {isWant
                            ? 'El pedido queda visible 48 horas.'
                            : 'La publicación queda visible 24 horas.'}
                    </p>
                </div>
            </div>
        </>
    );
}

function DateButton({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-pressed={active}
            className={cn(
                'h-12 rounded-lg border-2 px-4 text-base font-medium transition-colors',
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-input bg-background',
            )}
        >
            {children}
        </button>
    );
}

function ItemCard({
    index,
    item,
    varieties,
    usedVarieties,
    standardLengths,
    customLength,
    units,
    canRemove,
    errors,
    onPatch,
    onRemove,
}: {
    index: number;
    item: ListingItemDraft;
    varieties: Variety[];
    usedVarieties: number[];
    standardLengths: number[];
    customLength: LengthRange;
    units: Option[];
    canRemove: boolean;
    errors: Record<string, string>;
    onPatch: (patch: Partial<ListingItemDraft>) => void;
    onRemove: () => void;
}) {
    const varietyError =
        errors[`items.${index}.variety_id`] ?? errors[`items.${index}`];
    const lengthsError =
        errors[`items.${index}.lengths`] ??
        Object.entries(errors).find(([key]) =>
            key.startsWith(`items.${index}.lengths.`),
        )?.[1];

    return (
        <div className="flex flex-col gap-3 rounded-xl border bg-card p-4">
            <div className="flex items-start gap-2">
                <div className="min-w-0 flex-1">
                    <VarietyPicker
                        varieties={varieties}
                        value={item.variety_id}
                        onChange={(variety_id) => onPatch({ variety_id })}
                        alreadyUsed={usedVarieties}
                        invalid={varietyError !== undefined}
                    />
                </div>
                {canRemove && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={onRemove}
                        aria-label={`Quitar la línea ${index + 1}`}
                        className="size-12 shrink-0 text-muted-foreground hover:text-destructive"
                    >
                        <Trash2 className="size-5" />
                    </Button>
                )}
            </div>
            <InputError message={varietyError} />

            <LengthPicker
                value={item.lengths}
                onChange={(lengths) => onPatch({ lengths })}
                standardLengths={standardLengths}
                customLength={customLength}
            />
            <InputError message={lengthsError} />

            <div className="flex flex-col gap-2">
                <p className="text-sm font-medium text-muted-foreground">
                    Cantidad (opcional)
                </p>
                <div className="flex flex-wrap items-center gap-2">
                    <Input
                        type="number"
                        inputMode="decimal"
                        min="0.01"
                        step="0.01"
                        value={item.quantity}
                        onChange={(e) => onPatch({ quantity: e.target.value })}
                        placeholder="Cuántas"
                        aria-label={`Cantidad de la línea ${index + 1}`}
                        className="h-11 max-w-28 text-base"
                    />
                    <OptionButtons
                        options={units}
                        value={item.unit}
                        onChange={(unit) => onPatch({ unit: unit as Unit })}
                        size="sm"
                    />
                </div>
                <InputError message={errors[`items.${index}.quantity`]} />
            </div>
        </div>
    );
}

CreateListing.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: dashboard() },
        { title: 'Nueva publicación', href: listings.create() },
    ],
};
