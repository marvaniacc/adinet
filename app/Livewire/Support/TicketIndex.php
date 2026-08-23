<?php

namespace App\Livewire\Support;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class TicketIndex extends Component
{
    public bool $showForm = false;

    public string $subject = '';

    public string $category = 'support';

    public string $body = '';

    protected function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'category' => ['required', Rule::in(array_keys(Ticket::CATEGORIES))],
            'body' => ['required', 'string', 'min:20', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'موضوع تیکت الزامی است.',
            'subject.min' => 'موضوع باید حداقل ۵ کاراکتر باشد.',
            'body.required' => 'شرح مشکل الزامی است.',
            'body.min' => 'لطفاً شرح را کامل‌تر بنویسید (حداقل ۲۰ کاراکتر).',
        ];
    }

    public function openForm(): void
    {
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->reset('showForm', 'subject', 'category', 'body');
        $this->resetErrorBag();
    }

    public function store(): void
    {
        $data = $this->validate();

        $ticket = Auth::user()->tickets()->create([
            'subject' => $data['subject'],
            'category' => $data['category'],
            'status' => TicketStatus::Open,
        ]);

        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);

        $ticket->forceFill(['last_reply_at' => now()])->save();

        $this->closeForm();
        session()->flash('status', 'تیکت شما ثبت شد و در اسرع وقت بررسی می‌شود.');

        $this->redirect(route('tickets.show', $ticket), navigate: true);
    }

    public function render()
    {
        return view('livewire.support.ticket-index', [
            'tickets' => Auth::user()->tickets()->latest()->paginate(12),
            'categories' => Ticket::CATEGORIES,
        ]);
    }
}
