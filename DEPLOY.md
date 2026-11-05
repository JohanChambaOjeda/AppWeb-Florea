# Desplegar Florea en Hostinger Business Web Hosting

Guía para poner Florea en producción. **Nada de esto se ha ejecutado todavía**:
el servidor está sin tocar. Los nombres de los menús del hPanel pueden haber
cambiado; lo que importa es el concepto, que está explicado en cada paso.

Convenciones de esta guía:

- `u123456789` → tu usuario de Hostinger (lo ves al entrar por SSH).
- `florea.ec` → tu dominio.
- `$APP` → `/home/u123456789/domains/florea.ec/florea` (el código).

---

## 0. Antes de empezar

| Necesitas                         | Dónde                                    |
| --------------------------------- | ---------------------------------------- |
| Plan Business (trae SSH)          | hPanel                                   |
| Acceso SSH activado               | hPanel → Avanzado → Acceso SSH           |
| PHP 8.3 o superior                | hPanel → Avanzado → Configuración de PHP |
| Base de datos MySQL creada        | hPanel → Bases de datos → MySQL          |
| SSL activo y HTTPS forzado        | hPanel → Seguridad → SSL                 |
| Un repositorio Git remoto privado | GitHub/GitLab (ver paso 2)               |

**HTTPS no es opcional.** Desde la semana 3 Florea es una app instalable
con avisos push, y tanto el service worker como las notificaciones **solo
funcionan sobre HTTPS**. Sin certificado, la app se ve pero no se instala
ni avisa de nada.

**Por qué PHP 8.3+:** `composer.json` exige `"php": "^8.3"` y el código usa
enums tipados y propiedades readonly. Con 8.2 no arranca.

**Importante:** Hostinger suele tener una versión de PHP para la web y otra
para la línea de comandos. Compruébalo al entrar por SSH:

```bash
php -v
```

Si no es 8.3+, busca el binario correcto y úsalo en todos los comandos y en
el cron:

```bash
ls /opt/alt/php83/usr/bin/php /opt/alt/php84/usr/bin/php 2>/dev/null
# luego, por ejemplo:
/opt/alt/php83/usr/bin/php artisan migrate --force
```

---

## 1. Estructura de carpetas

La regla de oro: **el código fuera de la carpeta pública**. Si `vendor/`,
`.env` o `storage/` quedan accesibles por web, se filtran credenciales.

Queremos esto:

```
/home/u123456789/domains/florea.ec/
├── florea/              ← todo el proyecto (NO accesible por web)
│   ├── app/
│   ├── storage/
│   ├── vendor/
│   ├── .env             ← credenciales, nunca en Git
│   └── public/          ← la ÚNICA carpeta que se sirve
│       ├── index.php
│       ├── build/       ← assets compilados (se suben a mano)
│       └── storage/     ← enlace a storage/app/public (fotos)
└── public_html/         ← se queda vacía o desaparece
```

### Opción A — Cambiar la raíz del sitio (recomendada)

En hPanel → Sitios web → `florea.ec` → Avanzado, busca la opción de cambiar
la raíz del documento (_document root_) y apúntala a:

```
/domains/florea.ec/florea/public
```

Es la opción limpia: no se duplica nada y `public_html` deja de usarse.

### Opción B — Enlace simbólico

Si el panel no deja cambiar la raíz:

```bash
cd ~/domains/florea.ec
mv public_html public_html.bak
ln -s florea/public public_html
```

Comprueba que funciona antes de borrar `public_html.bak`. Algunos servidores
rechazan que `public_html` sea un enlace; si es el caso, pasa a la opción C.

### Opción C — Shim en `public_html` (último recurso)

Deja `public_html` como raíz y pon dentro un `index.php` que arranque la app
desde fuera:

```bash
cd ~/domains/florea.ec
cp florea/public/.htaccess public_html/
cp florea/public/favicon.ico public_html/ 2>/dev/null
cp florea/public/robots.txt public_html/ 2>/dev/null
```

