<?php

namespace App\Livewire\Dashboard\Lawyer;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class AppointmentIndex extends Component
{
    use WithPagination;

    public function mark(int $id, string $status): void
    {
        $appointment = Appointment::query()
            ->whereHas('lawyerProfile', fn ($q) => $q->where('user_id', Auth::id()))
            ->findOrFail($id);

        $this->authorize('manage', $appointment);

        $newStatus = AppointmentStatus::tryFrom($status);

        if ($newStatus === null || $newStatus === AppointmentStatus::Scheduled) {
            return; // Only terminal transitions are offered to lawyers.
        }

        // A held consultation completes the whole request, unlocking the review.
        DB::transaction(function () use ($appointment, $newStatus) {
            $appointment->forceFill(['status' => $newStatus])->save();

            if ($newStatus === AppointmentStatus::Completed) {
                $appointment->consultationRequest->forceFill([
                    'status' => ConsultationRequestStatus::Completed,
                ])->save();
            }

            // Lawyer cancelled a PAID session -> flag the money for refund.
            if ($newStatus === AppointmentStatus::Cancelled
                && ($appointment->payment?->status) === PaymentStatus::Paid) {
                $appointment->payment->forceFill([
                    'status' => PaymentStatus::RefundRequested,
                ])->save();
            }
        });

        session()->flash('status', 'وضعیت نوبت به «'.$newStatus->label().'» تغییر کرد.');
    }

    public function render()
    {
        $appointments = Auth::user()->lawyerProfile->appointments()
            ->with(['client:id,first_name,last_name,mobile', 'service:id,title'])
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->paginate(10);

        return view('livewire.dashboard.lawyer.appointment-index', [
            'appointments' => $appointments,
        ]);
    }
}
