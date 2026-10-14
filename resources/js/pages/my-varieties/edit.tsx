import { Head, router, usePage } from '@inertiajs/react';
import { Check, Plus, Search, Sprout, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import MyVarietiesController from '@/actions/App/Http/Controllers/MyVarietiesController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/my-varieties';

type Variety = { id: number; name: string; color: string | null };

type Props = {
    varieties: Variety[];
    selected: Record<string, number[]>;
    standardLengths: number[];
    customLength: { min: number; max: number };
    onboarding: boolean;
};

/** variety_id → largos elegidos. Una variedad marcada está en el mapa. */
type Selection = Record<number, number[]>;

export default function MyVarieties({
    varieties,
    selected: initialSelected,
    standardLengths,
    customLength,
    onboarding,
}: Props) {
    const { errors } = usePage().props as { errors: Record<string, string> };
    const [selection, setSelection] = useState<Selection>(() =>
        Object.fromEntries(
            Object.entries(initialSelected).map(([id, lengths]) => [
                Number(id),
                lengths,
            ]),
        ),
    );
    const [search, setSearch] = useState('');
    const [processing, setProcessing] = useState(false);
    const [clientError, setClientError] = useState<string | null>(null);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term
            ? varieties.filter((v) => v.name.toLowerCase().includes(term))
            : varieties;
    }, [search, varieties]);

    // Orden del envío = orden de la lista, para ubicar los errores del servidor.
    const chosen = varieties.filter((v) => v.id in selection);

    const errorFor = (varietyId: number): string | undefined => {
        const index = chosen.findIndex((v) => v.id === varietyId);

        if (index === -1) {
            return undefined;
        }

        return (
            errors[`varieties.${index}.lengths`] ??
            Object.entries(errors).find(([key]) =>
                key.startsWith(`varieties.${index}.`),
            )?.[1]
        );
    };

    const toggleVariety = (id: number) => {
        setClientError(null);
        setSelection((current) => {
            const next = { ...current };

            if (id in next) {
                delete next[id];
            } else {
                next[id] = [];
            }

            return next;
        });
    };

    const toggleLength = (id: number, length: number) => {
        setSelection((current) => {
            const lengths = current[id] ?? [];

            return {
                ...current,
                [id]: lengths.includes(length)
                    ? lengths.filter((l) => l !== length)
                    : [...lengths, length].sort((a, b) => a - b),
            };
        });
    };

    const save = () => {
        if (chosen.length === 0) {
            setClientError('Marca al menos una variedad.');

            return;
        }

        const missing = chosen.find((v) => selection[v.id].length === 0);

        if (missing) {
            setClientError(`Elige al menos un largo para ${missing.name}.`);
            document
                .getElementById(`variety-${missing.id}`)
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });

            return;
        }

        setClientError(null);
        router.put(
            MyVarietiesController.update().url,
            {
                varieties: chosen.map((v) => ({
                    variety_id: v.id,
                    lengths: selection[v.id],
                })),
            },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <>
            <Head title="Mis variedades" />

            <div className="flex flex-col gap-4 p-4 pb-0">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold">Mis variedades</h1>
                    {onboarding ? (
                        <div className="flex items-start gap-3 rounded-xl border border-primary/30 bg-violet-50 p-4 text-violet-900 dark:bg-violet-950/40 dark:text-violet-100">
                            <Sprout className="mt-0.5 size-5 shrink-0" />
                            <p className="text-base font-medium">
                                Marca lo que siembras para que te lleguen los
                                pedidos.
                            </p>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Marca lo que siembras y en qué largos. Puedes
                            cambiarlo cuando quieras.
                        </p>
                    )}
                </div>

                <div className="sticky top-0 z-10 -mx-4 bg-background px-4 py-2">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar variedad"
                            className="h-12 pl-10 text-base"
                        />
                    </div>
                </div>

                <ul className="flex flex-col gap-2">
                    {filtered.map((variety) => (
                        <VarietyRow
                            key={variety.id}
                            variety={variety}
                            lengths={selection[variety.id]}
                            standardLengths={standardLengths}
                            customLength={customLength}
                            error={errorFor(variety.id)}
                            onToggle={() => toggleVariety(variety.id)}
                            onToggleLength={(length) =>
                                toggleLength(variety.id, length)
                            }
                        />
                    ))}
                    {filtered.length === 0 && (
                        <li className="rounded-xl border border-dashed p-6 text-center text-muted-foreground">
                            No hay variedades con “{search}”.
                        </li>
                    )}
                </ul>

                <div className="sticky bottom-0 -mx-4 mt-2 border-t bg-background/95 px-4 py-3 backdrop-blur">
                    <InputError
                        message={clientError ?? errors.varieties}
                        className="mb-2 text-center"
                    />
                    <Button
                        size="lg"
                        className="h-14 w-full text-lg"
                        onClick={save}
                        disabled={processing}
                    >
                        {processing ? <Spinner /> : <Check />}
                        Guardar
                        {chosen.length > 0 &&
                            ` (${chosen.length} ${chosen.length === 1 ? 'variedad' : 'variedades'})`}
                    </Button>
                </div>
            </div>
        </>
    );
}

