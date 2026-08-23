<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\LawyerStatus;
use App\Models\LawyerProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class LawyerVerification extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'pending_review';

    public ?int $rejectingId = null;

    public string $rejection_reason = '';

    protected function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->cancelReject();
    }

    public function openReject(int $id): void
    {
        $this->rejectingId = $id;
        $this->resetErrorBag();
    }

    public function cancelReject(): void
    {
        $this->reset('rejectingId', 'rejection_reason');
    }

    public function verify(int $id): void
    {
        $profile = LawyerProfile::findOrFail($id);
        $this->authorize('moderate', $profile);

        if ($profile->status !== LawyerStatus::PendingReview) {
            return;
        }

        $profile->forceFill([
            'status' => LawyerStatus::Verified,
            'verified_at' => now(),
            'rejection_reason' => null,
        ])->save();

        session()->flash('status', "پروفایل {$profile->display_name} تأیید شد.");
    }

    public function reject(int $id): void
    {
        $data = $this->validate();
        $profile = LawyerProfile::findOrFail($id);
        $this->authorize('moderate', $profile);

        if ($profile->status !== LawyerStatus::PendingReview) {
            return;
        }

        $profile->forceFill([
            'status' => LawyerStatus::Rejected,
            'rejection_reason' => $data['rejection_reason'],
        ])->save();

        $this->cancelReject();
        session()->flash('status', "پروفایل {$profile->display_name} رد شد.");
    }

    public function suspend(int $id): void
    {
        $profile = LawyerProfile::findOrFail($id);
        $this->authorize('moderate', $profile);

        if ($profile->status !== LawyerStatus::Verified && $profile->status !== LawyerStatus::PendingReview) {
            return;
        }

        $profile->forceFill(['status' => LawyerStatus::Suspended])->save();

        session()->flash('status', "پروفایل {$profile->display_name} معلق شد.");
    }

    public function reinstate(int $id): void
    {
        $profile = LawyerProfile::findOrFail($id);
        $this->authorize('moderate', $profile);

        if ($profile->status !== LawyerStatus::Suspended) {
            return;
        }

        $profile->forceFill([
            'status' => LawyerStatus::Verified,
            'verified_at' => now(),
        ])->save();

        session()->flash('status', "پروفایل {$profile->display_name} مجدداً فعال شد.");
    }

    public function render()
    {
        $status = in_array($this->status, array_column(LawyerStatus::cases(), 'value'), true)
            ? LawyerStatus::from($this->status)
            : LawyerStatus::PendingReview;

        $profiles = LawyerProfile::query()
            ->where('status', $status)
            ->with(['user:id,mobile', 'city:id,name', 'specialties:id,name'])
            ->orderByDesc('submitted_for_review_at')
            ->orderBy('display_name')
            ->paginate(10);

        $counts = LawyerProfile::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.dashboard.admin.lawyer-verification', [
            'profiles' => $profiles,
            'counts' => $counts,
            'statuses' => LawyerStatus::cases(),
            'currentStatus' => $status,
            // Distinct name: $status is the public string property.
            'currentStatus' => $status,
        ]);
    }
}
