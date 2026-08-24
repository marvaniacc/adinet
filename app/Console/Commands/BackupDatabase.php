<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'adinet:backup {--keep=7 : Number of backup file pairs to retain}';

    protected $description = 'Dump the PostgreSQL database and private storage to a timestamped backup directory';

    public function handle(): int
    {
        $db = config('database.connections.pgsql');
        $backupDir = storage_path('app/backups');
        $timestamp = now()->format('Y-m-d_His');

        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        // 1) Database dump — run as postgres system user (peer auth),
        //    dump to /tmp first then move to backups dir.
        $dumpFile = "{$backupDir}/db-{$timestamp}.sql";
        $tmpDump = sys_get_temp_dir().'/'.basename($dumpFile);
        $dbName = escapeshellarg($db['database'] ?? 'adinet');

        shell_exec('sudo -u postgres pg_dump -Fc -f '.escapeshellarg($tmpDump)." {$dbName} 2>&1");

        if (file_exists($tmpDump)) {
            rename($tmpDump, $dumpFile);
        }

        if (! file_exists($dumpFile) || filesize($dumpFile) === 0) {
            $this->error('DB dump failed: '.($result ?: 'empty file'));

            return self::FAILURE;
        }

        // Compress the dump
        Process::run(['gzip', $dumpFile]);
        $dumpFile .= '.gz';

        if (! file_exists($dumpFile)) {
            $this->error('Dump file not found after gzip.');

            return self::FAILURE;
        }

        // 2) Private storage tar
        $storageDir = storage_path('app/private');
        $tarFile = "{$backupDir}/private-{$timestamp}.tar.gz";

        if (is_dir($storageDir)) {
            shell_exec('tar -czf '.escapeshellarg($tarFile).' -C '.escapeshellarg(dirname($storageDir)).' '.escapeshellarg(basename($storageDir)));
        }

        // 3) Prune old backups (keep N most-recent pairs)
        $keep = max(1, (int) $this->option('keep'));
        $files = array_merge(
            glob("{$backupDir}/db-*.sql.gz") ?: [],
            glob("{$backupDir}/private-*.tar.gz") ?: []
        );

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $keep * 2) as $old) {
            unlink($old);
        }

        $size = number_format(filesize($dumpFile) / 1024).' KB';
        $this->info("Backup completed: {$dumpFile} ({$size})");

        return self::SUCCESS;
    }
}
