/**
 * Hace cuánto llegó algo, en palabras cortas: "hace 5 min", "hace 2 h",
 * "hace 3 días". Se usa para saber qué tan fresca es una oferta.
 */
export function sinceLabel(iso: string, now: number): string {
    const ms = now - new Date(iso).getTime();

    if (Number.isNaN(ms)) {
        return '';
    }

    const minutes = Math.max(0, Math.floor(ms / 60_000));
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days >= 1) {
        return days === 1 ? 'hace 1 día' : `hace ${days} días`;
    }

    if (hours >= 1) {
        return `hace ${hours} h`;
    }

    if (minutes >= 1) {
        return `hace ${minutes} min`;
    }

    return 'recién';
}

export type TimeLeft = {
    label: string;
    /** Menos de 3 horas: se pinta en rojo para que se note. */
    urgent: boolean;
    expired: boolean;
};

/**
 * Cuánto le queda a una publicación, en palabras cortas para el celular.
 * "Quedan 2 días", "Quedan 5 h", "Quedan 20 min", "Vencida".
 */
export function timeLeft(expiresAt: string, now: number): TimeLeft {
    const ms = new Date(expiresAt).getTime() - now;

    if (Number.isNaN(ms) || ms <= 0) {
        return { label: 'Vencida', urgent: false, expired: true };
    }

    const minutes = Math.floor(ms / 60_000);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);
    const urgent = hours < 3;

    if (days >= 1) {
        return {
            label: days === 1 ? 'Queda 1 día' : `Quedan ${days} días`,
            urgent: false,
            expired: false,
        };
    }

    if (hours >= 1) {
        return { label: `Quedan ${hours} h`, urgent, expired: false };
    }

    if (minutes >= 1) {
        return { label: `Quedan ${minutes} min`, urgent: true, expired: false };
    }

    return { label: 'Último minuto', urgent: true, expired: false };
}
