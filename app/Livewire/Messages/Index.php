<?php

namespace App\Livewire\Messages;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $user = Auth::user();

        $conversations = Conversation::query()
            ->with(['consultationRequest:id,subject,status,client_id', 'lawyerProfile:id,display_name,slug'])
            ->when($user->isLawyer(), fn ($q) => $q->whereHas(
                'lawyerProfile',
                fn ($qq) => $qq->where('user_id', $user->id)
            ), fn ($q) => $q->where('client_id', $user->id))
            ->withCount(['messages as unread_count' => fn ($q) => $q
                ->where('sender_id', '!=', $user->id)
                ->whereNull('read_at'),
            ])
            ->orderByDesc('last_message_at')
            ->paginate(15);

        return view('livewire.messages.index', [
            'conversations' => $conversations,
        ]);
    }
}
