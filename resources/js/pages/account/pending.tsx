import { Head, Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

type Props = {
    companyName: string | null;
};

export default function Pending({ companyName }: Props) {
    return (
        <>
            <Head title="Cuenta en revisión" />

            <div className="flex flex-col items-center gap-6 text-center">
                <div className="flex size-16 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                    <Clock className="size-8" />
                </div>

                {companyName && (
                    <p className="text-lg font-medium">{companyName}</p>
                )}

                <p className="text-base text-muted-foreground">
                    Revisamos tus datos y te avisamos. Cuando tu cuenta esté
                    verificada, podrás publicar y ofertar.
                </p>

                <Button asChild variant="outline" size="lg" className="w-full">
                    <Link href={logout()} as="button">
                        Cerrar sesión
                    </Link>
                </Button>
            </div>
        </>
    );
}

Pending.layout = {
    title: 'Tu cuenta está en revisión',
    description: 'Te verificamos en menos de 24 horas',
};
