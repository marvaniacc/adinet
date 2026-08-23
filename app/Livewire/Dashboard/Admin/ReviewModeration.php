<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\ReviewStatus;
use App\Models\AdminAction;
use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class ReviewModeration extends Component
{
    use WithPagination;

    /** pending | approved | rejected */
    #[Url]
    public string $status = 'pending';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function decide(int $id, string $decision): void
    {
        $review = Review::findOrFail($id);
        $this->authorize('moderate', $review);

        $newStatus = match ($decision) {
            'approve' => ReviewStatus::Approved,
            'reject' => ReviewStatus::Rejected,
            default => null,
        };

        if ($newStatus === null || $review->status === $newStatus) {
            return;
        }

        $review->forceFill(['status' => $newStatus])->save();

        AdminAction::record(auth()->user(), "review.{$decision}", $review);

        session()->flash('status', 'نظر '.$newStatus->label().' شد.');
    }

    public function render()
    {
        $status = in_array($this->status, array_column(ReviewStatus::cases(), 'value'), true)
            ? ReviewStatus::from($this->status)
            : ReviewStatus::Pending;

        $reviews = Review::query()
            ->where('status', $status)
            ->with(['client:id,first_name,last_name', 'lawyerProfile:id,display_name'])
            ->latest()
            ->paginate(10);

        $counts = Review::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('livewire.dashboard.admin.review-moderation', [
            'reviews' => $reviews,
            'counts' => $counts,
            'statuses' => ReviewStatus::cases(),
            'currentStatus' => $status,
        ]);
    }
}
