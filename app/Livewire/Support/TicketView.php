<?php

namespace App\Livewire\Support;

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
        $this->ticket = Ticket::findOrFail($ticketId);
        $this->authorize('view', $this->ticket);
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

        if (! Auth::user()->can('reply', $this->ticket)) {
            $this->addError('body', 'امکان ارسال پاسخ برای این تیکت وجود ندارد.');

            return;
        }

        $isOwner = Auth::id() === $this->ticket->user_id;
        $isAdmin = Auth::user()->isAdmin();

        $this->ticket->messages()->create([
            'user_id' => Auth::id(),
            'body' => trim($data['body']),
        ]);

        // Status transitions: admin reply -> answered; owner reply on
        // answered ticket re-opens it. Closed stays closed (policy blocks).
        if ($isAdmin) {
            $this->ticket->forceFill(['status' => TicketStatus::Answered])->save();
        } elseif ($isOwner && $this->ticket->status === TicketStatus::Answered) {
            $this->ticket->forceFill(['status' => TicketStatus::Open])->save();
        }

        $this->ticket->forceFill(['last_reply_at' => now()])->save();
        $this->reset('body');
    }

    public function close(): void
    {
        $this->authorize('close', $this->ticket);

        $this->ticket->forceFill(['status' => TicketStatus::Closed])->save();
        session()->flash('status', 'تیکت بسته شد.');
    }

    public function render()
    {
        // Opening marks counterpart messages as "seen" via updated_at touch? Not needed.

        return view('livewire.support.ticket-view', [
            'messages' => $this->ticket->messages()->with('user:id,role,first_name,last_name')->get(),
        ]);
    }
}
