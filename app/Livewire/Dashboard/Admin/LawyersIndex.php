<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\LawyerStatus;
use App\Models\AdminAction;
use App\Models\LawyerProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class LawyersIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function suspend(int $id): void
    {
        $profile = LawyerProfile::findOrFail($id);

        if (! auth()->user()->isAdmin() || $profile->status !== LawyerStatus::Verified) {
            return;
        }

        $profile->forceFill(['status' => LawyerStatus::Suspended])->save();
        AdminAction::record(auth()->user(), 'lawyer.suspend', $profile);
        session()->flash('status', "پروفایل {$profile->display_name} معلق شد.");
    }

    public function reinstate(int $id): void
    {
        $profile = LawyerProfile::findOrFail($id);

        if (! auth()->user()->isAdmin() || $profile->status !== LawyerStatus::Suspended) {
            return;
        }

        $profile->forceFill(['status' => LawyerStatus::Verified, 'verified_at' => now()])->save();
        AdminAction::record(auth()->user(), 'lawyer.reinstate', $profile);
        session()->flash('status', "پروفایل {$profile->display_name} فعال شد.");
    }

    public function render()
    {
        $lawyers = LawyerProfile::query()
            ->with(['user:id,mobile', 'city:id,name'])
            ->withCount(['services', 'consultationRequests'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($qq) {
                $term = '%'.trim($this->search).'%';
                $qq->where('display_name', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('mobile', 'like', $term));
            }))
            ->when(in_array($this->status, array_column(LawyerStatus::cases(), 'value'), true),
                fn ($q) => $q->where('status', LawyerStatus::from($this->status)))
            ->latest()
            ->paginate(15);

        return view('livewire.dashboard.admin.lawyers-index', [
            'lawyers' => $lawyers,
            'statuses' => LawyerStatus::cases(),
        ]);
    }
}
