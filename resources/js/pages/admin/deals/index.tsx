import { Head } from '@inertiajs/react';
import { ArrowRight, Handshake } from 'lucide-react';
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

AdminDeals.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Tratos', href: admin.deals.index() },
    ],
};
