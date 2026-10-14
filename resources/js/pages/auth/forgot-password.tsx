import { Head } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
import TextLink from '@/components/text-link';
import { login } from '@/routes';

type Props = {
    whatsappUrl: string | null;
};

export default function ForgotPassword({ whatsappUrl }: Props) {
    return (
        <>
            <Head title="Olvidé mi contraseña" />

            <div className="flex flex-col gap-6">
                {whatsappUrl ? (
                    <a
                        href={whatsappUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex h-14 items-center justify-center gap-3 rounded-xl bg-[#25D366] text-lg font-medium text-white shadow"
                    >
                        <MessageCircle className="size-6" />
                        Escríbenos por WhatsApp
                    </a>
                ) : (
                    <p className="text-center text-muted-foreground">
                        Comunícate con el equipo de Florea.
                    </p>
                )}

                <div className="text-center text-sm text-muted-foreground">
                    ¿La recordaste?{' '}
                    <TextLink href={login()}>Volver a ingresar</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: '¿Olvidaste tu contraseña?',
    description:
        'Escríbenos por WhatsApp desde tu celular y te ayudamos a recuperarla.',
};
