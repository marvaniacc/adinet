<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class TicketIndex extends Component
{
    use WithPagination;

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
        $tickets = Ticket::query()
            ->with('user:id,first_name,last_name,mobile,role')
            ->when(in_array($this->status, array_column(TicketStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', TicketStatus::from($this->status)))
            ->when($this->search !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('subject', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('mobile', 'like', $term));
                });
            })
            ->latest('last_reply_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.dashboard.admin.ticket-index', [
            'tickets' => $tickets,
            'statuses' => TicketStatus::cases(),
        ]);
    }
}
