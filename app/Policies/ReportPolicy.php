<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /** Reports are private admin data - admins only, no exceptions. */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }
}
