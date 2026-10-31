import { Clock3, Handshake, Sparkles, Star } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { Reputation } from '@/types';

/**
 * Los números de una empresa: estrellas, tratos y, si vende, puntualidad.
 *
 * Con menos de 3 calificaciones no se muestra promedio sino "Nuevo en
 * Florea": una sola opinión no es reputación, ni buena ni mala.
 */
export function ReputationBadge({
    reputation,
    className,
}: {
    reputation: Reputation;
    className?: string;
}) {
    if (reputation.isNew) {
        return (
            <span
                className={cn(
                    'flex items-center gap-1 text-xs font-medium text-sky-700 dark:text-sky-400',
                    className,
                )}
            >
                <Sparkles className="size-3.5" />
                Nuevo en Florea
                {reputation.deals > 0 && (
                    <span className="text-muted-foreground">
                        · {reputation.deals}{' '}
                        {reputation.deals === 1 ? 'trato' : 'tratos'}
                    </span>
                )}
            </span>
        );
    }

    return (
        <span
            className={cn(
                'flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-muted-foreground',
                className,
            )}
        >
            <span className="flex items-center gap-1 font-semibold text-foreground">
                <Star className="size-3.5 fill-amber-400 text-amber-400" />
                {reputation.stars?.toFixed(1)}
                <span className="font-normal text-muted-foreground">
                    ({reputation.ratings})
                </span>
            </span>

            <span className="flex items-center gap-1">
                <Handshake className="size-3.5" />
                {reputation.deals} {reputation.deals === 1 ? 'trato' : 'tratos'}
            </span>

            {reputation.onTimePct !== null && (
                <span className="flex items-center gap-1">
                    <Clock3 className="size-3.5" />
                    {reputation.onTimePct}% a tiempo
                </span>
            )}
        </span>
    );
}
