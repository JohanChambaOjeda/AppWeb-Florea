import { useSyncExternalStore } from 'react';

/**
 * Un solo reloj para toda la pantalla: las tarjetas muestran cuánto le queda
 * a cada publicación y se refrescan solas cada minuto, sin montar un
 * setInterval por tarjeta.
 */
const TICK_MS = 30_000;

let now = Date.now();
let timer: ReturnType<typeof setInterval> | null = null;
const listeners = new Set<() => void>();

const subscribe = (listener: () => void): (() => void) => {
    listeners.add(listener);

    if (timer === null) {
        timer = setInterval(() => {
            now = Date.now();
            listeners.forEach((notify) => notify());
        }, TICK_MS);
    }

    return () => {
        listeners.delete(listener);

        if (listeners.size === 0 && timer !== null) {
            clearInterval(timer);
            timer = null;
        }
    };
};

export function useNow(): number {
    return useSyncExternalStore(
        subscribe,
        () => now,
        () => now,
    );
}
