/**
 * Formato local de Ecuador.
 *
 * Ecuador usa dólar con coma decimal: $0,45 y $1.234,50. Lo hace Intl con
 * es-EC, que es la única forma de no equivocarse con los separadores.
 */
const MONEY = new Intl.NumberFormat('es-EC', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * Precio listo para mostrar: "$0,45". El backend manda el decimal crudo
 * ("0.45") porque es lo que necesitan los formularios; el formato se decide
 * aquí, en un solo sitio.
 */
export function money(value: string | number | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    const amount = typeof value === 'number' ? value : Number(value);

    return Number.isFinite(amount) ? MONEY.format(amount) : '—';
}
