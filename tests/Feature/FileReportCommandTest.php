<?php

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    // Tests run inside the repo, so git metadata resolves for real.
});

it('files a report with the §51 format and stores it privately', function () {
    $this->artisan('adinet:file-report', [
        '--title' => 'Implement reports feature',
        '--type' => 'development',
        '--task' => 'Built private admin report archive',
        '--files' => 'app/Models/Report.php, app/Enums/ReportType.php',
        '--tests' => '125 passed',
        '--github' => 'pushed',
    ])->assertSuccessful();

    $report = Report::query()->sole();

    expect($report->type)->toBe(ReportType::Development)
        ->and(str_starts_with($report->file_path, 'reports/'))->toBeTrue()
        ->and(str_ends_with($report->file_name, '.txt'))->toBeTrue();

    // File lives on the private disk, never under public/.
    $storedPath = Storage::disk('local')->path($report->file_path);
    expect(str_contains($storedPath, public_path()))->toBeFalse();

    $content = Storage::disk('local')->get($report->file_path);
    foreach ([
        'Task: Built private admin report archive',
        'Generated: '.now()->toDateString(),
        'Repository: marvaniacc/adinet',
        'Branch: ',
        'Commit: ',
        'Files changed: app/Models/Report.php',
        'Tests: 125 passed',
        'GitHub: pushed',
    ] as $needle) {
        expect(str_contains($content, $needle))->toBeTrue("missing: {$needle}");
    }
});

it('falls back to a timestamp file name for persian titles', function () {
    $this->artisan('adinet:file-report', [
        '--title' => 'گزارش فارسی آزمون',
        '--type' => 'other',
    ])->assertSuccessful();

    $report = Report::query()->sole();

    expect(preg_match('/^\d{8}-\d{6}-report\.txt$/', $report->file_name))->toBe(1)
        ->and(Storage::disk('local')->exists($report->file_path))->toBeTrue();
});

it('rejects invalid types', function () {
    $this->artisan('adinet:file-report', ['--title' => 'x', '--type' => 'nonsense'])
        ->assertFailed();

    expect(Report::count())->toBe(0);
});
