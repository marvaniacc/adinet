<?php

namespace App\Console\Commands;

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FileReport extends Command
{
    protected $signature = 'adinet:file-report
        {--title= : Report title (required)}
        {--type=development : One of: audit, development, bug_fix, deployment, security, other}
        {--task= : Task description / summary body}
        {--files= : Comma-separated list of changed files}
        {--tests= : Test result summary}
        {--github=not-pushed : pushed | not-pushed}';

    protected $description = 'File a private admin report (.txt) documenting a completed development task';

    public function handle(): int
    {
        $title = trim((string) $this->option('title'));

        if ($title === '') {
            $this->error('--title is required.');

            return self::FAILURE;
        }

        try {
            $type = ReportType::from((string) $this->option('type'));
        } catch (\ValueError) {
            $this->error('Invalid --type. Allowed: '.implode(', ', array_column(ReportType::cases(), 'value')));

            return self::FAILURE;
        }

        [$branch, $commit, $dirty] = $this->gitMetadata();

        // Latin titles become slugs; non-latin (Persian) titles fall back
        // to a timestamp name - either way the stored file always ends in .txt.
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '');
        $baseName = (preg_match('/^[a-z0-9\-]{3,}$/', trim($slug, '-')) ? trim($slug, '-') : now()->format('Ymd-His').'-report');
        $fileName = $baseName.'.txt';
        $path = 'reports/'.now()->format('Y/m').'/'.$fileName;

        Storage::disk('local')->put($path, $this->composeContent($type, $title, $branch, $commit, $dirty));

        Report::create([
            'title' => $title,
            'type' => $type,
            'description' => $this->trimOrNull((string) $this->option('task')),
            'file_path' => $path,
            'file_name' => $fileName,
        ]);

        $this->components->info("Report filed: {$title}");
        $this->line("  file: {$path}");

        return self::SUCCESS;
    }

    private function composeContent(ReportType $type, string $title, string $branch, string $commit, bool $dirty): string
    {
        return implode("\n", [
            'Task: '.($this->opt('task') ?? '(see title)'),
            'Generated: '.now()->toDateString(),
            'Repository: marvaniacc/adinet',
            'Branch: '.$branch,
            'Commit: '.$commit,
            'Files changed: '.($this->opt('files') ?? '-'),
            'Tests: '.($this->opt('tests') ?? 'not recorded'),
            'GitHub: '.(string) $this->option('github'),
            'Working tree: '.($dirty ? 'dirty' : 'clean'),
            '',
            'Type: '.$type->label(),
            'Title: '.$title,
        ])."\n";
    }

    private function opt(string $key): ?string
    {
        return $this->trimOrNull((string) $this->option($key));
    }

    /** @return array{0: string, 1: string, 2: bool} branch, commit hash, dirty flag */
    private function gitMetadata(): array
    {
        $run = fn (string $cmd) => trim((string) shell_exec('cd '.escapeshellarg(base_path())." && {$cmd} 2>/dev/null"));

        return [
            $run('git rev-parse --abbrev-ref HEAD') ?: 'unknown',
            $run('git rev-parse --short HEAD') ?: 'unknown',
            $run('git status --porcelain') !== '',
        ];
    }

    private function trimOrNull(string $value, ?string $default = null): ?string
    {
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : $default;
    }
}
