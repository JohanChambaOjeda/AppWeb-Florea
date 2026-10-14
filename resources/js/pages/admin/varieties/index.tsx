import { Form, Head } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import VarietyController from '@/actions/App/Http/Controllers/Admin/VarietyController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type VarietyRow = {
    id: number;
    name: string;
    color: string | null;
    isActive: boolean;
    inUse: boolean;
};

type Props = {
    varieties: VarietyRow[];
    colorSuggestions: string[];
};

type Editing = VarietyRow | 'new' | null;

export default function VarietiesIndex({ varieties, colorSuggestions }: Props) {
    const [search, setSearch] = useState('');
    const [editing, setEditing] = useState<Editing>(null);

    const filtered = useMemo(() => {
        const term = search.trim().toLowerCase();

        return term
            ? varieties.filter((v) => v.name.toLowerCase().includes(term))
            : varieties;
    }, [search, varieties]);

    const activeCount = varieties.filter((v) => v.isActive).length;

    return (
        <>
            <Head title="Variedades" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Variedades</h1>
                        <p className="text-sm text-muted-foreground">
                            {activeCount} activas de {varieties.length}
                        </p>
                    </div>
                    <Button size="lg" onClick={() => setEditing('new')}>
                        <Plus />
                        Nueva variedad
                    </Button>
                </div>

                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar variedad"
                        className="h-11 pl-9"
                    />
                </div>

                <ul className="divide-y rounded-xl border bg-card">
                    {filtered.map((variety) => (
                        <li
                            key={variety.id}
                            className="flex flex-wrap items-center gap-3 p-3"
                        >
                            <div className="flex min-w-40 flex-1 flex-col">
                                <span
                                    className={cn(
                                        'font-medium',
                                        !variety.isActive &&
                                            'text-muted-foreground line-through',
                                    )}
                                >
                                    {variety.name}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {variety.color ?? 'Sin color'}
                                </span>
                            </div>

                            {!variety.isActive && (
                                <Badge variant="outline">Inactiva</Badge>
                            )}

                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditing(variety)}
                                >
                                    <Pencil />
                                    Editar
                                </Button>
                                <Form
                                    {...VarietyController.toggle.form(
                                        variety.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            {variety.isActive
                                                ? 'Desactivar'
                                                : 'Activar'}
                                        </Button>
                                    )}
                                </Form>
                                {!variety.inUse && (
                                    <Form
                                        {...VarietyController.destroy.form(
                                            variety.id,
                                        )}
                                        options={{ preserveScroll: true }}
                                        onBefore={() =>
                                            confirm(
                                                `¿Borrar la variedad ${variety.name}?`,
                                            )
                                        }
                                    >
                                        {({ processing }) => (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                disabled={processing}
                                                aria-label={`Borrar ${variety.name}`}
                                            >
                                                <Trash2 />
                                            </Button>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </li>
                    ))}
                    {filtered.length === 0 && (
                        <li className="p-6 text-center text-muted-foreground">
                            No hay variedades con “{search}”.
                        </li>
                    )}
                </ul>
            </div>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent>
                    {editing !== null && (
                        <VarietyForm
                            variety={editing === 'new' ? null : editing}
                            colorSuggestions={colorSuggestions}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function VarietyForm({
    variety,
    colorSuggestions,
    onDone,
}: {
    variety: VarietyRow | null;
    colorSuggestions: string[];
    onDone: () => void;
}) {
    const action = variety
        ? VarietyController.update.form(variety.id)
        : VarietyController.store.form();

    return (
        <Form
            {...action}
            options={{ preserveScroll: true }}
            onSuccess={onDone}
            className="grid gap-4"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>
                            {variety ? 'Editar variedad' : 'Nueva variedad'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Nombre</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            autoFocus
                            defaultValue={variety?.name}
                            className="h-11"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="color">Color</Label>
                        <Input
                            id="color"
                            name="color"
                            list="color-suggestions"
                            defaultValue={variety?.color ?? ''}
                            placeholder="Rojo, Blanco, Bicolor…"
                            className="h-11"
                        />
                        <datalist id="color-suggestions">
                            {colorSuggestions.map((color) => (
                                <option key={color} value={color} />
                            ))}
                        </datalist>
                        <InputError message={errors.color} />
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button disabled={processing}>Guardar</Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

VarietiesIndex.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Variedades', href: admin.varieties.index() },
    ],
};
