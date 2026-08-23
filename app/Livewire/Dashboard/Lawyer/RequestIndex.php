<?php

namespace App\Livewire\Dashboard\Lawyer;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\LawyerStatus;
use App\Models\Appointment;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Notifications\ConsultationRequestDecided;
use App\Support\JalaliDate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class RequestIndex extends Component
{
    use WithPagination;

    /** pending | accepted | closed */
    #[Url]
    public string $tab = 'pending';

    public ?int $acceptingId = null;

    public $scheduled_at = '';

    public string $accept_date_jalali = '';

    public string $accept_time = '';

    public string $accept_notes = '';

    public ?int $rejectingId = null;

    public string $rejection_reason = '';

    public function mount(): void
    {
        Auth::user()->lawyerProfile()->firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'display_name' => 'وکیل آدینت',
                'slug' => LawyerProfile::uniqueSlug('vakil'),
                'status' => LawyerStatus::Draft->value,
            ],
        );
    }

    public function updatedTab(): void
    {
        $this->resetPage();
        $this->cancelPanels();
    }

    public function openAccept(int $id): void
    {
        $this->rejectingId = null;
        $this->acceptingId = $id;
        $this->accept_date_jalali = '';
        $this->accept_time = '';
        $this->accept_notes = '';
        $this->resetErrorBag();
    }

    public function openReject(int $id): void
    {
        $this->acceptingId = null;
        $this->rejectingId = $id;
        $this->rejection_reason = '';
        $this->resetErrorBag();
    }

    public function cancelPanels(): void
    {
        $this->reset('acceptingId', 'scheduled_at', 'accept_date_jalali', 'accept_time', 'accept_notes', 'rejectingId', 'rejection_reason');
        $this->resetErrorBag();
    }

    protected function prepareForValidation($attributes)
    {
        if (! isset($attributes['accept_date_jalali'], $attributes['accept_time'])) {
            return $attributes;
        }

        try {
            $dt = JalaliDate::parseDateTime(
                (string) $attributes['accept_date_jalali'],
                (string) $attributes['accept_time'],
            );

            $attributes['scheduled_at'] = $dt->format('Y-m-d H:i:s');
        } catch (\InvalidArgumentException) {
            // invalid input: required rules report it against the date field
        }

        return $attributes;
    }

    protected function rules(): array
    {
        return [
            'accept_date_jalali' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                try {
                    JalaliDate::parse((string) $value);
                } catch (\InvalidArgumentException $e) {
                    $fail($e->getMessage());
                }
            }],
            'accept_time' => ['required', 'date_format:H:i'],
            'scheduled_at' => ['required', 'date', 'after:now', 'before:+90 days'],
            'accept_notes' => ['nullable', 'string', 'max:1000'],
            'rejection_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'accept_date_jalali.required' => 'تاریخ نوبت (شمسی) الزامی است.',
            'accept_date_jalali.string' => 'تاریخ شمسی معتبر نیست. نمونه: ۱۴۰۵/۰۶/۲۵',
            'accept_time.required' => 'ساعت نوبت الزامی است.',
            'scheduled_at.after' => 'زمان نوبت باید در آینده باشد.',
            'scheduled_at.before' => 'زمان نوبت نمی‌تواند بیش از ۹۰ روز بعد باشد.',
        ];
    }

    public function accept(int $id): void
    {
        $data = $this->validate();
        $data = ['scheduled_at' => $data['scheduled_at'], 'accept_notes' => $this->accept_notes ?: null];

        $consultationRequest = ConsultationRequest::query()
            ->whereHas('lawyerProfile', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $this->authorize('decide', $consultationRequest);

        DB::transaction(function () use ($consultationRequest, $data) {
            // Guard against double-accept races.
            $claimed = ConsultationRequest::query()
                ->whereKey($consultationRequest->getKey())
                ->where('status', ConsultationRequestStatus::Pending)
                ->update([
                    'status' => ConsultationRequestStatus::Accepted->value,
                    'decided_at' => now(),
                ]);

            if ($claimed === 0) {
                throw new \RuntimeException('این درخواست قبلاً پاسخ داده شده است.');
            }

            $service = $consultationRequest->service;

            Appointment::create([
                'consultation_request_id' => $consultationRequest->id,
                'client_id' => $consultationRequest->client_id,
                'lawyer_profile_id' => $consultationRequest->lawyer_profile_id,
                'service_id' => $consultationRequest->service_id,
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $service?->duration_minutes ?? 30,
                'consultation_type' => $service?->consultation_type ?? ConsultationType::Phone,
                'status' => AppointmentStatus::Scheduled,
                'notes' => $data['accept_notes'],
            ]);

            $consultationRequest->refresh();
            $consultationRequest->client->notify(new ConsultationRequestDecided($consultationRequest));
        });

        $this->cancelPanels();
        session()->flash('status', 'درخواست پذیرفته شد و نوبت برای موکل ایجاد شد.');
    }

    public function reject(int $id): void
    {
        $this->validateOnly('rejection_reason');

        $consultationRequest = ConsultationRequest::query()
            ->whereHas('lawyerProfile', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $this->authorize('decide', $consultationRequest);

        $updated = ConsultationRequest::query()
            ->whereKey($consultationRequest->getKey())
            ->where('status', ConsultationRequestStatus::Pending)
            ->update([
                'status' => ConsultationRequestStatus::Rejected->value,
                'rejection_reason' => $this->rejection_reason ?: null,
                'decided_at' => now(),
            ]);

        if ($updated === 0) {
            session()->flash('status', 'این درخواست قبلاً پاسخ داده شده است.');

            return;
        }

        $consultationRequest->refresh();
        $consultationRequest->client->notify(new ConsultationRequestDecided($consultationRequest));

        $this->cancelPanels();
        session()->flash('status', 'درخواست رد شد و موکل مطلع گردید.');
    }

    public function render()
    {
        $profile = Auth::user()->lawyerProfile;

        $query = $profile->consultationRequests()
            ->with(['client:id,first_name,last_name,mobile', 'service:id,title', 'appointment', 'conversation:id,consultation_request_id']);

        $filtered = match ($this->tab) {
            'accepted' => $query->where('status', ConsultationRequestStatus::Accepted),
            'closed' => $query->whereIn('status', [ConsultationRequestStatus::Rejected, ConsultationRequestStatus::Cancelled, ConsultationRequestStatus::Expired]),
            default => $query->where('status', ConsultationRequestStatus::Pending),
        };

        $requests = $filtered->latest()->paginate(10);

        return view('livewire.dashboard.lawyer.request-index', [
            'requests' => $requests,
            'pendingCount' => $profile->consultationRequests()->where('status', ConsultationRequestStatus::Pending)->count(),
        ]);
    }
}
