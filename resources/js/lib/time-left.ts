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
