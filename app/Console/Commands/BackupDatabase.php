<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--retention= : Días de retención; por defecto usa BACKUP_RETENTION_DAYS}';

    protected $description = 'Crea un respaldo de la base de datos MySQL y elimina respaldos antiguos';

    public function handle(): int
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}");

        if (! in_array($database['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            $this->error('El respaldo de base de datos requiere una conexión MySQL o MariaDB.');

            return self::FAILURE;
        }

        $name = $database['database'] ?? null;
        if (! is_string($name) || $name === '') {
            $this->error('La base de datos no está configurada.');

            return self::FAILURE;
        }

        $diskName = (string) config('backup.disk', 'local');
        $disk = Storage::disk($diskName);
        $path = trim((string) config('backup.path', 'backups'), '/');
        $filename = $path.'/database-'.now()->format('Ymd-His').'.sql';
        $password = $database['password'] ?? null;
        $environment = array_merge($_ENV, $_SERVER, $password !== null && $password !== '' ? ['MYSQL_PWD' => (string) $password] : []);
        $arguments = [
            (string) config('backup.binary', 'mysqldump'),
            '--host='.(string) ($database['host'] ?? '127.0.0.1'),
            '--port='.(string) ($database['port'] ?? 3306),
            '--user='.(string) ($database['username'] ?? ''),
            '--single-transaction',
            '--quick',
            '--routines',
            '--events',
            $name,
        ];

        $process = new Process($arguments, base_path(), $environment);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('No se pudo crear el respaldo: '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        if (! $disk->put($filename, $process->getOutput())) {
            $this->error('No se pudo guardar el respaldo en el disco configurado.');

            return self::FAILURE;
        }

        $this->removeExpiredBackups($disk, $path);
        $this->info("Respaldo creado: {$filename}");

        return self::SUCCESS;
    }

    private function removeExpiredBackups($disk, string $path): void
    {
        $retention = max(1, (int) ($this->option('retention') ?: config('backup.retention_days', 14)));
        $cutoff = now()->subDays($retention)->timestamp;

        foreach ($disk->files($path) as $file) {
            if (! str_ends_with($file, '.sql') || $disk->lastModified($file) >= $cutoff) {
                continue;
            }

            $disk->delete($file);
        }
    }
}
