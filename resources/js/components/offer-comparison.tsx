import { router } from '@inertiajs/react';
import { BadgeCheck, Check, MapPin, X } from 'lucide-react';
import { useState } from 'react';
import { ContactCard } from '@/components/contact-card';
import { PhotoGallery } from '@/components/photo-gallery';
import { Button } from '@/components/ui/button';
import { useNow } from '@/hooks/use-now';
import { money } from '@/lib/format';
import { cn } from '@/lib/utils';
import { sinceLabel } from '@/lib/time-left';
import offers from '@/routes/offers';
import type { ListingDetail, ReceivedOffer } from '@/types';

/**
 * Las ofertas recibidas, para compararlas: tarjetas en celular y tabla en
 * pantalla grande. Se pueden aceptar varias (regla 5).
 */
export function OfferComparison({
    offers: received,
    listing,
}: {
    offers: ReceivedOffer[];
    listing: ListingDetail;
}) {
    if (received.length === 0) {
        return (
            <p className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                Todavía no has recibido ofertas. Te avisamos en cuanto llegue la
                primera.
            </p>
        );
    }

    const accepted = received.filter((o) => o.status === 'accepted').length;

    return (
        <section className="flex flex-col gap-3">
            <div className="flex flex-wrap items-baseline gap-2">
                <h2 className="text-base font-semibold">
                    Ofertas recibidas ({received.length})
                </h2>
                {accepted > 0 && (
                    <span className="text-sm text-muted-foreground">
                        {accepted} aceptada{accepted === 1 ? '' : 's'}
                    </span>
                )}
            </div>

            <p className="text-sm text-muted-foreground">
                Puedes aceptar varias: completa el pedido con los productores
                que necesites.
            </p>

            {/* Celular: una tarjeta por oferta */}
            <div className="flex flex-col gap-3 lg:hidden">
                {received.map((offer) => (
                    <OfferCard key={offer.id} offer={offer} listing={listing} />
                ))}
            </div>

            {/* Pantalla grande: tabla para comparar de un vistazo */}
            <div className="hidden lg:block">
                <OfferTable offers={received} listing={listing} />
            </div>
        </section>
    );
}

/** Acepta o rechaza, con confirmación. */
function useRespond() {
    const [busy, setBusy] = useState<number | null>(null);

    const respond = (
        offer: ReceivedOffer,
        action: 'accept' | 'reject',
        companyName: string,
    ) => {
        const question =
            action === 'accept'
                ? `¿Aceptar la oferta de ${companyName}? Se le mostrará tu celular para coordinar.`
                : `¿Rechazar la oferta de ${companyName}?`;

        if (!window.confirm(question)) {
            return;
        }

        const url =
            action === 'accept'
                ? offers.accept(offer.id).url
                : offers.reject(offer.id).url;

        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onStart: () => setBusy(offer.id),
                onFinish: () => setBusy(null),
            },
        );
    };

    return { busy, respond };
}

function OfferCard({
    offer,
    listing,
}: {
    offer: ReceivedOffer;
    listing: ListingDetail;
}) {
    const now = useNow();
    const { busy, respond } = useRespond();
    const byItem = new Map(listing.items.map((item) => [item.id, item]));

    return (
        <article
            className={cn(
                'flex flex-col gap-3 rounded-xl border p-4',
                offer.status === 'accepted' &&
                    'border-green-300 bg-green-50/60 dark:border-green-900 dark:bg-green-950/30',
                offer.status === 'sent' && 'bg-card',
                (offer.status === 'rejected' || offer.status === 'withdrawn') &&
                    'bg-muted/40',
            )}
        >
            <header className="flex flex-wrap items-center gap-2">
                <span className="font-semibold">{offer.company.name}</span>
                {offer.company.verified && (
                    <BadgeCheck
                        className="size-4 text-sky-600"
                        aria-label="Empresa verificada"
                    />
                )}
                <span className="rounded-md border bg-background px-2 py-0.5 text-xs font-medium">
                    {offer.statusLabel}
                </span>
                <span className="ml-auto text-xs text-muted-foreground">
                    {sinceLabel(offer.createdAt, now)}
                </span>
            </header>

            <p className="flex flex-wrap items-center gap-x-3 text-sm text-muted-foreground">
                <span>{offer.company.typeLabel}</span>
                <span className="flex items-center gap-1">
                    <MapPin className="size-3.5" />
                    {offer.company.zone}
                </span>
            </p>

            {/* Qué cubre */}
            <ul className="flex flex-col gap-1 text-sm">
                {offer.items.map((item) => (
                    <li key={item.id} className="flex flex-wrap gap-x-2">
                        <span className="font-medium">
                            {byItem.get(item.listingItemId)?.variety ?? '—'}
                        </span>
                        <span className="text-muted-foreground">
                            {item.quantity} {item.unitLabel} · {item.length} cm
                        </span>
                    </li>
                ))}
            </ul>

            <div className="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                {offer.pricePerStem !== null && (
                    <span className="text-lg font-bold">
                        {money(offer.pricePerStem)}
                        <span className="text-sm font-normal text-muted-foreground">
                            {' '}
                            el tallo
                        </span>
                    </span>
                )}
                <span className="text-sm">
                    <span className="text-muted-foreground">Entrega: </span>
                    {offer.deliveryLabel}
                </span>
            </div>

            <PhotoGallery photos={offer.photos} />

            {offer.notes && (
                <p className="text-sm text-muted-foreground italic">
                    “{offer.notes}”
                </p>
            )}

            {offer.canRespond && (
                <div className="flex gap-2">
                    <Button
                        className="h-12 flex-1 text-base"
                        disabled={busy === offer.id}
                        onClick={() =>
                            respond(offer, 'accept', offer.company.name)
                        }
                    >
                        <Check />
                        Aceptar
                    </Button>
                    <Button
                        variant="outline"
                        className="h-12 flex-1 text-base"
                        disabled={busy === offer.id}
                        onClick={() =>
                            respond(offer, 'reject', offer.company.name)
                        }
                    >
                        <X />
                        Rechazar
                    </Button>
                </div>
            )}

            {offer.contact !== null && <ContactCard contact={offer.contact} />}
        </article>
    );
}

