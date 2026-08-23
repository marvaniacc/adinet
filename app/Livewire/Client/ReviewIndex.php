<?php

namespace App\Livewire\Client;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ReviewIndex extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.client.review-index', [
            'reviews' => Auth::user()->consultationRequests()
                ->whereHas('review')
                ->with(['review', 'lawyerProfile:id,display_name,slug'])
                ->latest()
                ->paginate(10),
        ]);
    }
}
