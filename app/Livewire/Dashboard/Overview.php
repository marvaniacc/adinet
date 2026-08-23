<?php

namespace App\Livewire\Dashboard;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Overview extends Component
{
    public User $user;

    public function mount(): void
    {
        $this->user = auth()->user();
    }

    public function render()
    {
        return view('livewire.dashboard.overview');
    }
}
