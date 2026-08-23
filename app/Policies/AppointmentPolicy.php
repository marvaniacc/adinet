<?php

namespace App\Policies;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $this->isClient($user, $appointment) || $this->isLawyer($user, $appointment);
    }

    /**
     * Client cancels a scheduled appointment — but once PAID, self-service
     * cancellation is disabled (product rule). The lawyer side or an
     * admin-mediated refund flow handles those instead.
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->isClient($user, $appointment)
            && $appointment->status === AppointmentStatus::Scheduled
            && $appointment->payment?->status !== PaymentStatus::Paid;
    }

    /** Lawyer marks completion / no-show / cancellation. */
    public function manage(User $user, Appointment $appointment): bool
    {
        return $this->isLawyer($user, $appointment)
            && $appointment->status === AppointmentStatus::Scheduled;
    }

    private function isClient(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->client_id;
    }

    private function isLawyer(User $user, Appointment $appointment): bool
    {
        return $user->isLawyer() && $user->id === $appointment->lawyerProfile->user_id;
    }
}
