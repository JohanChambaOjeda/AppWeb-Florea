import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { HandCoins, MapPin } from 'lucide-react';
import { ContactCard } from '@/components/contact-card';
import { PhotoGallery } from '@/components/photo-gallery';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useNow } from '@/hooks/use-now';
import { money } from '@/lib/format';
import { cn } from '@/lib/utils';
import { sinceLabel } from '@/lib/time-left';
import { dashboard } from '@/routes';
import { show } from '@/routes/listings';
import myOffers from '@/routes/my-offers';
import type { MyOfferWithListing, Paginated } from '@/types';

type Props = {
    offers: Paginated<MyOfferWithListing>;
};

export default function MyOffersIndex({ offers: page }: Props) {
    return (
        <>
            <Head title="Mis ofertas" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Mis ofertas</h1>

                {page.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center">
                        <HandCoins className="size-8 text-muted-foreground" />
                        <p className="text-base text-muted-foreground">
                            Todavía no has ofertado. Mira los pedidos del inicio
                            y manda tu primera oferta.
                        </p>
                        <Link
                            href={dashboard()}
                            className="font-medium text-primary underline"
                        >
                            Ver pedidos
                        </Link>
                    </div>
                ) : (
                    <InfiniteScroll
                        data="offers"
                        manual
                        className="flex flex-col gap-3"
                        next={({ loading, fetch, hasMore }) =>
                            hasMore ? (
                                <Button
                                    variant="outline"
                                    className="mt-3 h-12 w-full text-base"
                                    disabled={loading}
                                    onClick={fetch}
                                >
                                    {loading ? <Spinner /> : null}
                                    Cargar más
                                </Button>
                            ) : null
                        }
                    >
                        {page.data.map((offer) => (
                            <OfferRow key={offer.id} offer={offer} />
                        ))}
                    </InfiniteScroll>
                )}
            </div>
        </>
    );
}

function OfferRow({ offer }: { offer: MyOfferWithListing }) {
    const now = useNow();

    const tone = {
        accepted:
            'border-green-300 bg-green-50/60 dark:border-green-900 dark:bg-green-950/30',
        sent: 'bg-card',
        rejected: 'bg-muted/40',
        withdrawn: 'bg-muted/40',
    }[offer.status];

    return (
        <article
            className={cn('flex flex-col gap-3 rounded-xl border p-4', tone)}
        >
            <header className="flex flex-wrap items-center gap-2">
                <span
                    className={cn(
                        'rounded-md px-2 py-0.5 text-xs font-semibold text-white',
                        offer.listing.kind === 'want'
                            ? 'bg-violet-600'
                            : 'bg-sky-500',
                    )}
                >
                    {offer.listing.kindLabel}
                </span>
                <span className="rounded-md border bg-background px-2 py-0.5 text-xs font-medium">
                    {offer.statusLabel}
                </span>
                <span className="ml-auto text-xs text-muted-foreground">
                    {sinceLabel(offer.createdAt, now)}
                </span>
            </header>

            <div className="flex flex-col gap-0.5">
                <Link
                    href={show(offer.listing.id)}
                    className="font-semibold underline-offset-2 hover:underline"
                >
                    {offer.listing.companyName}
                </Link>
                <p className="flex flex-wrap items-center gap-x-3 text-sm text-muted-foreground">
                    <span className="flex items-center gap-1">
                        <MapPin className="size-3.5" />
                        {offer.listing.zone}
                    </span>
                    {offer.listing.neededByLabel && (
                        <span>Para {offer.listing.neededByLabel}</span>
                    )}
                </p>
            </div>

            <ul className="flex flex-col gap-1 text-sm">
                {offer.items.map((item) => (
                    <li key={item.id} className="text-muted-foreground">
                        {item.quantity} {item.unitLabel} · {item.length} cm
                    </li>
                ))}
            </ul>

            <div className="flex flex-wrap items-baseline gap-x-4 gap-y-1 text-sm">
                {offer.pricePerStem !== null && (
                    <span className="text-base font-bold">
                        {money(offer.pricePerStem)}
                        <span className="text-sm font-normal text-muted-foreground">
                            {' '}
                            el tallo
                        </span>
                    </span>
                )}
                <span>
                    <span className="text-muted-foreground">Entrega: </span>
                    {offer.deliveryLabel}
                </span>
            </div>

            <PhotoGallery photos={offer.photos} size="sm" />

            {offer.contact !== null && <ContactCard contact={offer.contact} />}

            {offer.canEdit && (
                <Link
                    href={show(offer.listing.id)}
                    className="text-sm font-medium text-primary underline"
                >
                    Ver la publicación y editar mi oferta
                </Link>
            )}
        </article>
    );
}

MyOffersIndex.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: dashboard() },
        { title: 'Mis ofertas', href: myOffers.index() },
    ],
};
