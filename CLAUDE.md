# Florea — contexto del proyecto

Florea es una app web de Grizzi Technology (Tabacundo, Ecuador). Conecta poscosechas y pequeños productores de rosas del cantón Pedro Moncayo para que una poscosecha encuentre en horas, no en días, la flor que le falta para completar un pedido. También sirve para mover sobrantes: cualquiera puede publicar lo que busca ("Busco") o lo que tiene ("Disponible").

Cliente que paga: la vendedora de la poscosecha (suscripción mensual a partir del 10 de diciembre de 2026). Los productores usan la app gratis.

## Stack (decidido, no cambiar sin consultar)

- Laravel 13 (PHP 8.3+) con el starter kit oficial de React: Inertia + React + TypeScript + Tailwind + shadcn/ui
- MySQL 8 (local en Laragon; en producción, Hostinger Business Web Hosting, que no tiene PostgreSQL)
- Tests con Pest
- Colas con driver `database` y cron cada minuto (Hostinger compartido no permite procesos permanentes)
- Notificaciones push del navegador (Web Push) a partir de la semana 3
- La app debe ser PWA (instalable) a partir de la semana 3

## Convenciones

- Código, tablas, columnas, modelos y rutas en inglés. Toda la interfaz y los mensajes de validación en español (Ecuador).
- Zona horaria: America/Guayaquil. Locale: es.
- Celular primero: botones grandes, poco texto, elegir en vez de escribir. Probar todo a 375 px de ancho.
- Colores: primario violeta (Tailwind `violet-600`), acento celeste (`sky-500`), fondos claros.
- Usar Enums de PHP para tipos y estados (no strings sueltos).
- No usar características que Hostinger compartido no soporte (websockets, Redis, procesos daemon, Node en el servidor). El build del frontend se hace en local.

## Glosario del sector

- Poscosecha (postharvest): empresa que compra, clasifica, empaca y exporta flor.
- Productor (grower): cultivo pequeño que vende su flor a poscosechas.
- Variedad: nombre comercial de la rosa (Freedom, Explorer, Pink Floyd...).
- Largo: longitud del tallo en cm (estándar 40, 50, 60, 70, 80, 90; puede haber largos especiales como 100 o 120). Un pedido puede aceptar varios largos ("60/50").
- Unidades: FB (full box), HB (half box), QB (quarter box), bonche (25 tallos), malla, tallo.
- Punto de corte: cerrado (Rusia), normal, abierto (Estados Unidos).

## Reglas de negocio clave

1. Solo empresas verificadas (`verified_at` no nulo) pueden publicar u ofertar. Las no verificadas ven una pantalla de "cuenta en revisión".
2. El teléfono de otra empresa solo se muestra si existe un trato (`deal`) entre ambas.
3. Una oferta de un productor no se puede enviar sin foto.
4. Ingreso con número de celular de Ecuador + contraseña. El celular se guarda normalizado en formato E.164 (+5939XXXXXXXX).
5. La verificación es manual desde el panel admin. Más adelante se pedirá foto de la cédula (la columna ya existe).

## Forma de trabajo

- Avanzar por pasos. Al terminar cada paso: correr migraciones y tests, mostrar un resumen corto de lo hecho y esperar aprobación antes de seguir.
- Hacer un commit de git local por paso, con mensaje en español.
- Ante una duda de negocio, preguntar en vez de suponer.

## Entorno local (notas)

- PHP CLI: el del PATH es `C:\xampp\php\php.exe` (8.4). Laragon tiene PHP 8.3 sin configurar para CLI.
- Base de datos: MySQL 8.4 de Laragon en el puerto 3306 (base `florea`). Ojo: XAMPP corre un MariaDB 10.4 como servicio en el puerto 3307; no usarlo.
- Tests: Pest con SQLite en memoria (ver `phpunit.xml`). Para correrlos sobre MySQL 8 (como producción), usar la base `florea_testing`:
  `DB_CONNECTION=mysql DB_DATABASE=florea_testing php vendor/bin/pest`
- Tras cambiar rutas: `php artisan wayfinder:generate --with-form` (genera `resources/js/routes` y `resources/js/actions`, que no van a git).
- Datos demo: `php artisan migrate:fresh --seed` (el DemoSeeder solo corre en local). Usuarios demo con contraseña `florea2026`; admin desde `ADMIN_PHONE` / `ADMIN_PASSWORD`.
