import { Check, ChevronDown, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type { Variety } from '@/types';

type Props = {
    varieties: Variety[];
    value: number | null;
    onChange: (varietyId: number) => void;
    /** Variedades ya usadas en otras líneas: se marcan, pero siguen elegibles. */
    alreadyUsed?: number[];
    invalid?: boolean;
};

/**
 * Elegir variedad en el celular: un botón grande que abre una lista con
 * buscador. Evita el <select> nativo, imposible de usar con 300 variedades.
 */
export function VarietyPicker({
    varieties,
    value,
    onChange,
    alreadyUsed = [],
    invalid = false,
}: Props) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');

    const selected = varieties.find((v) => v.id === value) ?? null;

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term
            ? varieties.filter((v) => v.name.toLowerCase().includes(term))
            : varieties;
    }, [search, varieties]);

    const choose = (varietyId: number) => {
        onChange(varietyId);
        setOpen(false);
        setSearch('');
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    aria-invalid={invalid}
                    className={cn(
                        'h-12 w-full justify-between px-3 text-base',
                        !selected && 'text-muted-foreground',
                    )}
                >
                    <span className="truncate">
                        {selected ? selected.name : 'Elegir variedad'}
                    </span>
                    <ChevronDown className="size-5 shrink-0 opacity-60" />
                </Button>
            </DialogTrigger>

            <DialogContent className="flex max-h-[85vh] flex-col gap-3 p-0 sm:max-w-md">
                <DialogHeader className="px-4 pt-4">
                    <DialogTitle>Elegir variedad</DialogTitle>
                </DialogHeader>

                <div className="px-4">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="search"
                            autoFocus
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar variedad"
                            className="h-12 pl-10 text-base"
                        />
                    </div>
                </div>

                <ul className="min-h-0 flex-1 overflow-y-auto px-2 pb-4">
                    {filtered.map((variety) => {
                        const isSelected = variety.id === value;

                        return (
                            <li key={variety.id}>
                                <button
                                    type="button"
                                    onClick={() => choose(variety.id)}
                                    className={cn(
                                        'flex min-h-13 w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-accent',
                                        isSelected &&
                                            'bg-violet-50 dark:bg-violet-950/40',
                                    )}
                                >
                                    <span className="flex min-w-0 flex-col">
                                        <span className="truncate text-base font-medium">
                                            {variety.name}
                                        </span>
                                        {variety.color && (
                                            <span className="text-sm text-muted-foreground">
                                                {variety.color}
                                            </span>
                                        )}
                                    </span>

                                    {alreadyUsed.includes(variety.id) &&
                                        !isSelected && (
                                            <span className="ml-auto shrink-0 text-xs text-muted-foreground">
                                                ya en la lista
                                            </span>
                                        )}

                                    {isSelected && (
                                        <Check className="ml-auto size-5 shrink-0 text-primary" />
                                    )}
                                </button>
                            </li>
                        );
                    })}

                    {filtered.length === 0 && (
                        <li className="px-3 py-6 text-center text-muted-foreground">
                            No hay variedades con “{search}”.
                        </li>
                    )}
                </ul>
            </DialogContent>
        </Dialog>
    );
}
