# Manual de instalación

## Requisitos

- Ubuntu 22.04 o superior recomendado.
- PHP 8.5 CLI/FPM, Composer 2, Node.js 20.19+ o 22.12+, npm y MySQL 8.
- Extensiones PHP: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`, `gd` y `bcmath`.

Verifique:

```bash
php -v
php -m
composer --version
node --version
mysql --version
```

## Instalación

```bash
git clone <repositorio> sis_ferreteria
cd sis_ferreteria
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Cree una base de datos y un usuario MySQL. Configure `.env`:

```dotenv
APP_NAME="Construir a tu Alcance"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.ejemplo.local
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sis_ferreteria
DB_USERNAME=sis_ferreteria
DB_PASSWORD=una-contraseña-segura
```

Nunca publique `.env` ni sus credenciales.

## Base de datos y assets

```bash
sudo service mysql start
php artisan migrate:fresh --seed --force
php artisan storage:link
npm run build
```

`migrate:fresh` elimina tablas existentes: úselo solo en instalaciones nuevas o pruebas. En producción con datos use `php artisan migrate --force`.

Para desarrollo:

```bash
php artisan serve
npm run dev
```

Para producción:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Scheduler

`credits:mark-overdue` marca diariamente créditos vencidos con saldo. Configure:

```cron
* * * * * cd /ruta/sis_ferreteria && php artisan schedule:run >> /dev/null 2>&1
```

## Verificación

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
php artisan test
npm run build
```

La cuenta demo es `admin@construir.local` / `password`; desactívela o cambie la contraseña antes de producción.
