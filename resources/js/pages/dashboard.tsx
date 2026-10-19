import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import {
    Inbox,
    PackageSearch,
    Plus,
    SlidersHorizontal,
    Sprout,
} from 'lucide-react';
import { ListingCard } from '@/components/listing-card';
import { OptionButtons } from '@/components/option-buttons';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { VarietyPicker } from '@/components/variety-picker';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import listings from '@/routes/listings';
import type { HomeTab, ListingCard as Card, Paginated, Variety } from '@/types';

type Props = {
    tab: HomeTab;
    tabs: Record<HomeTab, number>;
    listings: Paginated<Card>;
    filters: {
        all: boolean;
        variety_id: number | null;
        length: number | null;
    };
    canFilterAll: boolean;
    varieties: Variety[];
    standardLengths: number[];
};

const TABS: { value: HomeTab; label: string; short: string }[] = [
    { value: 'for-me', label: 'Pedidos para mí', short: 'Para mí' },
    { value: 'available', label: 'Disponible hoy', short: 'Disponible' },
    { value: 'mine', label: 'Mis publicaciones', short: 'Mías' },
];

export default function Dashboard({
    tab,
    tabs,
    listings: page,
    filters,
    canFilterAll,
    varieties,
    standardLengths,
}: Props) {
    /** Visita normal (no parcial) para que la lista vuelva a empezar. */
    const visit = (query: Record<string, string | number | boolean | null>) => {
        const clean = Object.fromEntries(
            Object.entries(query).filter(
                ([, value]) =>
                    value !== null && value !== false && value !== '',
            ),
        );

        router.get(dashboard().url, clean, { preserveScroll: true });
    };

    /** Cambiar de pestaña empieza limpio: los filtros son de cada pestaña. */
    const goTab = (value: HomeTab) => visit({ tab: value });

    /** Cambiar un filtro conserva los demás de la pestaña actual. */
    const setFilter = (
        patch: Record<string, string | number | boolean | null>,
    ) =>
        visit({
            tab,
            all: filters.all,
            variety_id: filters.variety_id,
            length: filters.length,
            ...patch,
        });

    return (
        <>
            <Head title="Inicio" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                {/* Publicar */}
                <div className="grid grid-cols-2 gap-3">
                    <NewListingButton
                        kind="want"
                        icon={PackageSearch}
                        title="Busco flor"
                        hint="Me falta para completar"
                        tone="violet"
                    />
                    <NewListingButton
                        kind="have"
                        icon={Sprout}
                        title="Tengo disponible"
                        hint="Me sobra flor"
                        tone="sky"
                    />
                </div>

                {/* Pestañas */}
                <div
                    role="tablist"
                    className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1"
                >
                    {TABS.map((item) => {
                        const active = item.value === tab;
                        const count = tabs[item.value] ?? 0;

                        return (
                            <button
                                key={item.value}
                                type="button"
                                role="tab"
                                aria-selected={active}
                                onClick={() => goTab(item.value)}
                                className={cn(
                                    'flex h-11 shrink-0 items-center gap-2 rounded-lg border-2 px-3 text-sm font-medium transition-colors',
                                    active
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-input bg-background',
                                )}
                            >
                                <span className="hidden sm:inline">
                                    {item.label}
                                </span>
                                <span className="sm:hidden">{item.short}</span>
                                {count > 0 && (
                                    <span
                                        className={cn(
                                            'rounded px-1.5 text-xs font-semibold tabular-nums',
                                            active
                                                ? 'bg-white/25'
                                                : 'bg-muted text-muted-foreground',
                                        )}
                                    >
                                        {count}
                                    </span>
                                )}
                            </button>
                        );
                    })}
                </div>

                {/* Filtros */}
                {tab === 'for-me' && canFilterAll && (
                    <label className="flex items-center gap-3 rounded-xl border bg-card px-4 py-3">
                        <input
                            type="checkbox"
                            checked={filters.all}
                            onChange={(e) =>
                                setFilter({ all: e.target.checked })
                            }
                            className="size-5 accent-violet-600"
                        />
                        <span className="flex flex-col">
                            <span className="text-base font-medium">
                                Ver todos
                            </span>
                            <span className="text-sm text-muted-foreground">
                                También los pedidos de variedades que no
                                siembras.
                            </span>
                        </span>
                    </label>
                )}

                {tab === 'available' && (
                    <div className="flex flex-col gap-3 rounded-xl border bg-card p-4">
                        <p className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                            <SlidersHorizontal className="size-4" />
                            Filtrar
                        </p>

                        <div className="flex flex-col gap-2">
                            <VarietyPicker
                                varieties={varieties}
                                value={filters.variety_id}
                                onChange={(variety_id) =>
                                    setFilter({ variety_id })
                                }
                            />
                            <OptionButtons
                                options={standardLengths.map((length) => ({
                                    value: String(length),
                                    label: String(length),
                                }))}
                                value={
                                    filters.length !== null
                                        ? String(filters.length)
                                        : null
                                }
                                onChange={(length) => setFilter({ length })}
                                clearable
                                size="sm"
                            />
                        </div>

                        {(filters.variety_id !== null ||
                            filters.length !== null) && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="self-start"
                                onClick={() =>
                                    setFilter({
                                        variety_id: null,
                                        length: null,
                                    })
                                }
                            >
                                Quitar filtros
                            </Button>
                        )}
                    </div>
                )}

                {/* Lista */}
                {page.data.length === 0 ? (
                    <EmptyState tab={tab} filters={filters} />
                ) : (
                    <InfiniteScroll
                        data="listings"
                        manual
                        className="flex flex-col gap-3"
                        next={({ loading, fetch, hasMore }) =>
                            hasMore ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="mt-3 h-12 w-full text-base"
                                    disabled={loading}
                                    onClick={fetch}
                                >
                                    {loading ? <Spinner /> : null}
                                    Cargar más
                                </Button>
                            ) : (
                                <p className="mt-3 text-center text-sm text-muted-foreground">
                                    {page.total > 0 && 'No hay más.'}
                                </p>
                            )
                        }
                    >
                        {page.data.map((listing) => (
                            <ListingCard key={listing.id} listing={listing} />
                        ))}
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}

function EmptyState({
    tab,
    filters,
}: {
    tab: HomeTab;
    filters: Props['filters'];
}) {
    const filtering = filters.variety_id !== null || filters.length !== null;

    const message = (() => {
        if (tab === 'mine') {
            return 'Todavía no has publicado nada. Usa los botones de arriba.';
        }

        if (tab === 'available') {
            return filtering
                ? 'Nadie tiene esa flor disponible ahora. Prueba quitando el filtro.'
                : 'Por ahora nadie publicó flor disponible.';
        }

        return filters.all
            ? 'Ahora mismo no hay pedidos abiertos.'
            : 'No hay pedidos de tus variedades ahora. Enciende “Ver todos” para ver el resto.';
    })();

    return (
        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center">
            <Inbox className="size-8 text-muted-foreground" />
            <p className="text-base text-muted-foreground">{message}</p>
        </div>
    );
}

function NewListingButton({
    kind,
    icon: Icon,
    title,
    hint,
    tone,
}: {
    kind: 'want' | 'have';
    icon: typeof PackageSearch;
    title: string;
    hint: string;
    tone: 'violet' | 'sky';
}) {
    return (
        <Link
            href={listings.create({ query: { kind } })}
            className={cn(
                'flex min-h-24 flex-col justify-between rounded-xl p-4 text-white transition-opacity hover:opacity-90',
                tone === 'violet' ? 'bg-violet-600' : 'bg-sky-500',
            )}
        >
            <div className="flex items-center justify-between">
                <Icon className="size-6" />
                <Plus className="size-5 opacity-70" />
            </div>
            <span className="flex flex-col">
                <span className="text-base font-semibold">{title}</span>
                <span className="text-xs text-white/80">{hint}</span>
            </span>
        </Link>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
};
