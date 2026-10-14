import { Form, Head } from '@inertiajs/react';
import { Sprout, Warehouse } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import { store } from '@/routes/register';

type CompanyType = 'postharvest' | 'grower';

type Props = {
    type: CompanyType;
    companyTypes: { value: CompanyType; label: string }[];
    zones: { id: number; name: string }[];
    passwordRules: string;
};

const TYPE_ICONS = { postharvest: Warehouse, grower: Sprout };

const bigField = 'h-12 text-base';

export default function Register({
    type: initialType,
    companyTypes,
    zones,
    passwordRules,
}: Props) {
    const [type, setType] = useState<CompanyType>(initialType);
    const isGrower = type === 'grower';

    return (
        <>
            <Head title="Crear cuenta" />
            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <input type="hidden" name="type" value={type} />

                        <div className="grid grid-cols-2 gap-3">
                            {companyTypes.map((option) => {
                                const Icon = TYPE_ICONS[option.value];
                                const selected = option.value === type;

                                return (
                                    <button
                                        key={option.value}
                                        type="button"
                                        onClick={() => setType(option.value)}
                                        aria-pressed={selected}
                                        className={cn(
                                            'flex h-16 items-center justify-center gap-2 rounded-xl border-2 text-base font-medium transition-colors',
                                            selected
                                                ? 'border-primary bg-primary text-primary-foreground'
                                                : 'border-input bg-background text-foreground',
                                        )}
                                    >
                                        <Icon className="size-5" />
                                        {option.label}
                                    </button>
                                );
                            })}
                        </div>
                        <InputError message={errors.type} />

                        <fieldset className="grid gap-5">
                            <legend className="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                {isGrower ? 'Tu finca' : 'Tu poscosecha'}
                            </legend>

                            <div className="grid gap-2">
                                <Label htmlFor="company_name">
                                    {isGrower
                                        ? 'Nombre de la finca'
                                        : 'Nombre de la poscosecha'}
                                </Label>
                                <Input
                                    id="company_name"
                                    name="company_name"
                                    required
                                    autoFocus
                                    className={bigField}
                                    placeholder={
                                        isGrower
                                            ? 'Finca San José'
                                            : 'Poscosecha Las Rosas'
                                    }
                                />
                                <InputError message={errors.company_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tax_id">RUC o cédula</Label>
                                <Input
                                    id="tax_id"
                                    name="tax_id"
                                    inputMode="numeric"
                                    required
                                    maxLength={13}
                                    className={bigField}
                                    placeholder="Solo números"
                                />
                                <InputError message={errors.tax_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="zone_id">Sector</Label>
                                <select
                                    id="zone_id"
                                    name="zone_id"
                                    required
                                    defaultValue=""
                                    className={cn(
                                        'w-full rounded-md border border-input bg-transparent px-3 shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                        bigField,
                                    )}
                                >
                                    <option value="" disabled>
                                        Elige tu sector
                                    </option>
                                    {zones.map((zone) => (
                                        <option key={zone.id} value={zone.id}>
                                            {zone.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.zone_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address_reference">
                                    Referencia de ubicación{' '}
                                    <span className="font-normal text-muted-foreground">
                                        (opcional)
                                    </span>
                                </Label>
                                <Input
                                    id="address_reference"
                                    name="address_reference"
                                    className={bigField}
                                    placeholder="Junto a la iglesia, vía a…"
                                />
                                <InputError
                                    message={errors.address_reference}
                                />
                            </div>
                        </fieldset>

                        <fieldset className="grid gap-5">
                            <legend className="mb-3 text-sm font-semibold tracking-wide text-muted-foreground uppercase">
                                Tus datos
                            </legend>

                            <div className="grid gap-2">
                                <Label htmlFor="name">Tu nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    autoComplete="name"
                                    className={bigField}
                                    placeholder="Nombre y apellido"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Celular</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    inputMode="tel"
                                    required
                                    autoComplete="tel-national"
                                    className={bigField}
                                    placeholder="0991234567"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Contraseña</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="new-password"
                                    className={bigField}
                                    placeholder="Mínimo 8 caracteres"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>
                        </fieldset>

                        <Button
                            type="submit"
                            size="lg"
                            className="h-12 w-full text-base"
                            data-test="register-user-button"
                        >
                            {processing && <Spinner />}
                            Crear cuenta
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            ¿Ya tienes cuenta?{' '}
                            <TextLink href={login()}>Ingresar</TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Crea tu cuenta',
    description: 'Te verificamos en menos de 24 horas',
};
