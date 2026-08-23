<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /** Only admins publish or hide reviews. Eligibility lives on ConsultationRequestPolicy::create. */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