Y edita `public_html/index.php` partiendo del original, cambiando las dos
rutas para que apunten a `../florea`:

```php
require __DIR__.'/../florea/vendor/autoload.php';
$app = require_once __DIR__.'/../florea/bootstrap/app.php';
```

Con esta opción, `public/build` y el enlace de `storage` tienen que vivir
dentro de `public_html`, y hay que repetir las copias en cada despliegue. Por
eso es el último recurso.

---

## 2. Subir el código por Git + SSH

El repositorio es local y **no tiene remoto todavía**. Crea uno **privado**
(el historial no contiene secretos, pero el código del negocio es tuyo) y
súbelo:

```bash
# En tu máquina
git remote add origin git@github.com:TU_USUARIO/florea.git
git push -u origin main
```

Luego, en el servidor:

```bash
ssh u123456789@florea.ec
cd ~/domains/florea.ec
git clone git@github.com:TU_USUARIO/florea.git florea
cd florea
```

Para que el servidor pueda clonar un repo privado, genera una clave en el
servidor y añádela como _deploy key_ de solo lectura en GitHub:

```bash
ssh-keygen -t ed25519 -C "florea-hostinger" -f ~/.ssh/id_ed25519 -N ""
cat ~/.ssh/id_ed25519.pub   # pégala en GitHub → Settings → Deploy keys
```

> **Alternativa sin Git:** subir el proyecto por SFTP. Funciona, pero cada
> actualización se vuelve manual y es fácil olvidar un archivo. Con Git, un
> despliegue son tres comandos (paso 9).

---

## 3. El archivo `.env` de producción

`.env` está en `.gitignore`, así que **no viaja con el repositorio**: se crea
a mano una sola vez.

```bash
cd $APP
cp .env.example .env
nano .env
```

Lo que hay que cambiar respecto al ejemplo:

```ini
APP_NAME=Florea
APP_ENV=production
APP_DEBUG=false
APP_KEY=                       # se genera en el paso siguiente
APP_URL=https://florea.ec      # con https y SIN barra final

APP_TIMEZONE=America/Guayaquil
APP_LOCALE=es
APP_FALLBACK_LOCALE=es

LOG_LEVEL=warning              # en local es debug; aquí solo lo que importa

DB_CONNECTION=mysql
DB_HOST=127.0.0.1              # Hostinger usa localhost, lo da el panel
DB_PORT=3306
DB_DATABASE=u123456789_florea
DB_USERNAME=u123456789_florea
DB_PASSWORD=la-que-generaste-en-el-panel

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Admin de Florea: sin estos dos, AdminSeeder falla a propósito
ADMIN_PHONE=0999999999
ADMIN_PASSWORD=una-contraseña-larga-y-distinta-a-la-de-local

# Avisos push. Se generan en el paso 4.1, NO se copian de local.
VAPID_SUBJECT="mailto:hola@florea.ec"
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

Tres avisos que cuestan caro si se pasan por alto:

1. **`APP_DEBUG=false` siempre.** Con `true`, cualquier error muestra el
   contenido de `.env` en pantalla, incluida la contraseña de la base.
2. **`APP_URL` tiene que ser exacta y con `https`.** Las URL de las fotos se
   construyen desde ahí (`config/filesystems.php`), así que si está mal, las
   fotos de las ofertas salen roto.
3. **`ADMIN_PASSWORD` distinta de la de local.** La de desarrollo
   (`florea2026`) es pública en el repositorio.

Genera la clave de cifrado:

```bash
php artisan key:generate --force
```

---

## 4. Instalar dependencias de PHP

```bash
cd $APP
composer install --no-dev --optimize-autoloader
```

- `--no-dev` deja fuera Pest, Pint y PHPStan: en producción no hacen falta y
  son muchos megas.
- `--optimize-autoloader` genera un mapa de clases, que en hosting compartido
  (disco lento) se nota.

Si `composer` no está en el PATH:

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

Si se queda sin memoria:

```bash
php -d memory_limit=-1 composer.phar install --no-dev --optimize-autoloader
```

> **Nota:** `composer install` lanza `package:discover` al final. Si `.env`
> todavía no existe, avisa. Por eso el `.env` va antes (paso 3).

### 4.1 Llaves de los avisos push (VAPID)

Cada entorno necesita **su propio par**. Las de tu máquina no sirven aquí: si
las reutilizas, los navegadores que se suscribieron en local dejan de recibir
avisos y los de producción nunca los reciben.

```bash
cd $APP
php artisan webpush:vapid
```

El comando escribe las dos llaves en el `.env`. Compruébalo sin imprimir los
valores:

```bash
php artisan tinker --execute='
    foreach (["subject","public_key","private_key"] as $k) {
        printf("%-12s %d caracteres%s", $k, strlen((string) config("webpush.vapid.$k")), PHP_EOL);
    }'
