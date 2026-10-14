import { Head, Link, usePage } from '@inertiajs/react';
import { Sprout, Warehouse } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Bienvenido" />
            <div className="flex min-h-svh flex-col bg-gradient-to-b from-violet-50 to-sky-50 px-4 py-8 text-foreground dark:from-background dark:to-background">
                <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center gap-10">
                    <div className="flex flex-col items-center gap-3 text-center">
                        <div className="flex size-16 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg">
                            <AppLogoIcon className="size-10 fill-current" />
                        </div>
                        <h1 className="text-3xl font-semibold tracking-tight">
                            Florea
                        </h1>
                        <p className="text-base text-muted-foreground">
                            Encuentra en horas la flor que te falta.
                            <br />
                            Poscosechas y productores de Pedro Moncayo.
                        </p>
                    </div>

                    {auth.user ? (
                        <Link
                            href={dashboard()}
                            className="flex h-14 items-center justify-center rounded-xl bg-primary text-lg font-medium text-primary-foreground shadow"
                        >
                            Entrar a Florea
                        </Link>
                    ) : (
                        <div className="flex flex-col gap-4">
                            <Link
                                href={register({
                                    query: { type: 'postharvest' },
                                })}
                                className="flex h-20 items-center gap-4 rounded-xl bg-primary px-5 text-left text-primary-foreground shadow"
                            >
                                <Warehouse className="size-8 shrink-0" />
                                <span>
                                    <span className="block text-lg font-semibold">
                                        Soy poscosecha
                                    </span>
                                    <span className="block text-sm opacity-90">
                                        Busco flor para completar pedidos
                                    </span>
                                </span>
                            </Link>

                            <Link
                                href={register({ query: { type: 'grower' } })}
                                className="flex h-20 items-center gap-4 rounded-xl bg-sky-500 px-5 text-left text-white shadow"
                            >
                                <Sprout className="size-8 shrink-0" />
                                <span>
                                    <span className="block text-lg font-semibold">
                                        Soy productor
                                    </span>
                                    <span className="block text-sm opacity-90">
                                        Vendo mi flor a las poscosechas
                                    </span>
                                </span>
                            </Link>

                            <p className="mt-2 text-center text-base text-muted-foreground">
                                ¿Ya tienes cuenta?{' '}
                                <Link
                                    href={login()}
                                    className="font-medium text-primary underline underline-offset-4"
                                >
                                    Ingresar
                                </Link>
                            </p>
                        </div>
                    )}
                </main>

                <footer className="mt-8 text-center text-xs text-muted-foreground">
                    Grizzi Technology · Tabacundo, Ecuador
                </footer>
            </div>
        </>
    );
}
