<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\LawyerStatus;
use App\Models\LawyerProfile;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Overview extends Component
{
    public function render()
    {
        return view('livewire.dashboard.admin.overview', [
            'pendingCount' => LawyerProfile::query()->where('status', LawyerStatus::PendingReview)->count(),
            'verifiedCount' => LawyerProfile::query()->where('status', LawyerStatus::Verified)->count(),
            'userCount' => User::query()->count(),
        ]);
    }
}
