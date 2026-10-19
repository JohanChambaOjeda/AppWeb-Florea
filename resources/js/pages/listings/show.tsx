import { Head, Link } from '@inertiajs/react';
import {
    BadgeCheck,
    CalendarDays,
    Clock,
    Copy,
    HandCoins,
    MapPin,
    Pencil,
    Plane,
    Scissors,
    StickyNote,
} from 'lucide-react';
import { useNow } from '@/hooks/use-now';
import { cn } from '@/lib/utils';
import { timeLeft } from '@/lib/time-left';
import { dashboard } from '@/routes';
import listings from '@/routes/listings';
import offers from '@/routes/offers';
import type { ListingDetail, ListingDetailItem, MyOffer } from '@/types';

type Props = {
    listing: ListingDetail;
    myOffer: MyOffer | null;
    canOffer: boolean;
    offerDenial: string | null;
};

export default function ShowListing({
    listing,
    myOffer,
    canOffer,
    offerDenial,
}: Props) {
    const now = useNow();
    const left = timeLeft(listing.expiresAt, now);
    const isWant = listing.kind === 'want';

    return (
        <>
            <Head title={`${listing.kindLabel} · ${listing.company.name}`} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                {/* Cabecera */}
                <div className="flex flex-col gap-3 rounded-xl border bg-card p-4">
                    <div className="flex flex-wrap items-center gap-2">
                        <span
                            className={cn(
                                'rounded-md px-2 py-0.5 text-xs font-semibold text-white',
                                isWant ? 'bg-violet-600' : 'bg-sky-500',
                            )}
                        >
                            {listing.kindLabel}
                        </span>
                        {!listing.isOpen && (
                            <span className="rounded-md border px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                {listing.statusLabel}
                            </span>
                        )}
                        <span
                            className={cn(
                                'ml-auto flex items-center gap-1 text-sm',
                                left.urgent
                                    ? 'font-semibold text-red-600'
                                    : 'text-muted-foreground',
                            )}
                        >
                            <Clock className="size-4" />
                            {left.label}
                        </span>
                    </div>

                    <div className="flex flex-col gap-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-lg font-semibold">
                                {listing.isMine
                                    ? 'Mi publicación'
                                    : listing.company.name}
                            </h1>
                            {listing.company.verified && (
                                <BadgeCheck
                                    className="size-5 text-sky-600"
                                    aria-label="Empresa verificada"
                                />
                            )}
                        </div>
                        <p className="flex flex-wrap items-center gap-x-3 text-sm text-muted-foreground">
                            <span>{listing.company.typeLabel}</span>
                            <span className="flex items-center gap-1">
                                <MapPin className="size-3.5" />
                                {listing.company.zone}
                            </span>
                        </p>
                    </div>
                </div>

                {/* Líneas */}
                <section className="flex flex-col gap-2">
                    <h2 className="text-base font-semibold">
                        {isWant ? 'Lo que necesita' : 'Lo que tiene'}
                    </h2>
                    <ul className="divide-y rounded-xl border bg-card">
                        {listing.items.map((item) => (
                            <ItemRow key={item.id} item={item} />
                        ))}
                    </ul>
                </section>

                {/* Detalles del pedido */}
                <section className="flex flex-col gap-2 rounded-xl border bg-card p-4 text-sm">
                    {listing.neededBy.label && (
                        <Detail icon={CalendarDays} label="Para cuándo">
                            {listing.neededBy.label}
                        </Detail>
                    )}
                    {listing.destinationLabel && (
                        <Detail icon={Plane} label="Destino">
                            {listing.destinationLabel}
                        </Detail>
                    )}
                    {listing.cutPointLabel && (
                        <Detail icon={Scissors} label="Punto de corte">
                            {listing.cutPointLabel}
                        </Detail>
                    )}
                    {listing.notes && (
                        <Detail icon={StickyNote} label="Nota">
                            {listing.notes}
                        </Detail>
                    )}
                </section>

                {/* Mi oferta */}
                {myOffer !== null && myOffer.status !== 'withdrawn' && (
                    <MyOfferCard offer={myOffer} listing={listing} />
                )}

                {/* Acciones */}
                <div className="flex flex-col gap-2">
                    {listing.isMine ? (
                        <>
                            <Link
                                href={listings.create({
                                    query: { from: listing.id },
                                })}
                                className="flex h-13 items-center justify-center gap-2 rounded-xl border-2 border-input text-base font-medium"
                            >
                                <Copy className="size-5" />
                                Duplicar
                            </Link>
                            <p className="text-center text-sm text-muted-foreground">
                                {listing.offersCount === 0
                                    ? 'Todavía no has recibido ofertas.'
                                    : `Has recibido ${listing.offersCount} ${listing.offersCount === 1 ? 'oferta' : 'ofertas'}. Compararlas llega en el paso 4.`}
                            </p>
                        </>
                    ) : canOffer ? (
                        <Link
                            href={offers.edit(listing.id)}
                            className="flex h-14 items-center justify-center gap-2 rounded-xl bg-primary text-lg font-semibold text-primary-foreground"
                        >
                            {myOffer?.canEdit ? (
                                <>
                                    <Pencil className="size-5" />
                                    Editar mi oferta
                                </>
                            ) : (
                                <>
                                    <HandCoins className="size-5" />
                                    {isWant ? 'Ofertar' : 'Quiero comprar'}
                                </>
                            )}
                        </Link>
                    ) : (
                        offerDenial !== null && (
                            <p className="rounded-xl border border-dashed p-4 text-center text-sm text-muted-foreground">
                                {offerDenial}
                            </p>
                        )
                    )}
                </div>
            </div>
        </>
    );
}

