import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { ClipboardList, Clock, Sprout, Warehouse } from 'lucide-react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Props = {
    stats: {
        pending: number;
        verifiedPostharvest: number;
        verifiedGrowers: number;
        openListings: number;
    };
};

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

export default function AdminDashboard({ stats }: Props) {
    return (
        <>
            <Head title="Panel admin" />
            <div className="flex flex-col gap-6 p-4">
                <h1 className="text-xl font-semibold">Panel de Florea</h1>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                    <StatCard
                        icon={ClipboardList}
                        label="Publicaciones abiertas"
                        value={stats.openListings}
                    />
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Panel admin', href: admin.dashboard() }],
};
