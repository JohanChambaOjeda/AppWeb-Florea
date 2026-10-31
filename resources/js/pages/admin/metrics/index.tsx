import { Head, router } from '@inertiajs/react';
import { Download, Target } from 'lucide-react';
import { OptionButtons } from '@/components/option-buttons';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { Option } from '@/types';

type Week = {
    from: string;
    to: string;
    listings: number;
    wants: number;
    offers: number;
    deals: number;
    firstOffer: {
        measured: number;
        averageMinutes: number | null;
        medianMinutes: number | null;
    };
    fast: {
        total: number;
        withinHours: number;
        pct: number | null;
        goalMet: boolean;
    };
    activeCompanies: {
        total: number;
        postharvest: number;
        growers: number;
    };
};

type Props = {
    week: Week;
    weeks: Option[];
    selected: string;
    goal: { hours: number; pct: number };
};

/** "2 h 15 min", "45 min", "1 día 3 h". */
function humanMinutes(total: number | null): string {
    if (total === null) {
        return '—';
    }

    const days = Math.floor(total / 1440);
    const hours = Math.floor((total % 1440) / 60);
    const minutes = total % 60;

    if (days > 0) {
        return `${days} ${days === 1 ? 'día' : 'días'}${hours > 0 ? ` ${hours} h` : ''}`;
    }

    if (hours > 0) {
        return `${hours} h${minutes > 0 ? ` ${minutes} min` : ''}`;
    }

    return `${minutes} min`;
}

function Stat({
    label,
    value,
    hint,
}: {
    label: string;
    value: string | number;
    hint?: string;
}) {
    return (
        <div className="flex flex-col gap-1 rounded-xl border bg-card p-4">
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-3xl font-semibold tabular-nums">{value}</span>
            {hint !== undefined && (
                <span className="text-xs text-muted-foreground">{hint}</span>
            )}
        </div>
    );
}

export default function AdminMetrics({ week, weeks, selected, goal }: Props) {
    const { fast, firstOffer, activeCompanies } = week;

    return (
        <>
            <Head title="Métricas del piloto" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">
                        Métricas del piloto
                    </h1>
                    <a
                        href={admin.metrics.export().url}
                        className="flex h-11 items-center gap-2 rounded-lg border-2 border-input px-4 text-sm font-medium"
                    >
                        <Download className="size-4" />
                        Exportar CSV
                    </a>
                </div>

                <div className="flex flex-col gap-2">
                    <span className="text-sm text-muted-foreground">
                        Semana
                    </span>
                    <OptionButtons
                        options={weeks}
                        value={selected}
                        onChange={(value) =>
                            router.get(
                                admin.metrics.index().url,
                                value !== null ? { week: value } : {},
                                { preserveScroll: true },
                            )
                        }
                        size="sm"
                    />
                </div>

                {/* La meta */}
                <section
                    className={cn(
                        'flex flex-col gap-2 rounded-xl border-2 p-5',
                        fast.pct === null
                            ? 'border-input bg-card'
                            : fast.goalMet
                              ? 'border-green-400 bg-green-50 dark:bg-green-950/40'
                              : 'border-amber-400 bg-amber-50 dark:bg-amber-950/40',
                    )}
                >
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Target className="size-4" />
                        La meta del piloto: {goal.pct}% o más de los “Busco” con
                        oferta en menos de {goal.hours} horas
                    </div>
                    <div className="text-5xl font-semibold tabular-nums">
                        {fast.pct === null ? '—' : `${fast.pct}%`}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {fast.total === 0
                            ? 'No hubo pedidos esta semana.'
                            : `${fast.withinHours} de ${fast.total} ${
                                  fast.total === 1 ? 'pedido' : 'pedidos'
                              } recibieron oferta en menos de ${goal.hours} h.`}
                        {fast.pct !== null &&
                            (fast.goalMet
                                ? ' Meta cumplida.'
                                : ' Todavía por debajo de la meta.')}
                    </p>
                </section>

                {/* Tiempo hasta la primera oferta */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        Tiempo hasta la primera oferta
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Stat
                            label="Mediana"
                            value={humanMinutes(firstOffer.medianMinutes)}
                            hint="La mitad tardó menos que esto"
                        />
                        <Stat
                            label="Promedio"
                            value={humanMinutes(firstOffer.averageMinutes)}
                            hint={`Sobre ${firstOffer.measured} ${
                                firstOffer.measured === 1
                                    ? 'publicación que recibió oferta'
                                    : 'publicaciones que recibieron oferta'
                            }`}
                        />
                    </div>
                </section>

                {/* Movimiento de la semana */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        Movimiento de la semana
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Stat label="Publicaciones" value={week.listings} />
                        <Stat label="De ellas, “Busco”" value={week.wants} />
                        <Stat label="Ofertas" value={week.offers} />
                        <Stat label="Tratos cerrados" value={week.deals} />
                    </div>
                </section>

                {/* Empresas activas */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        Empresas activas (publicaron, ofertaron o entraron)
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Stat label="Total" value={activeCompanies.total} />
                        <Stat
                            label="Poscosechas"
                            value={activeCompanies.postharvest}
                        />
                        <Stat
                            label="Productores"
                            value={activeCompanies.growers}
                        />
                    </div>
                </section>
            </div>
        </>
    );
}

AdminMetrics.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Métricas', href: admin.metrics.index() },
    ],
};
