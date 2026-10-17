import { Head, Link } from '@inertiajs/react';
import { PackageSearch, Sprout } from 'lucide-react';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import listings from '@/routes/listings';

export default function Dashboard() {
    return (
        <>
            <Head title="Inicio" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <div className="grid grid-cols-2 gap-3">
                    <NewListingButton
                        kind="want"
                        icon={PackageSearch}
                        title="Busco flor"
                        hint="Me falta para completar un pedido"
                        tone="violet"
                    />
                    <NewListingButton
                        kind="have"
                        icon={Sprout}
                        title="Tengo disponible"
                        hint="Me sobra flor para vender"
                        tone="sky"
                    />
                </div>

                <p className="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                    Las pestañas “Pedidos para mí” y “Disponible hoy” llegan en
                    el paso 2.
                </p>
            </div>
        </>
    );
}

function NewListingButton({
    kind,
    icon: Icon,
    title,
    hint,
    tone,
}: {
    kind: 'want' | 'have';
    icon: typeof PackageSearch;
    title: string;
    hint: string;
    tone: 'violet' | 'sky';
}) {
    return (
        <Link
            href={listings.create({ query: { kind } })}
            className={cn(
                'flex min-h-28 flex-col justify-between rounded-xl p-4 text-white transition-opacity hover:opacity-90',
                tone === 'violet' ? 'bg-violet-600' : 'bg-sky-500',
            )}
        >
            <Icon className="size-7" />
            <span className="flex flex-col">
                <span className="text-lg font-semibold">{title}</span>
                <span className="text-sm text-white/80">{hint}</span>
            </span>
        </Link>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
};
