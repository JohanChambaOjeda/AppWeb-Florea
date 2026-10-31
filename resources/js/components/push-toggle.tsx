import { BellOff, BellRing } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useInstall } from '@/hooks/use-install';
import { usePush } from '@/hooks/use-push';

/**
 * Interruptor de avisos para ESTE dispositivo.
 *
 * Cada navegador tiene su propia suscripción, así que una vendedora puede
 * tenerlos activos en el celular y apagados en la computadora.
 */
export function PushToggle() {
    const {
        supported,
        permission,
        subscribed,
        busy,
        error,
        subscribe,
        unsubscribe,
    } = usePush();
    const { platform, installed } = useInstall();

    return (
        <div className="flex flex-col gap-3 rounded-xl border bg-card p-4">
            <div className="flex flex-col gap-1">
                <h3 className="text-base font-semibold">
                    Avisos en este dispositivo
                </h3>
                <p className="text-sm text-muted-foreground">
                    Te avisamos de pedidos nuevos con tus variedades, ofertas y
                    respuestas. Puedes tenerlos activos en varios dispositivos.
                </p>
            </div>

            {!supported ? (
                <p className="rounded-lg bg-muted/60 p-3 text-sm">
                    {platform === 'ios' && !installed
                        ? 'En iPhone los avisos solo funcionan con Florea instalada en la pantalla de inicio.'
                        : 'Este navegador no soporta avisos. Prueba con Chrome en Android o Safari en iPhone.'}
                </p>
            ) : permission === 'denied' ? (
                <p className="rounded-lg bg-muted/60 p-3 text-sm">
                    Los avisos están bloqueados para Florea. Para activarlos,
                    entra a los ajustes del navegador, busca Florea y permite
                    las notificaciones.
                </p>
            ) : subscribed ? (
                <div className="flex flex-col gap-2">
                    <p className="flex items-center gap-2 text-sm font-medium text-green-700 dark:text-green-400">
                        <BellRing className="size-4" />
                        Avisos activos en este dispositivo
                    </p>
                    <Button
                        variant="outline"
                        className="h-12 w-full text-base"
                        disabled={busy}
                        onClick={() => void unsubscribe()}
                    >
                        {busy ? <Spinner /> : <BellOff className="size-5" />}
                        Desactivar avisos aquí
                    </Button>
                </div>
            ) : (
                <Button
                    className="h-12 w-full text-base"
                    disabled={busy}
                    onClick={() => void subscribe()}
                >
                    {busy ? <Spinner /> : <BellRing className="size-5" />}
                    Activar avisos
                </Button>
            )}

            {error !== null && (
                <p className="text-sm text-destructive">{error}</p>
            )}
        </div>
    );
}