```

Debe decir **87** caracteres la pública y **43** la privada: son las medidas de
un par P-256. Si sale 0, el `.env` quedó mal.

> **Ojo con el `.env`:** si la última línea no termina en salto de línea, el
> comando pega la llave detrás de ella y la rompe. Si ves algo como
> `ADMIN_PASSWORD=xVAPID_PUBLIC_KEY=...`, sepáralo a mano. Nos pasó en local.

> **Si falla con "Unable to create the key":** a PHP le falta el archivo
> `openssl.cnf`. En Linux no suele pasar; si pasa, se arregla con
> `OPENSSL_CONF=/etc/ssl/openssl.cnf php artisan webpush:vapid`.

---

## 5. El frontend se compila en local

**En el servidor no hay Node.** El build se hace en tu máquina y se sube ya
compilado.

```bash
# En tu máquina, dentro del proyecto
npm ci
npm run build
```

Eso genera `public/build/` (unos 1,1 MB) con `manifest.json` y los assets.
`public/build` está en `.gitignore` a propósito —no se versionan artefactos—,
así que **no viaja con `git push`** y hay que subirlo aparte:

```bash
# rsync es lo mejor: borra los assets viejos de builds anteriores
rsync -avz --delete public/build/ u123456789@florea.ec:domains/florea.ec/florea/public/build/
```

Si no tienes rsync (Windows sin WSL), con scp:

```bash
scp -r public/build u123456789@florea.ec:domains/florea.ec/florea/public/
```

> **Cuidado con scp:** no borra los archivos antiguos. Los assets llevan un
> hash en el nombre, así que la carpeta va creciendo con basura de builds
> anteriores. Cada cierto tiempo conviene borrarla en el servidor y subirla
> limpia.

**Regla práctica:** cada vez que cambie algo de `resources/js`, hay que
rehacer el build y volver a subir `public/build`. Si te olvidas, la web sigue
sirviendo la versión anterior de la interfaz.

> **Y hay algo peor que quedarse con la versión vieja:** la vista raíz carga un
> chunk distinto por cada pantalla, así que una pantalla **nueva** sin compilar
> devuelve un **error 500**, no una versión antigua. La suite tiene un test
> (`PwaTest`) que compara las páginas con el manifiesto y avisa antes de que
> eso llegue al servidor, pero solo si corres los tests.

---

## 6. Base de datos: migraciones y datos iniciales

```bash
cd $APP
php artisan migrate --force
```

`--force` es obligatorio en producción: sin él, Artisan pide confirmación
interactiva y en un cron o script se queda colgado.

Las migraciones cubren las tres semanas: catálogos, empresas, publicaciones,
ofertas, fotos, tratos, calificaciones, notificaciones, suscripciones push,
reputación, comentarios y la aceptación de términos.

### Los seeders de producción

El proyecto está hecho para que esto sea seguro:

```bash
php artisan db:seed --force
```

Eso siembra **solo** lo de producción:

| Seeder          | Qué crea                                                         |
| --------------- | ---------------------------------------------------------------- |
| `ZoneSeeder`    | Los sectores del cantón Pedro Moncayo                            |
| `VarietySeeder` | El catálogo de variedades de rosa                                |
| `AdminSeeder`   | Tu cuenta de administrador, desde `ADMIN_PHONE`/`ADMIN_PASSWORD` |

**El `DemoSeeder` no se ejecuta nunca en producción**, y está protegido dos
veces: `DatabaseSeeder` solo lo llama si `app()->isLocal()`, y `DemoSeeder`
vuelve a comprobarlo por dentro y se salta a sí mismo con un aviso. Con
`APP_ENV=production` no hay forma de que entre.

Si prefieres ir uno por uno:

```bash
php artisan db:seed --class=ZoneSeeder --force
php artisan db:seed --class=VarietySeeder --force
php artisan db:seed --class=AdminSeeder --force
```

`AdminSeeder` **lanza un error a propósito** si `ADMIN_PHONE` o
`ADMIN_PASSWORD` están vacíos, para que no te quedes sin poder entrar al
panel. Es idempotente (`updateOrCreate`): volver a correrlo actualiza la
contraseña del admin en vez de duplicar el usuario.

> **Red de seguridad:** `AppServiceProvider` llama a
> `DB::prohibitDestructiveCommands(app()->isProduction())`, así que
> `migrate:fresh`, `migrate:reset` y `db:wipe` **están bloqueados** en
> producción. Es a propósito: no hay ningún motivo para borrar la base del
> piloto.

---

## 7. Fotos: el enlace de `storage`

Las fotos de las ofertas se guardan en `storage/app/public/photos/AAAA/MM/` y
se sirven desde `public/storage`. Hay que crear el enlace una vez:

```bash
cd $APP
php artisan storage:link
```

Comprueba que quedó:

```bash
ls -la public/storage
# debe mostrar: public/storage -> /home/.../florea/storage/app/public
```

Si el servidor no permite enlaces simbólicos (raro, pero pasa), la
alternativa es montar la carpeta real:

```bash
rm -f public/storage
mkdir -p public/storage
# y en .env, servir desde ahí cambiando el disco 'public' de config/filesystems.php
```

**Sin este paso las miniaturas de las ofertas salen roto**, y es justo lo que
le da confianza al comprador. Es el error más fácil de cometer.

---

## 8. Cachés de producción

```bash
cd $APP
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

