<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'adinet:backup {--keep=7 : Number of backup files to retain}';

    protected $description = 'Dump the PostgreSQL database and private storage to a timestamped backup directory';

    public function handle(): int
    {
        $db = config('database.connections.pgsql');
        $backupDir = storage_path('app/backups');
        $timestamp = now()->format('Y-m-d_His');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        // 1) Database dump
        $dumpFile = "{$backupDir}/db-{$timestamp}.sql.gz";
        $env = array_merge($_ENV, $_SERVER, [
            'PGPASSWORD' => $db['password'] ?? '',
        ]);

        $process = Process::run(array_filter([
            'pg_dump',
            '-h', $db['host'] ?? '127.0.0.1',
            '-p', (string) ($db['port'] ?? 5432),
            '-U', $db['username'] ?? 'postgres',
            '-Fc',
            '-f', $dumpFile,
            $db['database'] ?? 'adinet',
        ]), fn () => $env);

        if ($process->failed()) {
            $this->error('DB dump failed: '.$process->errorOutput());

            return self::FAILURE;
        }

        // 2) Private storage tar
        $storageDir = storage_path('app/private');
        $tarFile = "{$backupDir}/private-{$timestamp}.tar.gz";

        if (is_dir($storageDir)) {
            Process::run(['tar', '-czf', $tarFile, '-C', dirname($storageDir), basename($storageDir)]);
        }

        // 3) Prune old backups
        $keep = max(1, (int) $this->option('keep'));
        $files = array_merge(
            glob("{$backupDir}/db-*.sql.gz") ?: [],
            glob("{$backupDir}/private-*.tar.gz") ?: []
        );

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $keep * 2) as $old) {
            unlink($old);
        }

        $this->info('Backup completed: '.count(glob("{$backupDir}/*")).' files in '.$backupDir);

        return self::SUCCESS;
    }
}
