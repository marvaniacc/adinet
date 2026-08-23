<?php

namespace App\Livewire\Dashboard\Lawyer;

use App\Enums\LawyerStatus;
use App\Models\LawyerProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Home extends Component
{
    public LawyerProfile $profile;

    public function mount(): void
    {
        $this->profile = Auth::user()->lawyerProfile()->firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'display_name' => 'وکیل آدینت',
                'slug' => LawyerProfile::uniqueSlug('vakil'),
                'status' => LawyerStatus::Draft,
            ],
        );
    }

    public function render()
    {
        return view('livewire.dashboard.lawyer.home');
    }
}
