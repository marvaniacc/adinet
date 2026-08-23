<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class RequestOversight extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    public ?int $expandedId = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expandedId = $this->expandedId === $id ? null : $id;
    }

    public function render()
    {
        $requests = ConsultationRequest::query()
            ->with(['client:id,first_name,last_name,mobile', 'lawyerProfile:id,display_name,slug', 'service:id,title'])
            ->when(in_array($this->status, array_column(ConsultationRequestStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', ConsultationRequestStatus::from($this->status)))
            ->when($this->search !== '', fn ($q) => $q->where(function ($qq) {
                $term = '%'.trim($this->search).'%';
                $qq->where('subject', 'like', $term)
                    ->orWhereHas('client', fn ($c) => $c->where('mobile', 'like', $term))
                    ->orWhereHas('lawyerProfile', fn ($l) => $l->where('display_name', 'like', $term));
            }))
            ->latest()
            ->paginate(15);

        return view('livewire.dashboard.admin.request-oversight', [
            'requests' => $requests,
            'statuses' => ConsultationRequestStatus::cases(),
        ]);
    }
}
