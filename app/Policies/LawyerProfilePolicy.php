<?php

namespace App\Policies;

use App\Models\LawyerProfile;
use App\Models\User;

class LawyerProfilePolicy
{
    /** A lawyer may manage only their own profile; admins may view all. */
    public function manage(User $user, LawyerProfile $profile): bool
    {
        return $user->isLawyer() && $user->id === $profile->user_id;
    }

    public function submitForReview(User $user, LawyerProfile $profile): bool
    {
        return $this->manage($user, $profile) && $profile->canSubmitForReview();
    }

    /** Only admins change verification status. */
    public function moderate(User $user, LawyerProfile $profile): bool
    {
        return $user->isAdmin();
    }
}
