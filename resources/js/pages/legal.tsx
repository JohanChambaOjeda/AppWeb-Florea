import { Head, Link } from '@inertiajs/react';
import { FileWarning } from 'lucide-react';
import { privacy, terms } from '@/routes';

type Section = { heading: string; body: string[] };

type Props = {
    kind: 'privacy' | 'terms';
    updatedAt: string;
    sections: Section[];
};

const TITLES = {
    privacy: 'Política de privacidad',
    terms: 'Términos de uso',
} as const;

export default function Legal({ kind, updatedAt, sections }: Props) {
    return (
        <>
            <Head title={TITLES[kind]} />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5 p-4">
                <header className="flex flex-col gap-2">
                    <h1 className="text-xl font-semibold">{TITLES[kind]}</h1>
                    <p className="text-sm text-muted-foreground">
                        Última actualización: {updatedAt} · Florea es un
                        producto de Grizzi Technology, Tabacundo, Ecuador.
                    </p>
                </header>

                {/* Aviso de borrador: el texto lo tiene que revisar Johan
                    (y ojalá un abogado) antes del piloto. */}
                <p className="flex items-start gap-2 rounded-xl border-2 border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/40">
                    <FileWarning className="mt-0.5 size-4 shrink-0" />
                    <span>
                        <strong>Borrador.</strong> Este texto está escrito en
                        lenguaje claro para que se entienda, pero todavía no lo
                        ha revisado un abogado. Revísalo antes de abrir el
                        piloto.
                    </span>
                </p>

                {sections.map((section) => (
                    <section
                        key={section.heading}
                        className="flex flex-col gap-2"
                    >
                        <h2 className="text-base font-semibold">
                            {section.heading}
                        </h2>
                        {section.body.map((paragraph, index) => (
                            <p
                                key={index}
                                className="text-base leading-relaxed text-muted-foreground"
                            >
                                {paragraph}
                            </p>
                        ))}
                    </section>
                ))}

                <Link
                    href={kind === 'privacy' ? terms() : privacy()}
                    className="text-base font-medium text-primary underline underline-offset-4"
                >
                    {kind === 'privacy'
                        ? 'Ver los términos de uso'
                        : 'Ver la política de privacidad'}
                </Link>
            </div>
        </>
    );
}
