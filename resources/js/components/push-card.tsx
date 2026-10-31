import { Link } from '@inertiajs/react';
import { BellRing, Smartphone, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useInstall } from '@/hooks/use-install';
import { usePush } from '@/hooks/use-push';
import { install } from '@/routes';

const DISMISSED = 'florea:push-dismissed';

/**
 * Tarjeta para activar los avisos. Sale después de entrar, una vez.
 *
 * El permiso se pide SOLO al tocar el botón: si se pide solo al cargar, la
 * mitad de la gente lo bloquea por reflejo y después no hay vuelta atrás.
 */
export function PushCard() {
    const { supported, permission, subscribed, busy, error, subscribe } =
        usePush();
    const { platform, installed } = useInstall();
    const [dismissed, setDismissed] = useState(true);

    useEffect(() => {
        setDismissed(localStorage.getItem(DISMISSED) === '1');
    }, []);

    // En iPhone los avisos solo existen con la app instalada.
    const iphoneSinInstalar = platform === 'ios' && !installed;

    if (subscribed || dismissed || permission === 'denied') {
        return null;
    }

    if (!supported && !iphoneSinInstalar) {
        return null;
    }

    const close = () => {
        localStorage.setItem(DISMISSED, '1');
        setDismissed(true);
    };

    return (
        <section className="flex flex-col gap-3 rounded-xl border-2 border-violet-300 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/40">
            <div className="flex items-start gap-3">
                <BellRing className="mt-0.5 size-5 shrink-0 text-violet-600" />
                <div className="flex min-w-0 flex-1 flex-col gap-1">
                    <p className="text-base font-semibold">
                        Activa los avisos para enterarte al instante de pedidos
                        y ofertas
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Un pedido para hoy dura horas. Con los avisos te enteras
                        sin estar mirando la pantalla.
                    </p>
                </div>
                <button
                    type="button"
                    aria-label="Ahora no"
                    onClick={close}
                    className="flex size-11 shrink-0 items-center justify-center rounded-lg text-muted-foreground hover:bg-violet-100 dark:hover:bg-violet-900"
                >
                    <X className="size-5" />
                </button>
            </div>

            {iphoneSinInstalar ? (
                <div className="flex flex-col gap-2">
                    <p className="flex items-start gap-2 rounded-lg bg-background/70 p-3 text-sm">
                        <Smartphone className="mt-0.5 size-4 shrink-0" />
                        En iPhone los avisos solo funcionan con Florea
                        instalada. Instálala primero y vuelve aquí.
                    </p>
                    <Link
                        href={install()}
                        className="flex h-13 items-center justify-center rounded-xl bg-primary text-base font-semibold text-primary-foreground"
                    >
                        Cómo instalar Florea
                    </Link>
                </div>
            ) : (
                <>
                    <Button
                        size="lg"
                        className="h-13 w-full text-base"
                        disabled={busy}
                        onClick={() => void subscribe()}
                    >
                        {busy ? <Spinner /> : <BellRing className="size-5" />}
                        Activar avisos
                    </Button>
                    {error !== null && (
                        <p className="text-sm text-destructive">{error}</p>
                    )}
                </>
            )}
        </section>
    );
}
