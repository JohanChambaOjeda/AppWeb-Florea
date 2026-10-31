import { Head } from '@inertiajs/react';
import { ArrowRight, Handshake, Star } from 'lucide-react';
import { money } from '@/lib/format';
import admin from '@/routes/admin';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    buyer: string;
    buyerType: string;
    seller: string;
    sellerType: string;
    closedAt: string;
    listingId: number;
    kindLabel: string;
    what: string;
    pricePerStem: string | null;
    delivery: string;
    delivered: string | null;
    rejectedRange: string | null;
    ratings: {
        id: number;
        from: string;
        score: number;
        onTime: boolean | null;
        comment: string | null;
    }[];
};

type Props = {
    deals: Paginated<Row>;
};

const dateTime = (iso: string): string =>
    new Date(iso).toLocaleString('es-EC', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });

export default function AdminDeals({ deals }: Props) {
    return (
        <>
            <Head title="Tratos cerrados" />

            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Tratos cerrados</h1>
                <p className="text-sm text-muted-foreground">
                    {deals.total} {deals.total === 1 ? 'trato' : 'tratos'} desde
                    que arrancó el piloto.
                </p>

                {deals.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center">
                        <Handshake className="size-8 text-muted-foreground" />
                        <p className="text-muted-foreground">
                            Todavía no se ha cerrado ningún trato.
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-left">
                                <tr>
                                    <th className="p-3 font-semibold">
                                        Cerrado
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Compra
                                    </th>
                                    <th className="p-3 font-semibold">Vende</th>
                                    <th className="p-3 font-semibold">
                                        Lo acordado
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Precio
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Entrega
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Resultado
                                    </th>
                                    <th className="p-3 font-semibold">
                                        Comentarios (solo admin)
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {deals.data.map((deal) => (
                                    <tr key={deal.id}>
                                        <td className="p-3 whitespace-nowrap tabular-nums">
                                            {dateTime(deal.closedAt)}
                                        </td>
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {deal.buyer}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {deal.buyerType}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            <div className="flex items-center gap-1">
                                                <ArrowRight className="size-3.5 text-muted-foreground" />
                                                <span className="font-medium">
                                                    {deal.seller}
                                                </span>
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {deal.sellerType}
                                            </div>
                                        </td>
                                        <td className="max-w-md p-3">
                                            {deal.what}
                                            <div className="text-xs text-muted-foreground">
                                                desde un “{deal.kindLabel}”
                                            </div>
                                        </td>
                                        <td className="p-3 font-semibold whitespace-nowrap tabular-nums">
                                            {money(deal.pricePerStem)}
                                        </td>
                                        <td className="p-3 whitespace-nowrap">
                                            {deal.delivery}
                                        </td>
                                        <td className="p-3">
                                            {deal.delivered === null ? (
                                                <span className="text-xs text-muted-foreground">
                                                    Sin calificar
                                                </span>
                                            ) : (
                                                <div className="flex flex-col gap-0.5 text-xs">
                                                    <span>
                                                        Entregado:{' '}
                                                        {deal.delivered}
                                                    </span>
                                                    {deal.rejectedRange !==
                                                        null && (
                                                        <span className="text-muted-foreground">
                                                            Rechazo:{' '}
                                                            {deal.rejectedRange}
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </td>
                                        <td className="max-w-sm p-3">
                                            <Comments ratings={deal.ratings} />
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

/**
 * Los comentarios de las calificaciones. Solo salen aquí: en la app, las
 * empresas ven únicamente los números de reputación.
 */
function Comments({ ratings }: { ratings: Row['ratings'] }) {
    if (ratings.length === 0) {
        return (
            <span className="text-xs text-muted-foreground">
                Nadie ha calificado
            </span>
        );
    }

    return (
        <ul className="flex flex-col gap-2">
            {ratings.map((rating) => (
                <li key={rating.id} className="text-xs">
                    <span className="flex items-center gap-1 font-medium">
                        <Star className="size-3 fill-amber-400 text-amber-400" />
                        {rating.score} · {rating.from}
                        {rating.onTime === false && (
                            <span className="text-red-600">· tarde</span>
                        )}
                    </span>
                    {rating.comment !== null && (
                        <span className="text-muted-foreground italic">
                            “{rating.comment}”
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}

AdminDeals.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Tratos', href: admin.deals.index() },
    ],
};
