import { Form, Head, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { PushToggle } from '@/components/push-toggle';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';

/** +593991234567 → 0991234567 */
function localPhone(e164: string): string {
    return e164.startsWith('+593') ? `0${e164.slice(4)}` : e164;
}

export default function Profile() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Mis datos" />

            <h1 className="sr-only">Mis datos</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Mis datos"
                    description="Tu nombre y el celular con el que ingresas"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block h-12 w-full text-base"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Nombre y apellido"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Celular</Label>

                                <Input
                                    id="phone"
                                    type="tel"
                                    inputMode="tel"
                                    className="mt-1 block h-12 w-full text-base"
                                    defaultValue={localPhone(auth.user.phone)}
                                    name="phone"
                                    required
                                    autoComplete="tel-national"
                                    placeholder="0991234567"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.phone}
                                />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    size="lg"
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Guardar
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <PushToggle />
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Mis datos',
            href: edit(),
        },
    ],
};