O de una vez:

```bash
php artisan optimize
```

Esto compila configuración, rutas y vistas en archivos únicos: en hosting
compartido es la diferencia más grande de rendimiento que vas a notar.

> **Importante:** después de `config:cache`, Laravel **ignora `.env`** y lee
> solo la caché. Si cambias cualquier variable del `.env`, hay que volver a
> correr `php artisan config:cache` o el cambio no tiene efecto. Es la causa
> número uno de "cambié la contraseña de la base y sigue fallando".

Para limpiar todo (al depurar):

```bash
php artisan optimize:clear
```

### Permisos

```bash
cd $APP
chmod -R 775 storage bootstrap/cache
```

Si aparece "failed to open stream: Permission denied" al escribir logs o
sesiones, es esto.

---

## 9. La línea de cron

Florea necesita **una sola línea de cron**, porque el hosting compartido no
permite dejar procesos corriendo. De esa línea cuelga todo lo demás.

hPanel → Avanzado → Trabajos cron (_Cron Jobs_). Cada minuto:

```
* * * * * cd /home/u123456789/domains/florea.ec/florea && php artisan schedule:run >> /dev/null 2>&1
```

Si el `php` del sistema no es 8.3+, pon la ruta completa:

```
* * * * * cd /home/u123456789/domains/florea.ec/florea && /opt/alt/php83/usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

De esa única línea salen tres tareas (definidas en `routes/console.php`):

| Tarea                                                  | Cada         | Para qué                                                                        |
| ------------------------------------------------------ | ------------ | ------------------------------------------------------------------------------- |
| `queue:work --stop-when-empty --tries=3 --max-time=50` | 1 min        | Procesa la cola (de ahí salen los avisos push) y **termina solo**               |
| `listings:expire`                                      | 5 min        | Vence publicaciones pasadas de plazo y rechaza sus ofertas pendientes (regla 7) |
| `push:digest`                                          | 5 min        | Agrupa en un aviso los pedidos que llegaron seguidos                            |
| `ratings:request`                                      | diario 09:00 | Pide calificar los tratos del día anterior                                      |
| `queue:prune-failed --hours=168`                       | semanal      | Que no se acumulen trabajos fallidos                                            |

### Cómo funciona la cola sin un proceso permanente

Esta es la parte que suele confundir en hosting compartido. Un `queue:work`
normal se queda corriendo para siempre, y Hostinger lo mata.

La solución es `--stop-when-empty`: el comando procesa lo que haya en la cola
y **se cierra solo** cuando no queda nada. El cron lo vuelve a lanzar al
minuto siguiente. Con `--max-time=50` se asegura de terminar antes de que
arranque la ejecución siguiente, y `withoutOverlapping()` en el scheduler
evita que se pisen dos.

En la práctica: **un aviso a productores tarda hasta un minuto en salir.**
Para este negocio es de sobra.

Por la cola pasan **todos los avisos push**. Las notificaciones de la campana
se escriben al instante (son una fila en la base); el push espera al siguiente
minuto de cron, porque hablar con los servidores de Google o Apple tarda
segundos y nadie debería esperar eso mientras oferta.

**Si el cron no está puesto, la campana funciona y el push no.** Es el fallo
más fácil de pasar por alto, porque la app parece estar bien.

### Comprobar que el cron funciona

```bash
cd $APP
php artisan schedule:list      # las tres tareas con su próxima ejecución
php artisan listings:expire    # a mano: dice cuántas venció
php artisan push:digest        # manda los resúmenes agrupados
php artisan ratings:request    # pide calificar los tratos de ayer
php artisan queue:work --stop-when-empty   # vacía la cola ahora
```

Si `schedule:list` falla con un error de base de datos, es que la conexión no
está bien: `withoutOverlapping()` usa la caché, que aquí es la base.

---

## 10. Comprobaciones antes de dar la dirección a nadie

```bash
cd $APP
php artisan about          # resumen de entorno, drivers y cachés
```

Revisa en ese resumen:

- `Environment ... production`
- `Debug Mode ... OFF`
- `Database ... mysql`
- `Config`, `Routes` y `Views` como `CACHED`
- Al final, en **Storage**, que la ruta de `public/storage` diga `LINKED`.
  Si dice `NOT LINKED`, falta el paso 7 y las fotos saldrán roto.

Y en el navegador:

- [ ] `https://florea.ec` carga y redirige a HTTPS
- [ ] `https://florea.ec/up` responde (health check de Laravel)
- [ ] Se puede registrar una empresa con un celular de Ecuador
- [ ] Entras al panel admin con `ADMIN_PHONE` / `ADMIN_PASSWORD`
- [ ] Verificas esa empresa desde el panel y ya puede publicar
- [ ] Publicas un "Busco" y aparece en el inicio
- [ ] Ofertas desde otra empresa **con una foto** y la miniatura **se ve**
      (si sale roto → falta `storage:link`, paso 7)
