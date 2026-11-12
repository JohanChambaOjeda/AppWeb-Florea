import { usePage } from '@inertiajs/react';
import {
    Building2,
    ClipboardList,
    Flower2,
    HandCoins,
    Handshake,
    LayoutGrid,
    MapPin,
    MessageSquareWarning,
    Sprout,
    Star,
    Target,
} from 'lucide-react';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import myOffers from '@/routes/my-offers';
import myVarieties from '@/routes/my-varieties';
import type { NavItem } from '@/types';

/**
 * Menú principal según el rol: el admin ve su panel; las empresas, la app.
 */
export function useMainNav(): NavItem[] {
    const { auth } = usePage().props;

    if (auth.isAdmin) {
        return [
            { title: 'Inicio', href: admin.dashboard(), icon: LayoutGrid },
            { title: 'Métricas', href: admin.metrics.index(), icon: Target },
            {
                title: 'Comentarios',
                href: admin.feedback.index(),
                icon: MessageSquareWarning,
            },
            {
                title: 'Publicaciones',
                href: admin.listings.index(),
                icon: ClipboardList,
            },
            { title: 'Tratos', href: admin.deals.index(), icon: Handshake },
            {
                title: 'Calificaciones',
                href: admin.ratings.index(),
                icon: Star,
            },
            {
                title: 'Empresas',
                href: admin.companies.index(),
                icon: Building2,
            },
            {
                title: 'Variedades',
                href: admin.varieties.index(),
                icon: Flower2,
            },
            { title: 'Sectores', href: admin.zones.index(), icon: MapPin },
        ];
    }

    const items: NavItem[] = [
        { title: 'Inicio', href: dashboard(), icon: LayoutGrid },
        { title: 'Mis ofertas', href: myOffers.index(), icon: HandCoins },
    ];

    if (auth.company?.type === 'grower') {
        items.push({
            title: 'Mis variedades',
            href: myVarieties.edit(),
            icon: Sprout,
        });
    }

    return items;
}
