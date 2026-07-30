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
php artisan optimize
```

## Contraseñas y autenticación

Las contraseñas deben tener como mínimo 8 caracteres e incluir mayúsculas,
minúsculas, números y símbolos. La misma regla se aplica al alta de usuarios,
registro, restablecimiento y actualización del perfil. El entorno no activa la
verificación de contraseñas comprometidas porque requiere consultar un servicio
externo; la validación actual funciona completamente sin red.

El login tiene un límite de cinco intentos por correo e IP. El envío y
restablecimiento de contraseña tienen un límite adicional de seis solicitudes
por minuto.

## Colas y worker

La configuración recomendada de producción usa `QUEUE_CONNECTION=database`.
Las tablas `jobs`, `job_batches` y `failed_jobs` forman parte de las migraciones
estándar del proyecto. Ejecute:

```bash
php artisan migrate --force
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```

Mantenga el worker activo con Supervisor. Ejemplo en
`/etc/supervisor/conf.d/sis-ferreteria-worker.conf`:

```ini
[program:sis-ferreteria-worker]
command=php /ruta/sis_ferreteria/artisan queue:work database --sleep=3 --tries=3 --timeout=90
directory=/ruta/sis_ferreteria
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/sis-ferreteria-worker.log
```

Después de crear el archivo:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart sis-ferreteria-worker
```

## Scheduler

`credits:mark-overdue` marca diariamente créditos vencidos con saldo y
`backup:database` crea el respaldo diario a las 02:00. Configure el cron:

```cron
* * * * * cd /ruta/sis_ferreteria && php artisan schedule:run >> /dev/null 2>&1
```

## Backups y restauración

Configure `BACKUP_DISK`, `BACKUP_PATH`, `BACKUP_RETENTION_DAYS` y
`MYSQLDUMP_BINARY` en `.env`. El comando usa las credenciales de
`config/database.php`, guarda archivos SQL en `storage/app/private/backups` por
defecto y elimina respaldos con más de 14 días:

```bash
php artisan backup:database
php artisan backup:database --retention=30
```

Pruebe que `mysqldump` esté instalado y disponible para el usuario que ejecuta
el worker/scheduler. Para restaurar un respaldo, detenga temporalmente los
workers, cree una base de datos vacía y ejecute:

```bash
mysql -h 127.0.0.1 -u usuario -p sis_ferreteria < storage/app/private/backups/database-AAAAMMDD-HHMMSS.sql
```

No almacene los archivos de respaldo en el repositorio ni exponga el disco
privado por HTTP.

## Checklist de producción

- `APP_ENV=production` y `APP_DEBUG=false`.
- `APP_URL` usa HTTPS y `SESSION_SECURE_COOKIE=true`.
- Credenciales de base de datos y correo fuera del repositorio.
- `php artisan config:cache`, `route:cache`, `view:cache` y `optimize`.
- `sudo chown -R www-data:www-data storage bootstrap/cache`.
- `sudo chmod -R ug+rwX storage bootstrap/cache`.
- `php artisan storage:link` si se usan archivos públicos.
- Cron de scheduler y worker Supervisor activos.
- HTTPS terminado en el servidor web y proxies configurados.
- Backups probados y restauración documentada.

## Verificación

```bash
vendor/bin/pint
vendor/bin/phpstan analyse
php artisan test
npm run build
```

La cuenta demo es `admin@construir.local` / `password`; desactívela o cambie la contraseña antes de producción.
