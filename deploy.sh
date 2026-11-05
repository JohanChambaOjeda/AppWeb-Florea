#!/usr/bin/env bash
#
# Actualiza Florea en Hostinger.
#
#   cd ~/domains/floreaec.com/florea && ./deploy.sh
#
# OJO: el frontend NO se compila aquí (en el servidor no hay Node). Antes de
# correr esto, desde tu máquina:
#
#   npm run build
#   scp -r public/build/* USUARIO@floreaec.com:domains/floreaec.com/florea/public/build/
#
# (En Windows no hay rsync, así que va con scp. Los archivos viejos se quedan
# ahí, pero no molestan: cada build lleva su hash en el nombre y el que manda
# es manifest.json, que sí se sobrescribe.)
#
# Si te saltas ese paso, la web sigue sirviendo la interfaz anterior, y una
# pantalla nueva da error 500 por falta de su chunk en el manifiesto.

set -euo pipefail

cd "$(dirname "$0")"

# Si el php del sistema no es 8.3+, pon aquí la ruta completa.
# Por ejemplo: PHP=/opt/alt/php83/usr/bin/php
PHP="${PHP:-php}"

echo "==> Versión de PHP"
"$PHP" -v | head -1

echo "==> Modo mantenimiento"
"$PHP" artisan down --render="errors::503" --retry=60 || true

# Pase lo que pase, la app vuelve a levantarse. Sin esto, un fallo a mitad
# deja el sitio caído hasta que alguien entre por SSH.
trap '"$PHP" artisan up || true' EXIT

echo "==> Código"
git pull --ff-only origin main

echo "==> Dependencias de PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Base de datos"
"$PHP" artisan migrate --force

echo "==> Enlace de storage (por si aún no existe)"
"$PHP" artisan storage:link || true

echo "==> Cachés"
"$PHP" artisan optimize

echo "==> Comprobación"
"$PHP" artisan about --only=environment,cache,drivers

echo
echo "Listo. Si cambió el frontend, recuerda haber subido public/build."
