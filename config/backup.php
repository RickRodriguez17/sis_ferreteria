<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'path' => env('BACKUP_PATH', 'backups'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'binary' => env('MYSQLDUMP_BINARY', 'mysqldump'),
];