- [ ] Aceptas la oferta y aparece el celular y el botón de WhatsApp
- [ ] El enlace de WhatsApp abre con el mensaje ya escrito
- [ ] La campana muestra las notificaciones
- [ ] Calificas el trato y aparece la reputación de la otra empresa
- [ ] El menú de Chrome ofrece **"Instalar aplicación"**
- [ ] Instalada, la app abre sin barra de navegador
- [ ] Activas los avisos y llega un push de prueba (publica un "Busco" con una
      variedad del productor y mira si le vibra el celular)
- [ ] En modo avión, la app muestra la pantalla **"Sin conexión"**
- [ ] `/admin/metrics` carga aunque no haya datos
- [ ] Todo lo anterior **desde un celular de verdad**, con datos móviles

Lo último no es un detalle: la app se usa a 375 px, en un invernadero, con
mala señal.

### Que nada privado quede expuesto

```bash
curl -I https://florea.ec/.env          # debe dar 403 o 404, NUNCA 200
curl -I https://florea.ec/storage/logs/laravel.log
curl -I https://florea.ec/composer.json
```

Si alguno responde `200`, la raíz del sitio está mal puesta: vuelve al paso 1.

---

## 11. Despliegues siguientes

Con la estructura ya montada, un despliegue son **dos comandos**: uno en tu
máquina y otro en el servidor.

