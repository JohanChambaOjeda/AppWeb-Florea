import { Link } from '@inertiajs/react';
import { MessageCircle, Phone, Star } from 'lucide-react';
import { useClipboard } from '@/hooks/use-clipboard';
import { create as rateDeal } from '@/routes/ratings';
import type { OfferContact } from '@/types';

/**
 * El contacto de la otra empresa. Solo se pinta cuando el trato está cerrado:
 * el backend solo manda `contact` si la oferta fue aceptada (regla 6).
 */
export function ContactCard({ contact }: { contact: OfferContact }) {
    const [copied, copy] = useClipboard();

    return (
        <div className="flex flex-col gap-3 rounded-xl border-2 border-green-300 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/40">
            <div className="flex flex-col gap-0.5">
                <p className="text-sm text-green-900/70 dark:text-green-100/70">
                    Trato cerrado. Ya pueden coordinar.
                </p>
                <p className="text-base font-semibold">{contact.name}</p>
                <p className="text-sm text-muted-foreground">
                    {contact.companyName}
                </p>
            </div>

            <a
                href={contact.whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="flex h-13 items-center justify-center gap-2 rounded-xl bg-green-600 text-base font-semibold text-white hover:bg-green-700"
            >
                <MessageCircle className="size-5" />
                Escribir por WhatsApp
            </a>

            <button
                type="button"
                onClick={() => void copy(contact.phone)}
                className="flex items-center justify-center gap-2 text-base font-medium tabular-nums"
            >
                <Phone className="size-4" />
                {contact.phone}
                <span className="text-sm text-muted-foreground">
                    {copied === contact.phone ? '· copiado' : '· copiar'}
                </span>
            </button>

            {contact.dealId !== null &&
                (contact.rated ? (
                    <p className="flex items-center justify-center gap-1.5 border-t border-green-200 pt-3 text-sm text-muted-foreground dark:border-green-900">
                        <Star className="size-4" />
                        Ya calificaste este trato
                    </p>
                ) : (
                    <Link
                        href={rateDeal(contact.dealId)}
                        className="flex items-center justify-center gap-1.5 border-t border-green-200 pt-3 text-sm font-medium underline-offset-4 hover:underline dark:border-green-900"
                    >
                        <Star className="size-4" />
                        Calificar este trato
                    </Link>
                ))}
        </div>
    );
}
