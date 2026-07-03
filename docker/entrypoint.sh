#!/usr/bin/env sh
set -e

cd /var/www/html

# Create .env if it doesn't exist (the built image ignores the local .env)
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Helper: set or append a KEY=VALUE line in .env (value used literally)
set_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env; then
        # Use | as delimiter; base64 keys never contain it
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

# Application key: use the provided one, otherwise generate a fresh one
if [ -n "$APP_KEY" ]; then
    set_env APP_KEY "$APP_KEY"
else
    php artisan key:generate --force
fi

# Database connection (point Laravel at the mysql service)
set_env DB_CONNECTION "${DB_CONNECTION:-mysql}"
set_env DB_HOST "${DB_HOST:-mysql}"
set_env DB_PORT "${DB_PORT:-3306}"
set_env DB_DATABASE "${DB_DATABASE:-smart_security}"
set_env DB_USERNAME "${DB_USERNAME:-smart}"
set_env DB_PASSWORD "${DB_PASSWORD:-secret}"

# Make sure no stale cached config overrides the fresh .env
php artisan config:clear >/dev/null 2>&1 || true

# Run migrations, retrying until the database is reachable
echo "Running migrations..."
n=0
until php artisan migrate --force; do
    n=$((n + 1))
    if [ "$n" -ge 15 ]; then
        echo "Database not ready after $n attempts, continuing anyway..."
        break
    fi
    echo "Waiting for database... attempt $n"
    sleep 3
done

php artisan storage:link 2>/dev/null || true

echo "Starting Laravel on http://0.0.0.0:8000"
exec php artisan serve --host=0.0.0.0 --port=8000
