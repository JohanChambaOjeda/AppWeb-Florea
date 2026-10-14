import { Head, Link } from '@inertiajs/react';
import { Clock, MessageCircle, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';

type Props = {
    companyName: string | null;
    rejected: boolean;
    rejectionReason: string | null;
    whatsappUrl: string | null;
};

export default function Pending({
    companyName,
    rejected,
    rejectionReason,
    whatsappUrl,
}: Props) {
    return (
        <>
            <Head
                title={rejected ? 'Cuenta no aprobada' : 'Cuenta en revisión'}
            />

            <div className="flex flex-col items-center gap-6 text-center">
                {rejected ? (
                    <div className="flex size-16 items-center justify-center rounded-full bg-red-100 text-red-700">
                        <XCircle className="size-8" />
                    </div>
                ) : (
                    <div className="flex size-16 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                        <Clock className="size-8" />
                    </div>
                )}

                <div className="grid gap-1">
                    <h2 className="text-xl font-semibold">
                        {rejected
                            ? 'Tu cuenta no fue aprobada'
                            : 'Tu cuenta está en revisión'}
                    </h2>
                    {companyName && (
                        <p className="text-muted-foreground">{companyName}</p>
                    )}
                </div>

                {rejected ? (
                    <div className="grid gap-2 text-base text-muted-foreground">
                        <p>No pudimos aprobar tu cuenta.</p>
                        {rejectionReason && (
                            <p className="rounded-lg bg-muted p-3 text-foreground">
                                {rejectionReason}
                            </p>
                        )}
                        <p>Escríbenos y lo revisamos juntos.</p>
                    </div>
                ) : (
                    <p className="text-base text-muted-foreground">
                        Te verificamos en menos de 24 horas. Revisamos tus datos
                        y te avisamos. Cuando tu cuenta esté verificada, podrás
                        publicar y ofertar.
                    </p>
                )}

                {whatsappUrl && (
                    <a
                        href={whatsappUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex h-12 w-full items-center justify-center gap-2 rounded-md bg-[#25D366] font-medium text-white"
                    >
                        <MessageCircle className="size-5" />
                        Escríbenos por WhatsApp
                    </a>
                )}

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
    title: '',
    description: '',
};