function VarietyRow({
    variety,
    lengths,
    standardLengths,
    customLength,
    error,
    onToggle,
    onToggleLength,
}: {
    variety: Variety;
    lengths: number[] | undefined;
    standardLengths: number[];
    customLength: { min: number; max: number };
    error?: string;
    onToggle: () => void;
    onToggleLength: (length: number) => void;
}) {
    const checked = lengths !== undefined;
    const [custom, setCustom] = useState('');
    const [customError, setCustomError] = useState<string | null>(null);

    // Largos especiales ya elegidos (ej. 120) se muestran como botones extra.
    const extraLengths = (lengths ?? []).filter(
        (l) => !standardLengths.includes(l),
    );

    const addCustom = () => {
        const value = Number(custom);

        if (
            !Number.isInteger(value) ||
            value < customLength.min ||
            value > customLength.max
        ) {
            setCustomError(
                `Escribe un largo entre ${customLength.min} y ${customLength.max} cm.`,
            );

            return;
        }

        if (!lengths?.includes(value)) {
            onToggleLength(value);
        }

        setCustom('');
        setCustomError(null);
    };

    return (
        <li
            id={`variety-${variety.id}`}
            className={cn(
                'rounded-xl border bg-card transition-colors',
                checked &&
                    'border-primary bg-violet-50/60 dark:bg-violet-950/30',
            )}
        >
            <button
                type="button"
                onClick={onToggle}
                aria-pressed={checked}
                className="flex min-h-14 w-full items-center gap-3 px-4 py-3 text-left"
            >
                <span
                    className={cn(
                        'flex size-7 shrink-0 items-center justify-center rounded-md border-2',
                        checked
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input bg-background',
                    )}
                >
                    {checked && <Check className="size-5" strokeWidth={3} />}
                </span>
                <span className="flex flex-col">
                    <span className="text-base font-medium">
                        {variety.name}
                    </span>
                    {variety.color && (
                        <span className="text-sm text-muted-foreground">
                            {variety.color}
                        </span>
                    )}
                </span>
                {checked && lengths.length > 0 && (
                    <span className="ml-auto text-sm font-medium text-primary">
                        {lengths.join(' / ')}
                    </span>
                )}
            </button>

            {checked && (
                <div className="flex flex-col gap-3 px-4 pb-4">
                    <p className="text-sm text-muted-foreground">
                        Largos (cm):
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {[...standardLengths, ...extraLengths].map((length) => {
                            const on = lengths.includes(length);

                            return (
                                <button
                                    key={length}
                                    type="button"
                                    onClick={() => onToggleLength(length)}
                                    aria-pressed={on}
                                    className={cn(
                                        'h-11 min-w-14 rounded-lg border-2 px-3 text-base font-semibold tabular-nums transition-colors',
                                        on
                                            ? 'border-sky-500 bg-sky-500 text-white'
                                            : 'border-input bg-background',
                                    )}
                                >
                                    {length}
                                </button>
                            );
                        })}
                    </div>

                    <div className="flex gap-2">
                        <Input
                            type="number"
                            inputMode="numeric"
                            min={customLength.min}
                            max={customLength.max}
                            value={custom}
                            onChange={(e) => {
                                setCustom(e.target.value);
                                setCustomError(null);
                            }}
                            onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    addCustom();
                                }
                            }}
                            placeholder="Otro largo"
                            aria-label={`Otro largo para ${variety.name}`}
                            className="h-11 max-w-36 text-base"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11"
                            onClick={addCustom}
                            disabled={custom === ''}
                        >
                            <Plus />
                            Agregar
                        </Button>
                    </div>
                    {extraLengths.length > 0 && (
                        <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <X className="size-3" />
                            Toca un largo especial para quitarlo.
                        </p>
                    )}

                    <InputError message={customError ?? error} />
                </div>
            )}
        </li>
    );
}

MyVarieties.layout = {
    breadcrumbs: [{ title: 'Mis variedades', href: edit() }],
};
