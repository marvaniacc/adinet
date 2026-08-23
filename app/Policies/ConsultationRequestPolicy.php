<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\ConsultationRequest;
use App\Models\User;

class ConsultationRequestPolicy
{
    public function view(User $user, ConsultationRequest $request): bool
    {
        return $user->id === $request->client_id
            || ($user->isLawyer() && $user->id === $request->lawyerProfile->user_id);
    }

    /** Client cancels their own request while it still can be cancelled. */
    public function cancel(User $user, ConsultationRequest $request): bool
    {
        if ($user->id !== $request->client_id) {
            return false;
        }

        if (! in_array($request->status, [ConsultationRequestStatus::Pending, ConsultationRequestStatus::Accepted], true)) {
            return false;
        }

        // Paid appointments require admin/lawyer-mediated cancellation.
        $appointment = $request->appointment;

        if ($appointment !== null
            && $appointment->status === AppointmentStatus::Scheduled
            && $appointment->payment?->status === PaymentStatus::Paid) {
            return false;
        }

        return true;
    }

    /** Lawyer accepts or rejects a pending request sent to them. */
    public function decide(User $user, ConsultationRequest $request): bool
    {
        return $user->isLawyer()
            && $user->id === $request->lawyerProfile->user_id
            && $request->status === ConsultationRequestStatus::Pending;
    }

    /**
     * A review may be written once, by the client of the consultation,
     * only after the consultation actually took place (request completed).
     */
    public function create(User $user, ConsultationRequest $request): bool
    {
        return $user->id === $request->client_id
            && $request->status === ConsultationRequestStatus::Completed
            && ! $request->review()->exists();
    }
}
