<?php

use App\Enums\ReportType;
use App\Livewire\Dashboard\Admin\ReportIndex;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->admin()->create();
});

function makeReport(array $attributes = []): Report
{
    Storage::disk('local')->put($path = 'reports/test/'.($attributes['file_name'] ?? 'r.txt'), 'report body');

    $report = Report::create([
        ...$attributes,
        'title' => $attributes['title'] ?? 'گزارش آزمون',
        'type' => $attributes['type'] ?? ReportType::Development,
        'file_path' => $path,
        'file_name' => $attributes['file_name'] ?? 'r.txt',
    ]);

    // created_at isn't mass-assignable; set explicitly for ordering tests.
    if (isset($attributes['created_at'])) {
        $report->forceFill(['created_at' => $attributes['created_at']])->save();
    }

    return $report;
}

it('lists reports for admins with newest first and supports type filtering', function () {
    makeReport(['title' => 'قدیمی‌تر', 'type' => ReportType::Audit, 'created_at' => now()->subDays(2)]);
    makeReport(['title' => 'تازه‌ترین', 'type' => ReportType::BugFix, 'created_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(ReportIndex::class)
        ->assertSeeInOrder(['تازه‌ترین', 'قدیمی‌تر']);

    // Filter narrows to audit only.
    Livewire::actingAs($this->admin)
        ->test(ReportIndex::class, ['status' => null])
        ->set('type', 'audit')
        ->assertSee('قدیمی‌تر')
        ->assertDontSee('تازه‌ترین');
});

it('guards the reports page to admins only', function () {
    $this->get(route('admin.reports.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->client()->create())
        ->get(route('admin.reports.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->lawyer()->create())
        ->get(route('admin.reports.index'))
        ->assertForbidden();

    $this->actingAs($this->admin)->get(route('admin.reports.index'))->assertOk();
});

it('streams private downloads through an authorized route and never exposes files publicly', function () {
    $report = makeReport(['title' => 'سند محرمانه']);

    $storedPath = Storage::disk('local')->path($report->file_path);
    expect(str_contains($storedPath, public_path()))->toBeFalse();

    // Guests first (actingAs below would persist).
    $this->get(route('admin.reports.download', $report))->assertRedirect(route('login'));

    $this->actingAs($this->admin)
        ->get(route('admin.reports.download', $report))
        ->assertOk()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8');

    // Non-admins cannot download.
    $this->actingAs(User::factory()->client()->create())
        ->get(route('admin.reports.download', $report))
        ->assertForbidden();
});
