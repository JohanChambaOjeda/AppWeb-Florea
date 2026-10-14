import { Form, Head, Link, router } from '@inertiajs/react';
import { Check, MessageCircle, X } from 'lucide-react';
import { useState } from 'react';
import CompanyController from '@/actions/App/Http/Controllers/Admin/CompanyController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';

type Status = 'pending' | 'verified' | 'rejected';

type CompanyRow = {
    id: number;
    name: string;
    type: 'postharvest' | 'grower';
    typeLabel: string;
    taxId: string;
    zone: string | null;
    addressReference: string | null;
    ownerName: string | null;
    ownerPhone: string | null;
    ownerWhatsapp: string | null;
    status: Status;
    rejectionReason: string | null;
    registeredAt: string;
};

type Paginated<T> = {
    data: T[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    last_page: number;
};

type Props = {
    companies: Paginated<CompanyRow>;
    filters: {
        tab: 'pending' | 'all';
        type: string | null;
        status: string | null;
    };
    pendingCount: number;
    companyTypes: { value: string; label: string }[];
};

const STATUS_LABELS: Record<Status, string> = {
    pending: 'Por verificar',
    verified: 'Verificada',
    rejected: 'Rechazada',
};

const STATUS_STYLES: Record<Status, string> = {
    pending: 'border-sky-200 bg-sky-50 text-sky-800',
    verified: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    rejected: 'border-red-200 bg-red-50 text-red-800',
};

const dateFormat = new Intl.DateTimeFormat('es-EC', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'America/Guayaquil',
});

const selectClass =
    'h-10 rounded-md border border-input bg-background px-3 text-sm shadow-xs';

