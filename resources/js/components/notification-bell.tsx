import { router, usePage } from '@inertiajs/react';
import {
    Bell,
    CalendarX,
    Check,
    CircleSlash,
    HandCoins,
    PartyPopper,
    Sprout,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import { useNow } from '@/hooks/use-now';
import { cn } from '@/lib/utils';
import { sinceLabel } from '@/lib/time-left';
import notifications from '@/routes/notifications';
import type { AppNotification } from '@/types';

const ICONS = {
    offer_received: HandCoins,
    offer_accepted: PartyPopper,
    offer_rejected: CircleSlash,
    listing_expired: CalendarX,
    matching_listing: Sprout,
} as const;

/**
 * Campana de la barra superior. El contador viaja en cada respuesta; la lista
 * se pide solo al abrir, con un reload parcial de la prop `notifications`.
 */
export function NotificationBell() {
    const page = usePage<{
        unreadNotifications: number;
        notifications: AppNotification[] | null;
    }>();
    const { unreadNotifications: unread, notifications: items } = page.props;

    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);

    const load = () => {
        setLoading(true);
        router.reload({
            only: ['notifications', 'unreadNotifications'],
            onFinish: () => setLoading(false),
        });
    };

    const openChange = (next: boolean) => {
        setOpen(next);

        if (next) {
            load();
        }
    };

    const go = (notification: AppNotification) => {
        setOpen(false);

        if (!notification.read) {
            router.post(
                notifications.read(notification.id).url,
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => {
                        if (notification.url !== null) {
                            router.visit(notification.url);
                        }
                    },
                },
            );

            return;
        }

        if (notification.url !== null) {
            router.visit(notification.url);
        }
    };

    return (
        <DropdownMenu open={open} onOpenChange={openChange}>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="relative size-10"
                    aria-label={
                        unread > 0
                            ? `Notificaciones: ${unread} sin leer`
                            : 'Notificaciones'
                    }
                >
                    <Bell className="size-5" />
                    {unread > 0 && (
                        <span className="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-violet-600 px-1 text-[10px] leading-4 font-bold text-white tabular-nums">
                            {unread > 9 ? '9+' : unread}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-[22rem] p-0">
                <div className="flex items-center justify-between border-b px-3 py-2">
                    <span className="text-sm font-semibold">
                        Notificaciones
                    </span>
                    {unread > 0 && (
                        <button
                            type="button"
                            onClick={() =>
                                router.post(
                                    notifications.readAll().url,
                                    {},
                                    {
                                        preserveScroll: true,
                                        preserveState: true,
                                    },
                                )
                            }
                            className="flex items-center gap-1 text-xs font-medium text-primary"
                        >
                            <Check className="size-3.5" />
                            Marcar todas
                        </button>
                    )}
                </div>

                <div className="max-h-[70vh] overflow-y-auto">
                    {loading && items === null ? (
                        <div className="flex justify-center p-6">
                            <Spinner />
                        </div>
                    ) : items === null || items.length === 0 ? (
                        <p className="p-6 text-center text-sm text-muted-foreground">
                            No tienes notificaciones todavía.
                        </p>
                    ) : (
                        <ul className="divide-y">
                            {items.map((item) => (
                                <NotificationRow
                                    key={item.id}
                                    notification={item}
                                    onClick={() => go(item)}
                                />
                            ))}
                        </ul>
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

function NotificationRow({
    notification,
    onClick,
}: {
    notification: AppNotification;
    onClick: () => void;
}) {
    const now = useNow();
    const Icon = ICONS[notification.kind] ?? Bell;

    return (
        <li>
            <button
                type="button"
                onClick={onClick}
                className={cn(
                    'flex w-full items-start gap-3 px-3 py-3 text-left hover:bg-accent',
                    !notification.read &&
                        'bg-violet-50/70 dark:bg-violet-950/30',
                )}
            >
                <Icon
                    className={cn(
                        'mt-0.5 size-5 shrink-0',
                        notification.read
                            ? 'text-muted-foreground'
                            : 'text-violet-600',
                    )}
                />
                <span className="flex min-w-0 flex-col gap-0.5">
                    <span className="text-sm font-semibold">
                        {notification.title}
                    </span>
                    <span className="text-sm text-muted-foreground">
                        {notification.body}
                    </span>
                    {notification.createdAt !== null && (
                        <span className="text-xs text-muted-foreground">
                            {sinceLabel(notification.createdAt, now)}
                        </span>
                    )}
                </span>
                {!notification.read && (
                    <span
                        className="mt-1.5 size-2 shrink-0 rounded-full bg-violet-600"
                        aria-label="Sin leer"
                    />
                )}
            </button>
        </li>
    );
}
