<?php

namespace App\Policies;

use App\Enums\ConsultationRequestStatus;
use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /** Only the two parties of the consultation may open the conversation. */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->involves($user);
    }

    /**
     * Writing is allowed only while the consultation is open
     * (pending/accepted) — terminal statuses (rejected, cancelled,
     * expired, completed) lock the thread to read-only.
     * Lawyers additionally must hold an active (verified, not suspended)
     * profile to write; clients keep write access on open threads.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (! $this->view($user, $conversation)) {
            return false;
        }

        $status = $conversation->consultationRequest->status;

        if (! in_array($status, [ConsultationRequestStatus::Pending, ConsultationRequestStatus::Accepted], true)) {
            return false;
        }

        if ($user->isLawyer() && ! $conversation->lawyerProfile->isActive()) {
            return false;
        }

        return true;
    }
}
