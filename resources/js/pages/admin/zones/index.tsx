import { Form, Head } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import { useState } from 'react';
import ZoneController from '@/actions/App/Http/Controllers/Admin/ZoneController';
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

type ZoneRow = {
    id: number;
    name: string;
    canton: string;
    isActive: boolean;
    companiesCount: number;
};

type Editing = ZoneRow | 'new' | null;

export default function ZonesIndex({ zones }: { zones: ZoneRow[] }) {
    const [editing, setEditing] = useState<Editing>(null);

    return (
        <>
            <Head title="Sectores" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">Sectores</h1>
                    <Button size="lg" onClick={() => setEditing('new')}>
                        <Plus />
                        Nuevo sector
                    </Button>
                </div>

                <ul className="divide-y rounded-xl border bg-card">
                    {zones.map((zone) => (
                        <li
                            key={zone.id}
                            className="flex flex-wrap items-center gap-3 p-3"
                        >
                            <div className="flex min-w-40 flex-1 flex-col">
                                <span
                                    className={cn(
                                        'font-medium',
                                        !zone.isActive &&
                                            'text-muted-foreground line-through',
                                    )}
                                >
                                    {zone.name}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {zone.canton} · {zone.companiesCount}{' '}
                                    {zone.companiesCount === 1
                                        ? 'empresa'
                                        : 'empresas'}
                                </span>
                            </div>

                            {!zone.isActive && (
                                <Badge variant="outline">Inactivo</Badge>
                            )}

                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setEditing(zone)}
                                >
                                    <Pencil />
                                    Editar
                                </Button>
                                <Form
                                    {...ZoneController.toggle.form(zone.id)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            {zone.isActive
                                                ? 'Desactivar'
                                                : 'Activar'}
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent>
                    {editing !== null && (
                        <ZoneForm
                            zone={editing === 'new' ? null : editing}
                            onDone={() => setEditing(null)}
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function ZoneForm({
    zone,
    onDone,
}: {
    zone: ZoneRow | null;
    onDone: () => void;
}) {
    const action = zone
        ? ZoneController.update.form(zone.id)
        : ZoneController.store.form();

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
                            {zone ? 'Editar sector' : 'Nuevo sector'}
                        </DialogTitle>
                    </DialogHeader>

                    <div className="grid gap-2">
                        <Label htmlFor="name">Nombre</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            autoFocus
                            defaultValue={zone?.name}
                            className="h-11"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="canton">Cantón</Label>
                        <Input
                            id="canton"
                            name="canton"
                            defaultValue={zone?.canton ?? 'Pedro Moncayo'}
                            className="h-11"
                        />
                        <InputError message={errors.canton} />
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

ZonesIndex.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Sectores', href: admin.zones.index() },
    ],
};
