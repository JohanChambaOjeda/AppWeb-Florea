import { useEffect, useState } from 'react';

/** El evento de Chrome para instalar; no está en los tipos estándar. */
type InstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

export type Platform = 'android' | 'ios' | 'desktop';

/** Chrome guarda el evento para poder mostrar el botón cuando queramos. */
let saved: InstallPromptEvent | null = null;

if (typeof window !== 'undefined') {
    window.addEventListener('beforeinstallprompt', (event) => {
        // Sin esto, Chrome muestra su propio aviso cuando le parece.
        event.preventDefault();
        saved = event as InstallPromptEvent;
    });
}

const detectPlatform = (): Platform => {
    if (typeof navigator === 'undefined') {
        return 'desktop';
    }

    const ua = navigator.userAgent;

    // El iPad moderno se declara Mac; se distingue por el táctil.
    if (
        /iphone|ipad|ipod/i.test(ua) ||
        (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1)
    ) {
        return 'ios';
    }

    return /android/i.test(ua) ? 'android' : 'desktop';
};

const detectStandalone = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        // Safari de iPhone no soporta display-mode y usa esto.
        (navigator as Navigator & { standalone?: boolean }).standalone === true
    );
};

export function useInstall() {
    const [platform] = useState<Platform>(detectPlatform);
    const [installed, setInstalled] = useState(detectStandalone);
    const [canPrompt, setCanPrompt] = useState(saved !== null);

    useEffect(() => {
        const onPrompt = (event: Event) => {
            saved = event as InstallPromptEvent;
            setCanPrompt(true);
        };

        const onInstalled = () => {
            saved = null;
            setCanPrompt(false);
            setInstalled(true);
        };

        window.addEventListener('beforeinstallprompt', onPrompt);
        window.addEventListener('appinstalled', onInstalled);

        return () => {
            window.removeEventListener('beforeinstallprompt', onPrompt);
            window.removeEventListener('appinstalled', onInstalled);
        };
    }, []);

    /** Abre el diálogo nativo de instalar. Solo existe en Chrome/Android. */
    const install = async (): Promise<boolean> => {
        if (saved === null) {
            return false;
        }

        await saved.prompt();

        const { outcome } = await saved.userChoice;

        if (outcome === 'accepted') {
            saved = null;
            setCanPrompt(false);
        }

        return outcome === 'accepted';
    };

    return { platform, installed, canPrompt, install };
}