/**
 * Lo que oferté en esta publicación. El celular de la otra empresa no sale
 * aquí: eso llega en el paso 4, y solo si la oferta fue aceptada.
 */
function MyOfferCard({
    offer,
    listing,
}: {
    offer: MyOffer;
    listing: ListingDetail;
}) {
    const tone = {
        sent: 'border-sky-300 bg-sky-50 dark:border-sky-900 dark:bg-sky-950/40',
        accepted:
            'border-green-300 bg-green-50 dark:border-green-900 dark:bg-green-950/40',
        rejected: 'border-input bg-muted/40',
        withdrawn: 'border-input bg-muted/40',
    }[offer.status];

    const byItem = new Map(listing.items.map((item) => [item.id, item]));

    return (
        <section
            className={cn('flex flex-col gap-3 rounded-xl border p-4', tone)}
        >
            <div className="flex flex-wrap items-center gap-2">
                <h2 className="text-base font-semibold">Mi oferta</h2>
                <span className="rounded-md border bg-background px-2 py-0.5 text-xs font-medium">
                    {offer.statusLabel}
                </span>
            </div>

            <ul className="flex flex-col gap-1 text-sm">
                {offer.items.map((item) => (
                    <li key={item.id} className="flex flex-wrap gap-x-2">
                        <span className="font-medium">
                            {byItem.get(item.listingItemId)?.variety ??
                                'Línea retirada'}
                        </span>
                        <span className="text-muted-foreground">
                            {item.quantity} {item.unitLabel} · {item.length} cm
                        </span>
                    </li>
                ))}
            </ul>

            <div className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                {offer.pricePerStem !== null && (
                    <span>
                        <span className="text-muted-foreground">Precio: </span>
                        <span className="font-semibold">
                            ${offer.pricePerStem} el tallo
                        </span>
                    </span>
                )}
                <span>
                    <span className="text-muted-foreground">Entrega: </span>
                    <span className="font-medium">{offer.deliveryLabel}</span>
                </span>
            </div>

            {offer.photos.length > 0 && (
                <div className="flex flex-wrap gap-2">
                    {offer.photos.map((photo) => (
                        <img
                            key={photo.id}
                            src={photo.url}
                            alt="Foto de la flor que oferté"
                            className="size-20 rounded-lg border object-cover"
                        />
                    ))}
                </div>
            )}

            {offer.notes && (
                <p className="text-sm text-muted-foreground">{offer.notes}</p>
            )}
        </section>
    );
}

function ItemRow({ item }: { item: ListingDetailItem }) {
    return (
        <li className="flex flex-wrap items-center gap-x-3 gap-y-1 p-3">
            <span className="text-base font-medium">{item.variety}</span>
            {item.color && (
                <span className="text-sm text-muted-foreground">
                    {item.color}
                </span>
            )}
            <span className="ml-auto flex items-center gap-3">
                <span className="text-sm font-semibold text-sky-700 tabular-nums dark:text-sky-400">
                    {item.lengths.join(' / ')} cm
                </span>
                {item.quantity !== null && (
                    <span className="rounded-md bg-muted px-2 py-0.5 text-sm font-medium">
                        {item.quantity} {item.unitLabel}
                    </span>
                )}
            </span>
        </li>
    );
}

function Detail({
    icon: Icon,
    label,
    children,
}: {
    icon: typeof CalendarDays;
    label: string;
    children: React.ReactNode;
}) {
    return (
        <p className="flex items-start gap-2">
            <Icon className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
            <span className="text-muted-foreground">{label}:</span>
            <span className="font-medium">{children}</span>
        </p>
    );
}

ShowListing.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: dashboard() },
        { title: 'Publicación', href: dashboard() },
    ],
};
