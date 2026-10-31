import { Form, Head, router } from '@inertiajs/react';
import { Check, MessageSquare, RotateCcw } from 'lucide-react';
import AdminFeedbackController from '@/actions/App/Http/Controllers/Admin/FeedbackController';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import admin from '@/routes/admin';
import type { Paginated } from '@/types';

type Row = {
    id: number;
    message: string;
    screen: string | null;
    user: string | null;
    company: string | null;
    companyType: string | null;
    resolved: boolean;
    createdAt: string;
};

type Props = {
    items: Paginated<Row>;
    showResolved: boolean;
    pending: number;
};

const when = (iso: string): string =>
    new Date(iso).toLocaleString('es-EC', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });

export default function AdminFeedback({ items, showResolved, pending }: Props) {
    return (
        <>
            <Head title="Comentarios" />

            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-xl font-semibold">
                        Comentarios de usuarios
                    </h1>
                    <Button
                        variant="outline"
                        onClick={() =>
                            router.get(
                                admin.feedback.index().url,
                                showResolved ? {} : { resolved: 1 },
                                { preserveScroll: true },
                            )
                        }
                    >
                        {showResolved ? 'Ver solo pendientes' : 'Ver todos'}
                    </Button>
                </div>

                <p className="text-sm text-muted-foreground">
                    {pending === 0
                        ? 'No hay comentarios pendientes.'
                        : `${pending} ${pending === 1 ? 'pendiente' : 'pendientes'}.`}
                </p>

                {items.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-8 text-center">
                        <MessageSquare className="size-8 text-muted-foreground" />
                        <p className="text-muted-foreground">
                            Nadie ha reportado nada todavía.
                        </p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {items.data.map((item) => (
                            <li
                                key={item.id}
                                className={cn(
                                    'flex flex-col gap-2 rounded-xl border p-4',
                                    item.resolved
                                        ? 'bg-muted/40'
                                        : 'border-amber-300 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30',
                                )}
                            >
                                <p className="text-base">{item.message}</p>

                                <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <span>{when(item.createdAt)}</span>
                                    {item.user !== null && (
                                        <span>{item.user}</span>
                                    )}
                                    {item.company !== null && (
                                        <span>
                                            {item.company} ({item.companyType})
                                        </span>
                                    )}
                                    {item.screen !== null && (
                                        <span className="rounded bg-muted px-1.5 py-0.5 font-mono">
                                            {item.screen}
                                        </span>
                                    )}
                                </div>

                                <Form
                                    {...AdminFeedbackController.toggle.form(
                                        item.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={processing}
                                            className="self-start"
                                        >
                                            {item.resolved ? (
                                                <>
                                                    <RotateCcw />
                                                    Reabrir
                                                </>
                                            ) : (
                                                <>
                                                    <Check />
                                                    Marcar resuelto
                                                </>
                                            )}
                                        </Button>
                                    )}
                                </Form>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

AdminFeedback.layout = {
    breadcrumbs: [
        { title: 'Panel admin', href: admin.dashboard() },
        { title: 'Comentarios', href: admin.feedback.index() },
    ],
};
