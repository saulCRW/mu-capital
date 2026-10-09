#!/bin/sh
set -e
cd /app

# Esquema de la base y planes (idempotentes: corren en cada arranque).
php artisan migrate --force --no-interaction
php artisan db:seed --class=PlanSeeder --force --no-interaction

# Superadmin inicial: solo si existen SUPERADMIN_EMAIL y SUPERADMIN_PASSWORD y el correo aún no está registrado.
if [ -n "$SUPERADMIN_EMAIL" ] && [ -n "$SUPERADMIN_PASSWORD" ]; then
    php artisan tinker --execute 'if (! \App\Modules\Users\Models\User::where("email", getenv("SUPERADMIN_EMAIL"))->exists()) { \App\Modules\Users\Models\User::create(["nombre" => "Superadmin MU Intelligence", "email" => getenv("SUPERADMIN_EMAIL"), "password" => getenv("SUPERADMIN_PASSWORD"), "rol" => \App\Modules\Users\Models\User::ROL_SUPERADMIN]); echo "Superadmin creado"; }'
fi

php artisan config:cache
php artisan view:cache
php artisan route:cache || true

# Programador de Laravel (mercado cada 15 min, cola de correos cada minuto) junto al servidor web.
(while true; do php artisan schedule:work; sleep 5; done) &

exec frankenphp run --config /etc/frankenphp/Caddyfile
