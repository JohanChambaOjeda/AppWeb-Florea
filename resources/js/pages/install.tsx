import { Head } from '@inertiajs/react';
import {
    BellRing,
    Check,
    Download,
    Share,
    SquarePlus,
    Wifi,
    Zap,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useInstall } from '@/hooks/use-install';
import { dashboard } from '@/routes';
import { install as installRoute } from '@/routes';

export default function Install() {
    const { platform, installed, canPrompt, install } = useInstall();
    const [rejected, setRejected] = useState(false);

    return (
        <>
            <Head title="Instalar Florea" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
                <header className="flex flex-col items-center gap-3 text-center">
                    <img
                        src="/icons/icon-192.png"
                        alt="Florea"
                        className="size-20 rounded-2xl shadow-sm"
                    />
                    <h1 className="text-xl font-semibold">Instalar Florea</h1>
                    <p className="text-base text-muted-foreground">
                        Instálala y queda como una app más en tu celular: se
                        abre de un toque, sin buscar el link.
                    </p>
                </header>

                {installed ? (
                    <div className="flex items-center gap-3 rounded-xl border-2 border-green-300 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/40">
                        <Check className="size-6 shrink-0 text-green-700 dark:text-green-400" />
                        <p className="text-base font-medium">
                            Ya tienes Florea instalada. Ábrela desde el ícono de
                            tu pantalla de inicio.
                        </p>
                    </div>
                ) : (
                    <>
                        <ul className="flex flex-col gap-3">
                            <Benefit icon={Zap}>
                                Se abre de un toque, como WhatsApp.
                            </Benefit>
                            <Benefit icon={BellRing}>
                                Te llegan los avisos de pedidos y ofertas.
                            </Benefit>
                            <Benefit icon={Wifi}>
                                Avisa cuando te quedas sin señal en vez de
                                quedarse colgada.
                            </Benefit>
                        </ul>

                        {platform === 'ios' ? (
                            <IphoneSteps />
                        ) : (
                            <AndroidSteps
                                canPrompt={canPrompt}
                                rejected={rejected}
                                onInstall={async () => {
                                    const ok = await install();

                                    setRejected(!ok);
                                }}
                            />
                        )}
                    </>
                )}

                <a
                    href={dashboard().url}
                    className="text-center text-base font-medium text-primary underline underline-offset-4"
                >
                    Volver al inicio
                </a>
            </div>
        </>
    );
}

function Benefit({
    icon: Icon,
    children,
}: {
    icon: typeof Zap;
    children: ReactNode;
}) {
    return (
        <li className="flex items-start gap-3 rounded-xl border bg-card p-4">
            <Icon className="mt-0.5 size-5 shrink-0 text-violet-600" />
            <span className="text-base">{children}</span>
        </li>
    );
}

function AndroidSteps({
    canPrompt,
    rejected,
    onInstall,
}: {
    canPrompt: boolean;
    rejected: boolean;
    onInstall: () => void;
}) {
    return (
        <section className="flex flex-col gap-4 rounded-xl border bg-card p-4">
            <h2 className="text-base font-semibold">En Android</h2>

            {canPrompt ? (
                <>
                    <Button
                        size="lg"
                        className="h-14 w-full text-lg"
                        onClick={onInstall}
                    >
                        <Download className="size-5" />
                        Instalar Florea
                    </Button>
                    {rejected && (
                        <p className="text-sm text-muted-foreground">
                            No pasa nada: puedes instalarla más tarde desde aquí
                            mismo.
                        </p>
                    )}
                </>
            ) : (
                <>
                    {/* Chrome solo da el botón cuando le da la gana; si no
                        está, se explica el camino del menú. */}
                    <Steps
                        steps={[
                            'Toca los tres puntos (⋮) arriba a la derecha del navegador.',
                            'Elige “Instalar aplicación” o “Agregar a pantalla principal”.',
                            'Confirma. El ícono de Florea queda en tu pantalla de inicio.',
                        ]}
                    />
                    <p className="text-sm text-muted-foreground">
                        Si no te aparece la opción, abre esta página en Chrome.
                    </p>
                </>
            )}
        </section>
    );
}

function IphoneSteps() {
    return (
        <section className="flex flex-col gap-4 rounded-xl border bg-card p-4">
            <h2 className="text-base font-semibold">En iPhone</h2>

            <p className="flex items-start gap-2 rounded-lg bg-muted/60 p-3 text-sm">
                <Share className="mt-0.5 size-4 shrink-0" />
                En iPhone hay que hacerlo desde Safari. Si estás en Chrome, abre
                esta página en Safari primero.
            </p>

            <Steps
                steps={[
                    'Toca el botón Compartir (el cuadrado con la flecha hacia arriba), abajo en la pantalla.',
                    'Baja en la lista y elige “Agregar a pantalla de inicio”.',
                    'Toca “Agregar” arriba a la derecha.',
                ]}
            />

            <p className="flex items-start gap-2 text-sm text-muted-foreground">
                <SquarePlus className="mt-0.5 size-4 shrink-0" />
                En iPhone los avisos solo funcionan con la app instalada, así
                que este paso es necesario para enterarte de los pedidos.
            </p>
        </section>
    );
}

function Steps({ steps }: { steps: string[] }) {
    return (
        <ol className="flex flex-col gap-3">
            {steps.map((step, index) => (
                <li key={index} className="flex items-start gap-3">
                    <span className="flex size-7 shrink-0 items-center justify-center rounded-full bg-violet-600 text-sm font-bold text-white">
                        {index + 1}
                    </span>
                    <span className="text-base">{step}</span>
                </li>
            ))}
        </ol>
    );
}

Install.layout = {
    breadcrumbs: [
        { title: 'Inicio', href: dashboard() },
        { title: 'Instalar Florea', href: installRoute() },
    ],
};