```bash
# 1. En tu máquina: la suite sobre MySQL, como producción
composer test:mysql

# 2. Compilar y subir el frontend
npm run build
rsync -avz --delete public/build/ u123456789@florea.ec:domains/florea.ec/florea/public/build/

# 3. En el servidor
ssh u123456789@florea.ec
cd ~/domains/florea.ec/florea && ./deploy.sh
```

> **Por qué el paso 1.** El día a día se prueba sobre SQLite porque tarda 14
> segundos en vez de 43, pero SQLite no se queja de lo que MySQL rechaza: los
> largos de las columnas, los índices únicos demasiado largos,
> `ONLY_FULL_GROUP_BY` y el modo estricto. Una migración que pasa en SQLite y
> revienta en MySQL te deja la app a medio migrar, y eso se arregla a mano.

El `deploy.sh` del repositorio pone la app en mantenimiento, baja el código,
instala dependencias, migra, rehace el enlace de storage, recachea y te
enseña `php artisan about` para que compruebes que quedó bien. Si algo falla
a mitad, **vuelve a levantar la app igual** en vez de dejarla caída.

La primera vez hay que darle permiso de ejecución:

```bash
chmod +x deploy.sh
```

Y si el `php` del sistema no es 8.3+, pásale la ruta:

```bash
PHP=/opt/alt/php83/usr/bin/php ./deploy.sh
```

> **El orden importa.** Primero el frontend, después el backend. Si subes el
> código nuevo sin el `public/build` nuevo, las pantallas que se añadieron en
> ese cambio devuelven un 500 hasta que llegue el `rsync`.

---

## 12. Problemas típicos

