<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\ReportType;
use App\Models\Report;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ReportIndex extends Component
{
    use WithPagination;

    /** '' = all types */
    #[Url]
    public string $type = '';

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $type = in_array($this->type, array_column(ReportType::cases(), 'value'), true)
            ? ReportType::from($this->type)
            : null;

        $reports = Report::query()
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->latest()
            ->paginate(15);

        $counts = Report::query()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('livewire.dashboard.admin.report-index', [
            'reports' => $reports,
            'types' => ReportType::cases(),
            'counts' => $counts,
            'total' => Report::query()->count(),
            // Distinct name: $type is the public string property.
            'activeType' => $type,
        ]);
    }
}
