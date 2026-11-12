#!/usr/bin/env bash
#
# Actualiza Florea en Hostinger.
#
#   cd ~/domains/florea.lat/florea && ./deploy.sh
#
# OJO: el frontend NO se compila aquí (en el servidor no hay Node). Antes de
# correr esto, desde tu máquina:
#
#   npm run build
#   scp -r public/build/* USUARIO@florea.lat:domains/florea.lat/florea/public/build/
#
# (En Windows no hay rsync, así que va con scp. Los archivos viejos se quedan
# ahí, pero no molestan: cada build lleva su hash en el nombre y el que manda
# es manifest.json, que sí se sobrescribe.)
#
# Si te saltas ese paso, la web sigue sirviendo la interfaz anterior, y una
# pantalla nueva da error 500 por falta de su chunk en el manifiesto.

set -euo pipefail

cd "$(dirname "$0")"

# El PHP con el que corre todo.
#
# En Hostinger el `php` del PATH es 8.2, pero Florea necesita 8.4: las
# versiones de Symfony con los parches de seguridad de noviembre exigen
# >=8.4.1. Por eso se busca el 8.4 primero y solo se cae al del sistema si
# no está. Se puede forzar con: PHP=/ruta/al/php ./deploy.sh
if [ -z "${PHP:-}" ]; then
    for candidate in /opt/alt/php84/usr/bin/php /opt/alt/php83/usr/bin/php php; do
        if command -v "$candidate" > /dev/null 2>&1; then
            PHP="$candidate"
            break
        fi
    done
fi

echo "==> Versión de PHP"
"$PHP" -v | head -1

# Si no llega a 8.3 no seguimos: composer install fallaría a mitad y el
# despliegue quedaría con el código nuevo y las dependencias viejas.
"$PHP" -r 'exit(version_compare(PHP_VERSION, "8.3", ">=") ? 0 : 1);' || {
    echo "ERROR: Florea necesita PHP 8.3+ y este es $("$PHP" -r 'echo PHP_VERSION;')."
    echo "Prueba:  PHP=/opt/alt/php84/usr/bin/php ./deploy.sh"
    exit 1
}

echo "==> Modo mantenimiento"
"$PHP" artisan down --render="errors::503" --retry=60 || true

# Pase lo que pase, la app vuelve a levantarse. Sin esto, un fallo a mitad
# deja el sitio caído hasta que alguien entre por SSH.
trap '"$PHP" artisan up || true' EXIT

echo "==> Código"
git pull --ff-only origin main

echo "==> Dependencias de PHP"
# Con "$PHP" por delante: si no, composer usa el php del PATH (8.2 aquí) y
# se niega a instalar, dejando el código nuevo con el vendor viejo.
"$PHP" "$(command -v composer || echo composer.phar)" \
    install --no-dev --optimize-autoloader --no-interaction

echo "==> Base de datos"
"$PHP" artisan migrate --force

echo "==> Enlace de storage (por si aún no existe)"
"$PHP" artisan storage:link || true

# scp desde Windows crea las carpetas nuevas en modo 700, porque Windows no
# tiene permisos de Unix y el cliente se pone en lo más restrictivo. El
# servidor web no puede entrar y devuelve 404 en todos los assets: la app
# carga el HTML y se queda en blanco. Esto lo corrige en cada despliegue.
echo "==> Permisos de public/build"
if [ -d public/build ]; then
    find public/build -type d -exec chmod 755 {} +
    find public/build -type f -exec chmod 644 {} +
fi

echo "==> Cachés"
"$PHP" artisan optimize

echo "==> Comprobación"
"$PHP" artisan about --only=environment,cache,drivers

echo
echo "Listo. Si cambió el frontend, recuerda haber subido public/build."
