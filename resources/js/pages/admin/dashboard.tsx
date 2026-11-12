import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ClipboardList,
    Clock,
    HandCoins,
    Handshake,
    Sprout,
    Star,
    Timer,
    Warehouse,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Props = {
    stats: {
        pending: number;
        verifiedPostharvest: number;
        verifiedGrowers: number;
        openListings: number;
        listingsToday: number;
        offersToday: number;
        dealsThisWeek: number;
        lowRatingsThisWeek: number;
    };
    timeToFirstOffer: {
        minutes: number | null;
        listings: number;
    };
};

/** "2 h 15 min", "45 min", "1 día 3 h". */
function humanMinutes(total: number): string {
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

function StatCard({
    icon: Icon,
    label,
    value,
    highlight = false,
    children,
}: {
    icon: LucideIcon;
    label: string;
    value: number;
    highlight?: boolean;
    children?: ReactNode;
}) {
    return (
        <div
            className={cn(
                'flex flex-col gap-3 rounded-xl border p-5',
                highlight && value > 0
                    ? 'border-primary bg-violet-50 dark:bg-violet-950/40'
                    : 'border-sidebar-border/70 bg-card',
            )}
        >
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Icon className="size-4" />
                {label}
            </div>
            <div className="text-4xl font-semibold tabular-nums">{value}</div>
            {children}
        </div>
    );
}

export default function AdminDashboard({ stats, timeToFirstOffer }: Props) {
    return (
        <>
            <Head title="Panel admin" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold">Panel de Florea</h1>

                {/* La métrica del piloto */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        La métrica del piloto
                    </h2>
                    <div className="flex flex-col gap-2 rounded-xl border-2 border-primary bg-violet-50 p-5 dark:bg-violet-950/40">
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Timer className="size-4" />
                            Tiempo promedio hasta la primera oferta
                        </div>
                        <div className="text-5xl font-semibold tabular-nums">
                            {timeToFirstOffer.minutes === null
                                ? '—'
                                : humanMinutes(timeToFirstOffer.minutes)}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {timeToFirstOffer.listings === 0
                                ? 'Todavía no hay publicaciones con ofertas.'
                                : `Sobre ${timeToFirstOffer.listings} ${
                                      timeToFirstOffer.listings === 1
                                          ? 'publicación que recibió oferta'
                                          : 'publicaciones que recibieron oferta'
                                  }.`}
                        </p>
                    </div>
                </section>

                {/* Movimiento */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        Movimiento
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatCard
                            icon={ClipboardList}
                            label="Publicaciones abiertas"
                            value={stats.openListings}
                        />
                        <StatCard
                            icon={ClipboardList}
                            label="Publicadas hoy"
                            value={stats.listingsToday}
                        >
                            <Link
                                href={admin.listings.index()}
                                className="text-sm font-medium text-primary underline underline-offset-4"
                            >
                                Ver las del día
                            </Link>
                        </StatCard>
                        <StatCard
                            icon={HandCoins}
                            label="Ofertas de hoy"
                            value={stats.offersToday}
                        />
                        <StatCard
                            icon={Handshake}
                            label="Tratos de la semana"
                            value={stats.dealsThisWeek}
                        >
                            <Link
                                href={admin.deals.index()}
                                className="text-sm font-medium text-primary underline underline-offset-4"
                            >
                                Ver tratos
                            </Link>
                        </StatCard>
                        {/*
                            Las malas de la semana. Van aqui arriba a
                            proposito: una calificacion de 1 estrella que
                            nadie mira en tres dias es una empresa que ya se
                            fue de Florea sin decir por que.
                        */}
                        <StatCard
                            icon={Star}
                            label="Calificaciones bajas"
                            value={stats.lowRatingsThisWeek}
                            highlight={stats.lowRatingsThisWeek > 0}
                        >
                            <Link
                                href={admin.ratings.index({
                                    query: { low: 1 },
                                })}
                                className="text-sm font-medium text-primary underline underline-offset-4"
                            >
                                {stats.lowRatingsThisWeek > 0
                                    ? 'Revisar ahora'
                                    : 'Ver calificaciones'}
                            </Link>
                        </StatCard>
                    </div>
                </section>

                {/* Empresas */}
                <section className="flex flex-col gap-3">
                    <h2 className="text-sm font-semibold text-muted-foreground">
                        Empresas
                    </h2>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <StatCard
                            icon={Clock}
                            label="Empresas por verificar"
                            value={stats.pending}
                            highlight
                        >
                            <Link
                                href={admin.companies.index()}
                                className="text-sm font-medium text-primary underline underline-offset-4"
                            >
                                {stats.pending > 0
                                    ? 'Revisar ahora'
                                    : 'Ver empresas'}
                            </Link>
                        </StatCard>
                        <StatCard
                            icon={Warehouse}
                            label="Poscosechas verificadas"
                            value={stats.verifiedPostharvest}
                        />
                        <StatCard
                            icon={Sprout}
                            label="Productores verificados"
                            value={stats.verifiedGrowers}
                        />
                    </div>
                </section>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Panel admin', href: admin.dashboard() }],
};