| Síntoma                                        | Causa y arreglo                                                                                                                                                                                                                                   |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Página en blanco o error 500                   | Mira `storage/logs/laravel.log`. Lo más común: permisos (paso 8) o `APP_KEY` sin generar                                                                                                                                                          |
| "Vite manifest not found"                      | Falta `public/build` en el servidor: paso 5                                                                                                                                                                                                       |
| Las miniaturas de las fotos salen roto         | Falta `php artisan storage:link` (paso 7), o `APP_URL` mal puesta                                                                                                                                                                                 |
| Cambié `.env` y no pasa nada                   | Falta `php artisan config:cache` (paso 8)                                                                                                                                                                                                         |
| Los avisos a productores no llegan             | El cron no está puesto o apunta a un PHP viejo (paso 9). Pruébalo a mano con `queue:work --stop-when-empty`                                                                                                                                       |
| Las publicaciones no vencen                    | Lo mismo: el cron. Compruébalo con `php artisan listings:expire`                                                                                                                                                                                  |
| Los enlaces salen en `http://` y no `https://` | `APP_URL` con `https`, y si sigue, el proxy de Hostinger: configura `trustProxies` en `bootstrap/app.php`                                                                                                                                         |
| "could not find driver"                        | Falta la extensión `pdo_mysql` en la versión de PHP elegida: hPanel → Configuración de PHP → Extensiones                                                                                                                                          |
| Las fotos no se suben                          | Falta la extensión `gd` (la usa `intervention/image`). Mírala en el mismo sitio                                                                                                                                                                   |
| `413` al subir una foto                        | `upload_max_filesize` y `post_max_size` por debajo de 12 MB: hPanel → Configuración de PHP                                                                                                                                                        |
| Chrome no ofrece "Instalar aplicación"         | Sin HTTPS no hay instalación. Comprueba también que `/manifest.webmanifest` y `/sw.js` responden 200                                                                                                                                              |
| Instalé la app y sigue mostrando lo viejo      | El service worker guarda la versión anterior. Cierra la app, vuelve a abrirla, y si sigue: Chrome → Configuración del sitio → Borrar datos                                                                                                        |
| La campana avisa pero el celular no vibra      | Push sin cron: el aviso está en la cola esperando. Es el fallo más común (paso 9)                                                                                                                                                                 |
| "No se pudieron activar los avisos"            | Falta `bcmath`, o las llaves VAPID están vacías o mal copiadas (paso 4.1)                                                                                                                                                                         |
| Activé los avisos y dejaron de llegar          | Cambiaron las llaves VAPID. Las suscripciones viejas ya no valen: cada quien tiene que volver a activarlos                                                                                                                                        |
| Una pantalla nueva da 500 y las demás van      | Falta su chunk en `public/build`: subiste el código sin el frontend (paso 5)                                                                                                                                                                      |
| `/admin/metrics` sale en blanco                | Normal si todavía no hay tratos. Con datos, mira el log                                                                                                                                                                                           |
| Una migración falla con `Duplicate entry`      | Añade un índice único sobre datos que ya tienen repetidos. Límpialos primero con un `SELECT ... GROUP BY ... HAVING COUNT(*) > 1`. Esto **no** se ve en una base vacía: pruébalo sobre una copia de producción, no solo con `composer test:mysql` |

### Extensiones de PHP que hacen falta

En hPanel → Avanzado → Configuración de PHP → Extensiones, comprueba:

`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`,
`fileinfo`, **`gd`**, **`exif`** y **`bcmath`**.

Las tres últimas no son opcionales en Florea:

- Sin **`gd`** no se puede ofertar en un "Busco", porque la foto es obligatoria.
- Sin **`exif`**, las fotos tomadas en vertical se guardan de lado.
- Sin **`bcmath`** (o `gmp`) no funciona el cifrado de los avisos push.

---

## 13. Copias de seguridad

Hostinger hace copias automáticas, pero antes de cada despliegue con
migraciones conviene tener la tuya:

```bash
cd ~
mysqldump -u u123456789_florea -p u123456789_florea > florea-$(date +%F).sql
```

Y guarda también las fotos, que no están en la base:

```bash
tar czf florea-fotos-$(date +%F).tar.gz -C $APP/storage/app/public photos
```

Bájate los dos archivos a tu máquina. Las fotos no se pueden recuperar de
ninguna otra parte.

### Copias automáticas de Hostinger

hPanel → Archivos → Copias de seguridad. Activa las diarias y comprueba que
cubren **la base de datos y los archivos**: las fotos de las ofertas viven en
`storage/app/public/photos`, que es disco, no base.

### Cómo restaurar

**Desde el panel:** hPanel → Copias de seguridad → elige la fecha → Restaurar.
Es lo más rápido y lo que vas a querer casi siempre.

**A mano, desde tus copias:**

```bash
cd $APP
php artisan down --render="errors::503"

mysql -u u123456789_florea -p u123456789_florea < florea-2026-11-10.sql
tar xzf florea-fotos-2026-11-10.tar.gz -C $APP/storage/app/public

php artisan optimize
php artisan up
```

Después de restaurar, comprueba que `php artisan about` sigue diciendo
`LINKED` en Storage: si se restauró la carpeta por encima, el enlace puede
haberse perdido y hay que rehacer `php artisan storage:link`.

> **Pruébalo antes del piloto.** Una copia que nunca se ha restaurado no es una
> copia, es una esperanza.
