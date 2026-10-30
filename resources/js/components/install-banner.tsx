import { Link } from '@inertiajs/react';
import { Download, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { useInstall } from '@/hooks/use-install';
import { install } from '@/routes';

const DISMISSED = 'florea:install-dismissed';

/**
 * Aviso discreto en el inicio, la primera vez. Se cierra y no vuelve.
 *
 * No pide permiso de nada ni abre diálogos: solo lleva a la pantalla que
 * explica cómo instalar, que es donde el usuario decide.
 */
export function InstallBanner() {
    const { installed } = useInstall();
    const [dismissed, setDismissed] = useState(true);

    // Se lee en el efecto y no al montar: en el primer render del servidor
    // no existe localStorage.
    useEffect(() => {
        setDismissed(localStorage.getItem(DISMISSED) === '1');
    }, []);

    if (installed || dismissed) {
        return null;
    }

    return (
        <div className="flex items-center gap-3 rounded-xl border border-violet-300 bg-violet-50 p-3 dark:border-violet-900 dark:bg-violet-950/40">
            <Download className="size-5 shrink-0 text-violet-600" />

            <Link
                href={install()}
                className="min-w-0 flex-1 text-sm font-medium underline-offset-2 hover:underline"
            >
                Instala Florea en tu celular y ábrela de un toque
            </Link>

            <button
                type="button"
                aria-label="Cerrar aviso"
                onClick={() => {
                    localStorage.setItem(DISMISSED, '1');
                    setDismissed(true);
                }}
                className="flex size-11 shrink-0 items-center justify-center rounded-lg text-muted-foreground hover:bg-violet-100 dark:hover:bg-violet-900"
            >
                <X className="size-5" />
            </button>
        </div>
    );
}
