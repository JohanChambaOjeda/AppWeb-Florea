import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
};

export default function Login({ status }: Props) {
    return (
        <>
            <Head title="Ingresar" />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Celular</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    inputMode="tel"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="tel-national"
                                    placeholder="0991234567"
                                    className="h-12 text-lg"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Contraseña</Label>
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-sm"
                                        tabIndex={5}
                                    >
                                        Olvidé mi contraseña
                                    </TextLink>
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Tu contraseña"
                                    className="h-12 text-lg"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                    defaultChecked
                                />
                                <Label htmlFor="remember">Recordarme</Label>
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="mt-2 h-12 w-full text-base"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Ingresar
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿No tienes cuenta?{' '}
                            <TextLink href={home()} tabIndex={5}>
                                Regístrate
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Ingresa a Florea',
    description: 'Con tu número de celular y tu contraseña',
};
