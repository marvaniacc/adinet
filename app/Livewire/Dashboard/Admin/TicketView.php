<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class TicketView extends Component
{
    public Ticket $ticket;

    public string $body = '';

    public function mount(int $ticketId): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $this->ticket = Ticket::findOrFail($ticketId);
    }

    protected function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ];
    }

    public function reply(): void
    {
        $data = $this->validate();

        $this->ticket->messages()->create([
            'user_id' => Auth::id(),
            'body' => trim($data['body']),
        ]);

        $this->ticket->forceFill([
            'status' => TicketStatus::Answered,
            'last_reply_at' => now(),
        ])->save();

        session()->flash('status', 'پاسخ ارسال شد.');
        $this->reset('body');
    }

    public function closeTicket(): void
    {
        $this->ticket->forceFill(['status' => TicketStatus::Closed])->save();
        session()->flash('status', 'تیکت بسته شد.');
    }

    public function reopen(): void
    {
        $this->ticket->forceFill(['status' => TicketStatus::Open])->save();
        session()->flash('status', 'تیکت بازگشایی شد.');
    }

    public function render()
    {
        return view('livewire.dashboard.admin.ticket-show', [
            'messages' => $this->ticket->messages()->with('user:id,role,first_name,last_name')->get(),
        ]);
    }
}
