<?php

namespace App\Livewire\Client;

use App\Enums\ConsultationRequestStatus;
use App\Enums\LawyerStatus;
use App\Models\ConsultationRequest;
use App\Models\Conversation;
use App\Models\LawyerProfile;
use App\Notifications\ConsultationRequestReceived;
use App\Services\SlotGenerator;
use App\Support\JalaliDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class RequestCreate extends Component
{
    public LawyerProfile $profile;

    public $service_id = '';

    public string $subject = '';

    public string $description = '';

    public $preferred_date = '';

    public $preferred_time = '';

    public function mount(string $slug): void
    {
        $this->profile = LawyerProfile::query()
            ->where('slug', $slug)
            ->where('status', LawyerStatus::Verified)
            ->firstOrFail();
    }

    /** Accept both chip-provided Gregorian dates and manually typed Jalali dates. */
    protected function prepareForValidation($attributes)
    {
        $raw = trim((string) ($attributes['preferred_date'] ?? ''));

        if ($raw === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $attributes; // slot picker already supplies ISO dates
        }

        try {
            $attributes['preferred_date'] = JalaliDate::parse($raw)->toDateString();
        } catch (\InvalidArgumentException) {
            // leave as-is; the date rule reports it
        }

        return $attributes;
    }

    protected function rules(): array
    {
        return [
            'service_id' => [
                'required', 'integer',
                Rule::exists('lawyer_services', 'id')->where(function ($q) {
                    $q->where('lawyer_profile_id', $this->profile->id)->where('is_active', true);
                }),
            ],
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today', 'before:+60 days'],
            'preferred_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.required' => 'انتخاب یکی از خدمات مشاوره الزامی است.',
            'subject.required' => 'موضوع درخواست را بنویسید.',
            'subject.min' => 'موضوع باید حداقل ۵ کاراکتر باشد.',
            'description.required' => 'شرح مسئله حقوقی الزامی است.',
            'description.min' => 'لطفاً شرح مسئله را کامل‌تر بنویسید (حداقل ۳۰ کاراکتر).',
            'preferred_date.after_or_equal' => 'تاریخ پیشنهادی نمی‌تواند در گذشته باشد.',
        ];
    }

    public function submit(): void
    {
        $data = $this->validate();

        $request = DB::transaction(function () use ($data) {
            $consultationRequest = ConsultationRequest::create([
                ...$data,
                'preferred_date' => $data['preferred_date'] ?: null,
                'preferred_time' => $data['preferred_time'] ?: null,
                'lawyer_profile_id' => $this->profile->id,
                'client_id' => Auth::id(),
                'status' => ConsultationRequestStatus::Pending,
            ]);

            // One conversation per consultation request, created with it.
            Conversation::create([
                'consultation_request_id' => $consultationRequest->id,
                'client_id' => Auth::id(),
                'lawyer_profile_id' => $this->profile->id,
            ]);

            // Notify the lawyer; SMS failure must never roll back the request.
            $this->profile->user->notify(new ConsultationRequestReceived($consultationRequest));

            return $consultationRequest;
        });

        session()->flash('status', 'درخواست مشاوره شما ثبت شد و به وکیل اطلاع داده شد.');

        $this->redirect(route('dashboard.requests'), navigate: true);
    }

    public function render()
    {
        return view('livewire.client.request-create', [
            'services' => $this->profile->activeServices()->get(),
            'timeSlots' => app(SlotGenerator::class)->upcomingFor($this->profile)->take(60),
        ]);
    }
}
