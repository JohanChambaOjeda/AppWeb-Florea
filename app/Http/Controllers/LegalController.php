<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Privacidad y términos.
 *
 * BORRADOR: el texto está en lenguaje llano a propósito, porque lo van a
 * leer productores en el celular, no abogados. Falta que lo revise alguien
 * con criterio legal antes del piloto.
 *
 * Está alineado con la Ley Orgánica de Protección de Datos Personales del
 * Ecuador: qué se guarda, para qué, con quién se comparte y cómo pedir que
 * se borre.
 */
class LegalController extends Controller
{
    private const UPDATED_AT = '30 de octubre de 2026';

    public function privacy(): Response
    {
        return Inertia::render('legal', [
            'kind' => 'privacy',
            'updatedAt' => self::UPDATED_AT,
            'sections' => [
                [
                    'heading' => 'Qué datos guardamos',
                    'body' => [
                        'De la persona: tu nombre y tu número de celular. El celular es además tu usuario para entrar.',
                        'De la empresa: el nombre, el RUC o la cédula, el sector donde está y una referencia de ubicación si la escribes.',
                        'De lo que publicas: las variedades, los largos, las cantidades, los precios que ofreces y las fotos de la flor.',
                        'Del uso: cuándo entraste por última vez, para saber qué empresas están activas durante el piloto.',
                    ],
                ],
                [
                    'heading' => 'Para qué los usamos',
                    'body' => [
                        'Para conectar a quien busca flor con quien la tiene. Esa es la única razón por la que existe Florea.',
                        'Para avisarte cuando hay un pedido de tus variedades o una oferta en tu publicación.',
                        'Para medir si Florea sirve: cuánto se tarda en recibir la primera oferta y cuántos tratos se cierran.',
                    ],
                ],
                [
                    'heading' => 'Quién ve tu número de celular',
                    'body' => [
                        'Tu celular NO se muestra en las publicaciones ni en las ofertas.',
                        'Solo se le muestra a la otra empresa cuando se acepta una oferta y queda cerrado el trato, porque a partir de ahí tienen que coordinar la entrega.',
                        'El equipo de Florea puede verlo para dar soporte y verificar empresas.',
                    ],
                ],
                [
                    'heading' => 'Las fotos de la flor',
                    'body' => [
                        'Las fotos que subes con una oferta las ve la empresa que publicó el pedido.',
                        'Se guardan reducidas, sin los datos de ubicación que a veces trae la cámara del celular.',
                    ],
                ],
                [
                    'heading' => 'Tus derechos',
                    'body' => [
                        'Puedes pedir ver, corregir o borrar tus datos cuando quieras, como reconoce la Ley Orgánica de Protección de Datos Personales del Ecuador.',
                        'Para pedirlo, escríbenos por WhatsApp al número de soporte o usa "¿Algo no funcionó?" dentro de la app.',
                        'Si borramos tu cuenta, se borran tus publicaciones, tus ofertas y tus fotos. Los tratos ya cerrados se conservan sin tus datos personales, porque la otra empresa también fue parte.',
                    ],
                ],
                [
                    'heading' => 'Con quién compartimos',
                    'body' => [
                        'Con nadie. No vendemos datos ni los pasamos a terceros con fines de publicidad.',
                        'Los datos están alojados en los servidores de nuestro proveedor de hosting, que los guarda por encargo nuestro.',
                    ],
                ],
            ],
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('legal', [
            'kind' => 'terms',
            'updatedAt' => self::UPDATED_AT,
            'sections' => [
                [
                    'heading' => 'Qué es Florea',
                    'body' => [
                        'Florea es un lugar donde las poscosechas y los productores de rosas del cantón Pedro Moncayo se encuentran: uno publica lo que le falta o lo que le sobra, y el otro responde.',
                        'Florea no compra ni vende flor. No somos parte del trato.',
                    ],
                ],
                [
                    'heading' => 'Quién puede usarla',
                    'body' => [
                        'Empresas reales del sector florícola, con RUC o cédula, verificadas por nuestro equipo antes de poder publicar u ofertar.',
                        'Cada empresa responde por lo que publica y por lo que hacen sus usuarios.',
                    ],
                ],
                [
                    'heading' => 'El trato es entre ustedes',
                    'body' => [
                        'Cuando se acepta una oferta, el acuerdo es entre las dos empresas: el precio, la entrega, el pago y la calidad.',
                        'Florea no garantiza la calidad de la flor, ni el pago, ni el cumplimiento. Lo que sí hacemos es dejar constancia de lo acordado y permitir que se califiquen.',
                        'Si algo sale mal, cuéntanoslo: no podemos obligar a nadie, pero sí podemos suspender a quien incumple repetidamente.',
                    ],
                ],
                [
                    'heading' => 'Lo que no se puede hacer',
                    'body' => [
                        'Publicar flor que no tienes o pedidos falsos.',
                        'Subir fotos que no sean de tu flor del día.',
                        'Usar los números de celular que obtienes en un trato para mandar publicidad.',
                        'Hacerte pasar por otra empresa.',
                    ],
                ],
                [
                    'heading' => 'Precio',
                    'body' => [
                        'Durante el piloto, Florea es gratis para todos.',
                        'A partir del 10 de diciembre de 2026, las poscosechas pagan una suscripción mensual. Los productores siguen usándola gratis.',
                        'Te avisaremos con tiempo antes de cualquier cobro.',
                    ],
                ],
                [
                    'heading' => 'Cerrar la cuenta',
                    'body' => [
                        'Puedes pedir que cerremos tu cuenta cuando quieras, sin dar explicaciones.',
                        'Nosotros podemos suspender una cuenta que incumpla estos términos, avisando el motivo.',
                    ],
                ],
            ],
        ]);
    }
}
