<?php

namespace App\Livewire\Dashboard\Lawyer;

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
        $profile = Auth::user()->lawyerProfile;

        return view('livewire.dashboard.lawyer.review-index', [
            'reviews' => $profile->reviews()
                ->with(['client:id,first_name,last_name', 'consultationRequest:id,subject'])
                ->latest()
                ->paginate(10),
            'avgRating' => round((float) $profile->approvedReviews()->avg('rating'), 1),
            'approvedCount' => $profile->approvedReviews()->count(),
        ]);
    }
}
