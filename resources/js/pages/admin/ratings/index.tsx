import { Head, Link, router } from '@inertiajs/react';
import {
    Clock3,
    ExternalLink,
    Star,
    TriangleAlert,
    Wallet,
} from 'lucide-react';
import { OptionButtons } from '@/components/option-buttons';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import listings from '@/routes/listings';
import type { Option, Paginated } from '@/types';

type Row = {
    id: number;
    createdAt: string;
    from: string;
    fromType: string;
    to: string;
    toType: string;
    score: number;
    /** De la poscosecha al productor. */
    onTime: boolean | null;
    /** Del productor a la poscosecha. */
    paidAsAgreed: string | null;
    honoredAgreement: boolean | null;
    paymentTerm: string | null;
    comment: string | null;
    dealId: number;
    listingId: number;
    closedAt: string;
};

type Props = {
    ratings: Paginated<Row>;
    filters: {
        direction: string | null;
        company_id: number | null;
        low: boolean;
    };
    companies: Option[];
    lowThisWeek: number;
    total: number;
};

const DIRECTIONS: Option[] = [
    { value: 'postharvest_to_grower', label: 'Poscosecha → Productor' },
    { value: 'grower_to_postharvest', label: 'Productor → Poscosecha' },
];

const when = (iso: string): string =>
    new Date(iso).toLocaleString('es-EC', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });

export default function AdminRatings({
    ratings,
    filters,
    companies,
    lowThisWeek,
    total,
}: Props) {
    const setFilter = (
        patch: Record<string, string | number | boolean | null>,
    ) =>
        router.get(
            admin.ratings.index().url,
            Object.fromEntries(
                Object.entries({ ...filters, ...patch }).filter(
                    ([, value]) =>
                        value !== null && value !== false && value !== '',
                ),
            ),
            { preserveScroll: true },
        );

    const filtering =
        filters.direction !== null ||
        filters.company_id !== null ||
        filters.low;

    return (
        <>
            <Head title="Calificaciones" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                    <h1 className="text-xl font-semibold">Calificaciones</h1>
                    <p className="text-sm text-muted-foreground">
                        {total} en total
                    </p>
                </div>

                {lowThisWeek > 0 && (
                    <button
                        type="button"
                        onClick={() => setFilter({ low: true })}
                        className="flex items-center gap-2 rounded-xl border-2 border-amber-300 bg-amber-50 p-3 text-left text-sm dark:border-amber-900 dark:bg-amber-950/40"
                    >
                        <TriangleAlert className="size-4 shrink-0" />
                        <span>
                            <strong className="font-semibold">
                                {lowThisWeek}
                            </strong>{' '}
                            de 1 o 2 estrellas esta semana. Míralas antes de que
                            la relación se rompa.
                        </span>
                    </button>
                )}

                {/* Filtros */}
                <div className="flex flex-col gap-3 rounded-xl border bg-card p-4">
                    <div className="flex flex-col gap-2">
                        <p className="text-sm font-medium">Dirección</p>
                        <OptionButtons
                            options={DIRECTIONS}
                            value={filters.direction}
                            onChange={(direction) => setFilter({ direction })}
                            clearable
                            size="sm"
                        />
                    </div>

                    <div className="flex flex-col gap-2">
                        <p className="text-sm font-medium">Empresa</p>
                        <select
                            value={filters.company_id ?? ''}
                            onChange={(e) =>
                                setFilter({
                                    company_id: e.target.value || null,
                                })
                            }
                            className="h-11 rounded-md border border-input bg-background px-3 text-base"
                        >
                            <option value="">Todas</option>
                            {companies.map((company) => (
                                <option
                                    key={company.value}
                                    value={company.value}
                                >
                                    {company.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <label className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            checked={filters.low}
                            onChange={(e) =>
                                setFilter({ low: e.target.checked })
                            }
                            className="size-5 accent-violet-600"
                        />
                        <span className="text-base">Solo 1 y 2 estrellas</span>
                    </label>

                    {filtering && (
                        <Button
                            variant="ghost"
                            className="self-start"
                            onClick={() =>
                                router.get(admin.ratings.index().url)
                            }
                        >
                            Quitar filtros
                        </Button>
                    )}
                </div>

                {ratings.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-sm text-muted-foreground">
                        {filtering
                            ? 'Ninguna calificación coincide con el filtro.'
                            : 'Todavía no hay calificaciones.'}
                    </p>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {ratings.data.map((rating) => (
                            <RatingCard key={rating.id} rating={rating} />
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

function RatingCard({ rating }: { rating: Row }) {
    const low = rating.score <= 2;

    return (
        <li
            className={cn(
                'flex flex-col gap-3 rounded-xl border bg-card p-4',
                low && 'border-amber-300 dark:border-amber-900',
            )}
        >
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="flex flex-col gap-0.5">
                    <p className="text-base font-semibold">
                        {rating.from}{' '}
                        <span className="font-normal text-muted-foreground">
                            →
                        </span>{' '}
                        {rating.to}
                    </p>
                    <p className="text-xs text-muted-foreground">
                        {rating.fromType} califica a {rating.toType} ·{' '}
                        {when(rating.createdAt)}
                    </p>
                </div>

                <span
                    className="flex items-center gap-1"
                    aria-label={`${rating.score} de 5`}
                >
                    {[1, 2, 3, 4, 5].map((value) => (
                        <Star
                            key={value}
                            className={cn(
                                'size-4',
                                value <= rating.score
                                    ? 'fill-amber-400 text-amber-400'
                                    : 'text-muted-foreground/40',
                            )}
                        />
                    ))}
                </span>
            </div>

            {/* Las respuestas: cada dirección contestó lo suyo. */}
            <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground">
                {rating.onTime !== null && (
                    <span className="flex items-center gap-1">
                        <Clock3 className="size-3.5" />
                        {rating.onTime ? 'Llegó a tiempo' : 'Llegó tarde'}
                    </span>
                )}

                {rating.paidAsAgreed !== null && (
                    <span className="flex items-center gap-1">
                        <Wallet className="size-3.5" />
                        Pago: {rating.paidAsAgreed}
                        {rating.paymentTerm !== null &&
                            ` (acordado: ${rating.paymentTerm})`}
                    </span>
                )}

                {rating.honoredAgreement !== null && (
                    <span>
                        {rating.honoredAgreement
                            ? 'Respetó lo acordado'
                            : 'NO respetó lo acordado'}
                    </span>
                )}
            </div>

            {/*
                El comentario completo, sin recortar. Es lo único que esta
                pantalla tiene y las de las empresas no: en la app se ven
                números, porque un comentario crudo entre dos que se conocen
                del cantón acaba en pelea.
            */}
            {rating.comment !== null && (
                <blockquote className="rounded-lg border-l-4 border-muted bg-muted/40 p-3 text-sm whitespace-pre-line">
                    {rating.comment}
                </blockquote>
            )}

            <Link
                href={listings.show(rating.listingId)}
                className="flex items-center gap-1 self-start text-sm font-medium underline-offset-4 hover:underline"
            >
                <ExternalLink className="size-3.5" />
                Ver la publicación del trato #{rating.dealId}
            </Link>
        </li>
    );
}

AdminRatings.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Calificaciones', href: admin.ratings.index() },
    ],
};
