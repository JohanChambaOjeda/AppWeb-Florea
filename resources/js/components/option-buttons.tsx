import { cn } from '@/lib/utils';
import type { Option } from '@/types';

type Props = {
    options: Option[];
    value: string | null;
    onChange: (value: string | null) => void;
    /** Permite desmarcar tocando la opción ya elegida (campos opcionales). */
    clearable?: boolean;
    size?: 'default' | 'sm';
    className?: string;
};

/**
 * Elegir en vez de escribir: botones grandes en una fila que se envuelve.
 * Se usa para destino, punto de corte, entrega y unidad.
 */
export function OptionButtons({
    options,
    value,
    onChange,
    clearable = false,
    size = 'default',
    className,
}: Props) {
    return (
        <div className={cn('flex flex-wrap gap-2', className)}>
            {options.map((option) => {
                const on = option.value === value;

                return (
                    <button
                        key={option.value}
                        type="button"
                        aria-pressed={on}
                        onClick={() =>
                            onChange(on && clearable ? null : option.value)
                        }
                        className={cn(
                            'rounded-lg border-2 font-medium transition-colors',
                            size === 'sm'
                                ? 'h-10 px-3 text-sm'
                                : 'h-12 px-4 text-base',
                            on
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-input bg-background',
                        )}
                    >
                        {option.label}
                    </button>
                );
            })}
        </div>
    );
}