function OfferTable({
    offers: received,
    listing,
}: {
    offers: ReceivedOffer[];
    listing: ListingDetail;
}) {
    const now = useNow();
    const { busy, respond } = useRespond();
    const byItem = new Map(listing.items.map((item) => [item.id, item]));

    return (
        <div className="overflow-x-auto rounded-xl border">
            <table className="w-full text-sm">
                <thead className="bg-muted/60 text-left">
                    <tr>
                        <th className="p-3 font-semibold">Empresa</th>
                        <th className="p-3 font-semibold">Cubre</th>
                        <th className="p-3 font-semibold">Precio</th>
                        <th className="p-3 font-semibold">Entrega</th>
                        <th className="p-3 font-semibold">Fotos</th>
                        <th className="p-3 font-semibold">Llegó</th>
                        <th className="p-3 font-semibold"></th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {received.map((offer) => (
                        <tr
                            key={offer.id}
                            className={cn(
                                offer.status === 'accepted' &&
                                    'bg-green-50 dark:bg-green-950/30',
                                (offer.status === 'rejected' ||
                                    offer.status === 'withdrawn') &&
                                    'text-muted-foreground',
                            )}
                        >
                            <td className="p-3 align-top">
                                <div className="flex items-center gap-1 font-medium">
                                    {offer.company.name}
                                    {offer.company.verified && (
                                        <BadgeCheck className="size-4 text-sky-600" />
                                    )}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    {offer.company.typeLabel} ·{' '}
                                    {offer.company.zone}
                                </div>
                                <div className="mt-1 inline-block rounded border px-1.5 text-xs">
                                    {offer.statusLabel}
                                </div>
                            </td>
                            <td className="p-3 align-top">
                                <ul>
                                    {offer.items.map((item) => (
                                        <li key={item.id}>
                                            {byItem.get(item.listingItemId)
                                                ?.variety ?? '—'}{' '}
                                            <span className="text-muted-foreground">
                                                {item.quantity} {item.unitLabel}{' '}
                                                · {item.length} cm
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </td>
                            <td className="p-3 align-top font-semibold whitespace-nowrap">
                                {money(offer.pricePerStem)}
                            </td>
                            <td className="p-3 align-top">
                                {offer.deliveryLabel}
                            </td>
                            <td className="p-3 align-top">
                                <PhotoGallery photos={offer.photos} size="sm" />
                            </td>
                            <td className="p-3 align-top whitespace-nowrap text-muted-foreground">
                                {sinceLabel(offer.createdAt, now)}
                            </td>
                            <td className="p-3 align-top">
                                {offer.canRespond && (
                                    <div className="flex flex-col gap-2">
                                        <Button
                                            size="sm"
                                            disabled={busy === offer.id}
                                            onClick={() =>
                                                respond(
                                                    offer,
                                                    'accept',
                                                    offer.company.name,
                                                )
                                            }
                                        >
                                            <Check />
                                            Aceptar
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            disabled={busy === offer.id}
                                            onClick={() =>
                                                respond(
                                                    offer,
                                                    'reject',
                                                    offer.company.name,
                                                )
                                            }
                                        >
                                            <X />
                                            Rechazar
                                        </Button>
                                    </div>
                                )}
                                {offer.contact !== null && (
                                    <a
                                        href={offer.contact.whatsappUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="block rounded-lg bg-green-600 px-3 py-2 text-center text-xs font-semibold whitespace-nowrap text-white"
                                    >
                                        WhatsApp
                                        <span className="block font-normal tabular-nums">
                                            {offer.contact.phone}
                                        </span>
                                    </a>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
