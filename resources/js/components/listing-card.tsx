import { Link } from '@inertiajs/react';
import { BadgeCheck, CalendarDays, Clock, MapPin, Tag } from 'lucide-react';
import { ReputationBadge } from '@/components/reputation-badge';
import { useNow } from '@/hooks/use-now';
import { cn } from '@/lib/utils';
import { timeLeft } from '@/lib/time-left';
import { show } from '@/routes/listings';
import type { ListingCard as Card } from '@/types';

/**
 * Tarjeta de publicación. Nunca muestra celulares: el contacto solo aparece
 * en la oferta aceptada (regla 6).
 */
export function ListingCard({ listing }: { listing: Card }) {
    const now = useNow();
    const left = timeLeft(listing.expiresAt, now);
    const isWant = listing.kind === 'want';

    return (
        <Link
            href={show(listing.id)}
            className="flex flex-col gap-2 rounded-xl border bg-card p-4 transition-colors hover:border-primary/50"
        >
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

                {listing.newOffersCount > 0 && (
                    <span className="ml-auto rounded-md bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-800 dark:bg-violet-950 dark:text-violet-200">
                        {listing.newOffersCount}{' '}
                        {listing.newOffersCount === 1
                            ? 'oferta nueva'
                            : 'ofertas nuevas'}
                    </span>
                )}
            </div>

            {/* Empresa */}
            <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                <span className="font-semibold text-foreground">
                    {listing.isMine ? 'Mi publicación' : listing.company.name}
                </span>
                {listing.company.verified && (
                    <BadgeCheck
                        className="size-4 text-sky-600"
                        aria-label="Empresa verificada"
                    />
                )}
                <span className="flex items-center gap-1 text-muted-foreground">
                    <MapPin className="size-3.5" />
                    {listing.company.zone}
                </span>
            </div>

            {!listing.isMine && (
                <ReputationBadge reputation={listing.company.reputation} />
            )}

            {/* Líneas resumidas */}
            <p className="text-base leading-snug font-medium">
                {listing.summary}
            </p>

            {/* Pie */}
            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                {listing.neededBy.label && (
                    <span
                        className={cn(
                            'flex items-center gap-1',
                            listing.neededBy.isToday &&
                                'font-semibold text-foreground',
                        )}
                    >
                        <CalendarDays className="size-3.5" />
                        {listing.neededBy.label}
                    </span>
                )}

                <span
                    className={cn(
                        'flex items-center gap-1',
                        left.urgent && 'font-semibold text-red-600',
                    )}
                >
                    <Clock className="size-3.5" />
                    {left.label}
                </span>

                <span className="flex items-center gap-1">
                    <Tag className="size-3.5" />
                    {listing.offersCount === 0
                        ? 'Sin ofertas'
                        : `${listing.offersCount} ${listing.offersCount === 1 ? 'oferta' : 'ofertas'}`}
                </span>
            </div>
        </Link>
    );
}
