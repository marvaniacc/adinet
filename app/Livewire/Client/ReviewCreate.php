<?php

namespace App\Livewire\Client;

use App\Enums\ReviewStatus;
use App\Models\ConsultationRequest;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class ReviewCreate extends Component
{
    public ConsultationRequest $request;

    public $rating = '';

    public string $comment = '';

    public function mount(int $requestId): void
    {
        $this->request = ConsultationRequest::query()
            ->where('client_id', Auth::id())
            ->with(['lawyerProfile:id,display_name,slug', 'appointment'])
            ->findOrFail($requestId);

        $this->authorize('create', [$this->request]);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'comment' => ['nullable', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'امتیازدهی الزامی است.',
            'comment.min' => 'نظر باید حداقل ۱۰ کاراکتر باشد.',
        ];
    }

    public function store(): void
    {
        // Re-check eligibility at submit time (status could have changed).
        $this->authorize('create', [$this->request]);

        if ($this->request->review()->exists()) {
            session()->flash('status', 'برای این مشاوره قبلاً نظر ثبت کرده‌اید.');

            $this->redirect(route('reviews.index'), navigate: true);

            return;
        }

        $data = $this->validate();

        Review::create([
            ...$data,
            'consultation_request_id' => $this->request->id,
            'lawyer_profile_id' => $this->request->lawyer_profile_id,
            'client_id' => Auth::id(),
            'status' => ReviewStatus::Pending,
        ]);

        session()->flash('status', 'نظر شما ثبت شد و پس از تأیید مدیر منتشر می‌شود.');

        $this->redirect(route('reviews.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.client.review-create');
    }
}
