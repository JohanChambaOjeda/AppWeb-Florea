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

---

## 6. Base de datos: migraciones y datos iniciales

```bash
cd $APP
php artisan migrate --force
```

`--force` es obligatorio en producción: sin él, Artisan pide confirmación
interactiva y en un cron o script se queda colgado.

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

| Tarea                                                  | Cada    | Para qué                                                                        |
| ------------------------------------------------------ | ------- | ------------------------------------------------------------------------------- |
| `listings:expire`                                      | 5 min   | Vence publicaciones pasadas de plazo y rechaza sus ofertas pendientes (regla 7) |
| `queue:work --stop-when-empty --tries=3 --max-time=50` | 1 min   | Procesa la cola y **termina solo**                                              |
| `queue:prune-failed --hours=168`                       | semanal | Que no se acumulen trabajos fallidos                                            |

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

Lo que pasa por la cola hoy es el aviso a los productores cuando se publica
un "Busco" que coincide con sus variedades. Las demás notificaciones (oferta
recibida, aceptada, rechazada) se escriben al instante, sin cola.

### Comprobar que el cron funciona

```bash
cd $APP
php artisan schedule:list      # las tres tareas con su próxima ejecución
php artisan listings:expire    # a mano: dice cuántas venció
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

Con la estructura ya montada, actualizar son estos pasos:

```bash
# 1. En tu máquina: compilar y subir el frontend
npm run build
rsync -avz --delete public/build/ u123456789@florea.ec:domains/florea.ec/florea/public/build/

# 2. En el servidor
ssh u123456789@florea.ec
cd ~/domains/florea.ec/florea

php artisan down --render="errors::503"     # opcional, si hay migraciones
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize                        # config + rutas + vistas
php artisan up
```

Conviene dejarlo en un script, `$APP/deploy.sh`:

```bash
#!/usr/bin/env bash
set -e   # para en el primer error, para no dejar el sitio a medias

cd "$(dirname "$0")"

php artisan down --render="errors::503" || true
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up

echo "Listo. Recuerda subir public/build si cambió el frontend."
```

```bash
chmod +x deploy.sh
./deploy.sh
```

---

## 12. Problemas típicos

| Síntoma                                        | Causa y arreglo                                                                                             |
| ---------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| Página en blanco o error 500                   | Mira `storage/logs/laravel.log`. Lo más común: permisos (paso 8) o `APP_KEY` sin generar                    |
| "Vite manifest not found"                      | Falta `public/build` en el servidor: paso 5                                                                 |
| Las miniaturas de las fotos salen roto         | Falta `php artisan storage:link` (paso 7), o `APP_URL` mal puesta                                           |
| Cambié `.env` y no pasa nada                   | Falta `php artisan config:cache` (paso 8)                                                                   |
| Los avisos a productores no llegan             | El cron no está puesto o apunta a un PHP viejo (paso 9). Pruébalo a mano con `queue:work --stop-when-empty` |
| Las publicaciones no vencen                    | Lo mismo: el cron. Compruébalo con `php artisan listings:expire`                                            |
| Los enlaces salen en `http://` y no `https://` | `APP_URL` con `https`, y si sigue, el proxy de Hostinger: configura `trustProxies` en `bootstrap/app.php`   |
| "could not find driver"                        | Falta la extensión `pdo_mysql` en la versión de PHP elegida: hPanel → Configuración de PHP → Extensiones    |
| Las fotos no se suben                          | Falta la extensión `gd` (la usa `intervention/image`). Mírala en el mismo sitio                             |
| `413` al subir una foto                        | `upload_max_filesize` y `post_max_size` por debajo de 12 MB: hPanel → Configuración de PHP                  |

### Extensiones de PHP que hacen falta

En hPanel → Avanzado → Configuración de PHP → Extensiones, comprueba:

`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`,
`bcmath`, `fileinfo`, **`gd`** (fotos) y `exif` (orientación de las fotos del
celular).

Sin `gd` no se puede ofertar en un "Busco", porque la foto es obligatoria.
Sin `exif`, las fotos tomadas en vertical se guardan de lado.

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
