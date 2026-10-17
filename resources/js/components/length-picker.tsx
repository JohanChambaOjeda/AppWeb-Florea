import { Plus } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { LengthRange } from '@/types';

type Props = {
    value: number[];
    onChange: (lengths: number[]) => void;
    standardLengths: number[];
    customLength: LengthRange;
    label?: string;
};

/**
 * Largos en cm: los estándar como botones de selección múltiple, más
 * "Otro largo" para los especiales (100, 120...). Compartido por el
 * formulario de publicación y los filtros.
 */
export function LengthPicker({
    value,
    onChange,
    standardLengths,
    customLength,
    label = 'Largos (cm)',
}: Props) {
    const [custom, setCustom] = useState('');
    const [customError, setCustomError] = useState<string | null>(null);

    // Los largos especiales ya elegidos se muestran como botones extra,
    // para poder quitarlos tocándolos otra vez.
    const extra = value.filter((l) => !standardLengths.includes(l));
    const all = [...standardLengths, ...extra.sort((a, b) => a - b)];

    const toggle = (length: number) => {
        onChange(
            value.includes(length)
                ? value.filter((l) => l !== length)
                : [...value, length].sort((a, b) => a - b),
        );
    };

    const addCustom = () => {
        const parsed = Number(custom);

        if (
            !Number.isInteger(parsed) ||
            parsed < customLength.min ||
            parsed > customLength.max
        ) {
            setCustomError(
                `Escribe un largo entre ${customLength.min} y ${customLength.max} cm.`,
            );

            return;
        }

        if (!value.includes(parsed)) {
            onChange([...value, parsed].sort((a, b) => a - b));
        }

        setCustom('');
        setCustomError(null);
    };

    return (
        <div className="flex flex-col gap-2">
            <p className="text-sm font-medium text-muted-foreground">{label}</p>

            <div className="flex flex-wrap gap-2">
                {all.map((length) => {
                    const on = value.includes(length);

                    return (
                        <button
                            key={length}
                            type="button"
                            onClick={() => toggle(length)}
                            aria-pressed={on}
                            className={cn(
                                'h-11 min-w-14 rounded-lg border-2 px-3 text-base font-semibold tabular-nums transition-colors',
                                on
                                    ? 'border-sky-500 bg-sky-500 text-white'
                                    : 'border-input bg-background',
                            )}
                        >
                            {length}
                        </button>
                    );
                })}
            </div>

            <div className="flex gap-2">
                <Input
                    type="number"
                    inputMode="numeric"
                    min={customLength.min}
                    max={customLength.max}
                    value={custom}
                    onChange={(e) => {
                        setCustom(e.target.value);
                        setCustomError(null);
                    }}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            addCustom();
                        }
                    }}
                    placeholder="Otro largo"
                    aria-label="Otro largo"
                    className="h-11 max-w-32 text-base"
                />
                <Button
                    type="button"
                    variant="outline"
                    className="h-11"
                    onClick={addCustom}
                    disabled={custom === ''}
                >
                    <Plus />
                    Agregar
                </Button>
            </div>

            <InputError message={customError ?? undefined} />
        </div>
    );
}
