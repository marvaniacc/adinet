<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class AppointmentOversight extends Component
{
    use WithPagination;

    /** scheduled | completed | cancelled | no_show | '' (all) */
    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $appointments = Appointment::query()
            ->with(['client:id,first_name,last_name,mobile', 'lawyerProfile:id,display_name', 'service:id,title'])
            ->when(in_array($this->status, array_column(AppointmentStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', AppointmentStatus::from($this->status)))
            ->when($this->search !== '', fn ($q) => $q->where(function ($qq) {
                $term = '%'.trim($this->search).'%';
                $qq->whereHas('client', fn ($c) => $c->where('mobile', 'like', $term))
                    ->orWhereHas('lawyerProfile', fn ($l) => $l->where('display_name', 'like', $term));
            }))
            ->orderByDesc('scheduled_at')
            ->paginate(15);

        return view('livewire.dashboard.admin.appointment-oversight', [
            'appointments' => $appointments,
            'statuses' => AppointmentStatus::cases(),
        ]);
    }
}
