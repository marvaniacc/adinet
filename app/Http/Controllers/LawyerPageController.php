<?php

namespace App\Http\Controllers;

use App\Models\LawyerProfile;
use Illuminate\Contracts\View\View;

class LawyerPageController extends Controller
{
    public function show(string $slug): View
    {
        $profile = LawyerProfile::query()
            ->where('slug', $slug)
            ->with(['city:id,name', 'specialties:id,name,slug'])
            ->firstOrFail();

        // Only verified profiles are public; owners may preview their own.
        $isOwner = auth()->check() && auth()->id() === $profile->user_id;

        if (! $profile->isVerified() && ! $isOwner) {
            abort(404);
        }

        return view('lawyers.show', [
            'profile' => $profile,
            'services' => $profile->activeServices()->get(),
            'isOwner' => $isOwner,
            // Public aggregate + approved reviews only.
            'avgRating' => round((float) $profile->approvedReviews()->avg('rating'), 1),
            'reviewsCount' => $profile->approvedReviews()->count(),
            'reviews' => $profile->approvedReviews()
                ->with(['client:id,first_name,last_name'])
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }
}