export default function CompaniesIndex({
    companies,
    filters,
    pendingCount,
    companyTypes,
}: Props) {
    const [rejecting, setRejecting] = useState<CompanyRow | null>(null);

    const applyFilter = (key: 'type' | 'status', value: string) => {
        router.get(
            admin.companies.index().url,
            { ...filters, tab: 'all', [key]: value || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Empresas" />
            <div className="flex flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Empresas</h1>

                <nav className="flex gap-2 border-b">
                    <TabLink
                        href={admin.companies.index().url}
                        active={filters.tab === 'pending'}
                    >
                        Por verificar
                        {pendingCount > 0 && (
                            <span className="ml-2 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">
                                {pendingCount}
                            </span>
                        )}
                    </TabLink>
                    <TabLink
                        href={
                            admin.companies.index({ query: { tab: 'all' } }).url
                        }
                        active={filters.tab === 'all'}
                    >
                        Todas
                    </TabLink>
                </nav>

                {filters.tab === 'all' && (
                    <div className="flex flex-wrap gap-3">
                        <select
                            aria-label="Tipo"
                            className={selectClass}
                            value={filters.type ?? ''}
                            onChange={(e) =>
                                applyFilter('type', e.target.value)
                            }
                        >
                            <option value="">Todos los tipos</option>
                            {companyTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                        <select
                            aria-label="Estado"
                            className={selectClass}
                            value={filters.status ?? ''}
                            onChange={(e) =>
                                applyFilter('status', e.target.value)
                            }
                        >
                            <option value="">Todos los estados</option>
                            {Object.entries(STATUS_LABELS).map(
                                ([value, label]) => (
                                    <option key={value} value={value}>
                                        {label}
                                    </option>
                                ),
                            )}
                        </select>
                    </div>
                )}

                {companies.data.length === 0 ? (
                    <p className="rounded-xl border border-dashed p-8 text-center text-muted-foreground">
                        {filters.tab === 'pending'
                            ? 'No hay empresas por verificar. ¡Todo al día!'
                            : 'No hay empresas con estos filtros.'}
                    </p>
                ) : (
                    <ul className="grid gap-3">
                        {companies.data.map((company) => (
                            <li
                                key={company.id}
                                className="flex flex-col gap-3 rounded-xl border bg-card p-4 md:flex-row md:items-center md:justify-between"
                            >
                                <div className="grid gap-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="text-base font-semibold">
                                            {company.name}
                                        </span>
                                        <Badge variant="secondary">
                                            {company.typeLabel}
                                        </Badge>
                                        <Badge
                                            variant="outline"
                                            className={
                                                STATUS_STYLES[company.status]
                                            }
                                        >
                                            {STATUS_LABELS[company.status]}
                                        </Badge>
                                    </div>
                                    <dl className="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-sm text-muted-foreground">
                                        <dt>RUC/cédula</dt>
                                        <dd className="font-mono text-foreground">
                                            {company.taxId}
                                        </dd>
                                        <dt>Sector</dt>
                                        <dd className="text-foreground">
                                            {company.zone}
                                            {company.addressReference &&
                                                ` · ${company.addressReference}`}
                                        </dd>
                                        <dt>Dueño</dt>
                                        <dd className="text-foreground">
                                            {company.ownerName} ·{' '}
                                            {company.ownerWhatsapp ? (
                                                <a
                                                    href={company.ownerWhatsapp}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 text-primary underline underline-offset-4"
                                                >
                                                    <MessageCircle className="size-3.5" />
                                                    {company.ownerPhone}
                                                </a>
                                            ) : (
                                                '—'
                                            )}
                                        </dd>
                                        <dt>Registro</dt>
                                        <dd className="text-foreground">
                                            {dateFormat.format(
                                                new Date(company.registeredAt),
                                            )}
                                        </dd>
                                        {company.rejectionReason && (
                                            <>
                                                <dt>Motivo</dt>
                                                <dd className="text-foreground">
                                                    {company.rejectionReason}
                                                </dd>
                                            </>
                                        )}
                                    </dl>
                                </div>

                                {company.status === 'pending' && (
                                    <div className="flex gap-2 md:flex-col">
                                        <Form
                                            {...CompanyController.verify.form(
                                                company.id,
                                            )}
                                            options={{ preserveScroll: true }}
                                            className="flex-1"
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    size="lg"
                                                    className="w-full"
                                                    disabled={processing}
                                                >
                                                    <Check />
                                                    Verificar
                                                </Button>
                                            )}
                                        </Form>
                                        <Button
                                            size="lg"
                                            variant="outline"
                                            className="flex-1"
                                            onClick={() =>
                                                setRejecting(company)
                                            }
                                        >
                                            <X />
                                            Rechazar
                                        </Button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                )}

                {companies.last_page > 1 && (
                    <div className="flex items-center justify-between text-sm">
                        <PageLink href={companies.prev_page_url}>
                            ← Anterior
                        </PageLink>
                        <span className="text-muted-foreground">
                            Página {companies.current_page} de{' '}
                            {companies.last_page}
                        </span>
                        <PageLink href={companies.next_page_url}>
                            Siguiente →
                        </PageLink>
                    </div>
                )}
            </div>

            <Dialog
                open={rejecting !== null}
                onOpenChange={(open) => !open && setRejecting(null)}
            >
                <DialogContent>
                    {rejecting && (
                        <Form
                            {...CompanyController.reject.form(rejecting.id)}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setRejecting(null)}
                            className="grid gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>
                                            Rechazar {rejecting.name}
                                        </DialogTitle>
                                        <DialogDescription>
                                            La persona verá el motivo al
                                            ingresar a Florea.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="grid gap-2">
                                        <Label htmlFor="reason">
                                            Motivo (opcional)
                                        </Label>
                                        <Textarea
                                            id="reason"
                                            name="reason"
                                            maxLength={500}
                                            placeholder="Ej.: El RUC no corresponde a la finca."
                                        />
                                        <InputError message={errors.reason} />
                                    </div>
                                    <DialogFooter>
                                        <DialogClose asChild>
                                            <Button
                                                type="button"
                                                variant="outline"
                                            >
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            Rechazar
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

function TabLink({
    href,
    active,
    children,
}: {
    href: string;
    active: boolean;
    children: React.ReactNode;
}) {
    return (
        <Link
            href={href}
            preserveScroll
            className={cn(
                '-mb-px flex items-center border-b-2 px-3 py-2 text-sm font-medium',
                active
                    ? 'border-primary text-primary'
                    : 'border-transparent text-muted-foreground hover:text-foreground',
            )}
        >
            {children}
        </Link>
    );
}

function PageLink({
    href,
    children,
}: {
    href: string | null;
    children: React.ReactNode;
}) {
    if (!href) {
        return <span className="text-muted-foreground/50">{children}</span>;
    }

    return (
        <Link
            href={href}
            preserveScroll
            className="font-medium text-primary underline underline-offset-4"
        >
            {children}
        </Link>
    );
}

CompaniesIndex.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Empresas', href: admin.companies.index() },
    ],
};
