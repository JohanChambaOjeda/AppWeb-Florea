import { Head, router } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { ListingStatus, Paginated } from '@/types';

type Row = {
    id: number;
    kind: 'want' | 'have';
    kindLabel: string;
    company: string;
    companyType: string;
    summary: string;
    offersCount: number;
    acceptedCount: number;
    status: ListingStatus;
    statusLabel: string;
    createdAt: string;
};

type Props = {
    listings: Paginated<Row> & {
        links: { url: string | null; label: string; active: boolean }[];
    };
    date: string;
    isToday: boolean;
};

const hour = (iso: string): string =>
    new Date(iso).toLocaleTimeString('es-EC', {
        hour: '2-digit',
        minute: '2-digit',
    });

export default function AdminListings({ listings, date, isToday }: Props) {
    return (
        <>
            <Head title="Publicaciones del día" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">
                        Publicaciones {isToday ? 'de hoy' : `del ${date}`}
                    </h1>
                    <label className="flex items-center gap-2 text-sm">
                        <span className="text-muted-foreground">Día:</span>
                        <Input
                            type="date"
                            value={date}
                            max={new Date().toISOString().slice(0, 10)}
                            onChange={(e) =>
                                router.get(
                                    admin.listings.index().url,
                                    { date: e.target.value },
                                    { preserveScroll: true },
                                )
                            }
                            className="h-10 w-44"
                        />
                    </label>
                </div>

                <p className="text-sm text-muted-foreground">
                    {listings.total}{' '}
                    {listings.total === 1 ? 'publicación' : 'publicaciones'}.
                </p>

                {listings.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center">
                        <ClipboardList className="size-8 text-muted-foreground" />
                        <p className="text-muted-foreground">
                            No se publicó nada ese día.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-left">
                                <tr>
                                    <th className="p-3 font-semibold">Hora</th>
                                    <th className="p-3 font-semibold">Tipo</th>
                                    <th className="p-3 font-semibold">
                                        Empresa
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Líneas
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Ofertas
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {listings.data.map((row) => (
                                    <tr key={row.id}>
                                        <td className="p-3 whitespace-nowrap tabular-nums">
                                            {hour(row.createdAt)}
                                        </td>
                                        <td className="p-3">
                                            <span
                                                className={cn(
                                                    'rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap text-white',
                                                    row.kind === 'want'
                                                        ? 'bg-violet-600'
                                                        : 'bg-sky-500',
                                                )}
                                            >
                                                {row.kindLabel}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {row.company}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {row.companyType}
                                            </div>
                                        </td>
                                        <td className="max-w-md p-3 text-muted-foreground">
                                            {row.summary}
                                        </td>
                                        <td className="p-3 tabular-nums">
                                            {row.offersCount}
                                            {row.acceptedCount > 0 && (
                                                <span className="ml-1 text-xs font-medium text-green-700 dark:text-green-400">
                                                    ({row.acceptedCount}{' '}
                                                    aceptada
                                                    {row.acceptedCount === 1
                                                        ? ''
                                                        : 's'}
                                                    )
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            <span className="rounded border px-1.5 py-0.5 text-xs whitespace-nowrap">
                                                {row.statusLabel}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

AdminListings.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Publicaciones', href: admin.listings.index() },
    ],
};
