import { usePage } from '@inertiajs/react';
import { Building2, Flower2, LayoutGrid, MapPin } from 'lucide-react';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

/**
 * Menú principal según el rol: el admin ve su panel; las empresas, la app.
 */
export function useMainNav(): NavItem[] {
    const { auth } = usePage().props;

    if (auth.isAdmin) {
        return [
            { title: 'Inicio', href: admin.dashboard(), icon: LayoutGrid },
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

    return [{ title: 'Inicio', href: dashboard(), icon: LayoutGrid }];
}
