import { router, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import push from '@/routes/push';

/**
 * La llave VAPID viaja en base64url y pushManager.subscribe la quiere en
 * bytes. Se devuelve un ArrayBuffer y no un Uint8Array porque TypeScript
 * no acepta el Uint8Array genérico donde se espera un BufferSource.
 */
function urlBase64ToBytes(base64: string): ArrayBuffer {
    const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4))
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const raw = atob(padded);
    const buffer = new ArrayBuffer(raw.length);
    const bytes = new Uint8Array(buffer);

    for (let i = 0; i < raw.length; i++) {
        bytes[i] = raw.charCodeAt(i);
    }

    return buffer;
}

export type PushState = {
    /** El navegador sabe hacer push. Safari de iPhone, solo si está instalada. */
    supported: boolean;
    /** 'default' = todavía no se le preguntó. */
    permission: NotificationPermission | 'unsupported';
    /** Este dispositivo ya está suscrito. */
    subscribed: boolean;
    busy: boolean;
    error: string | null;
};

export function usePush() {
    const page = usePage<{
        push: { vapidPublicKey: string | null; subscribed: boolean };
    }>();
    const { vapidPublicKey } = page.props.push;

    const supported =
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window;

    const [state, setState] = useState<PushState>({
        supported,
        permission: supported ? Notification.permission : 'unsupported',
        subscribed: false,
        busy: false,
        error: null,
    });

    // Lo que manda es lo que diga el navegador de ESTE dispositivo, no lo
    // que haya en la base: el usuario pudo revocar el permiso a mano.
    useEffect(() => {
        if (!supported) {
            return;
        }

        navigator.serviceWorker.ready
            .then((registration) => registration.pushManager.getSubscription())
            .then((subscription) =>
                setState((s) => ({ ...s, subscribed: subscription !== null })),
            )
            .catch(() => undefined);
    }, [supported]);

    /** Solo se llama al tocar el botón: nunca se pide permiso solo. */
    const subscribe = useCallback(async (): Promise<boolean> => {
        if (!supported || vapidPublicKey === null) {
            return false;
        }

        setState((s) => ({ ...s, busy: true, error: null }));

        try {
            const permission = await Notification.requestPermission();

            setState((s) => ({ ...s, permission }));

            if (permission !== 'granted') {
                setState((s) => ({
                    ...s,
                    busy: false,
                    error:
                        permission === 'denied'
                            ? 'Bloqueaste los avisos. Actívalos en los ajustes del navegador.'
                            : null,
                }));

                return false;
            }

            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToBytes(vapidPublicKey),
            });

            const json = subscription.toJSON();

            router.post(
                push.subscribe().url,
                {
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: json.keys?.p256dh ?? '',
                        auth: json.keys?.auth ?? '',
                    },
                },
                { preserveScroll: true, preserveState: true },
            );

            setState((s) => ({ ...s, subscribed: true, busy: false }));

            return true;
        } catch {
            setState((s) => ({
                ...s,
                busy: false,
                error: 'No se pudieron activar los avisos en este dispositivo.',
            }));

            return false;
        }
    }, [supported, vapidPublicKey]);

    const unsubscribe = useCallback(async (): Promise<void> => {
        if (!supported) {
            return;
        }

        setState((s) => ({ ...s, busy: true }));

        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();

        if (subscription !== null) {
            const endpoint = subscription.endpoint;

            await subscription.unsubscribe();

            router.delete(push.unsubscribe().url, {
                data: { endpoint },
                preserveScroll: true,
                preserveState: true,
            });
        }

        setState((s) => ({ ...s, subscribed: false, busy: false }));
    }, [supported]);

    return { ...state, subscribe, unsubscribe };
}
