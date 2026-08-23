<?php

namespace App\Models;

use App\Enums\LawyerStatus;
use App\Enums\ReviewStatus;
use Database\Factories\LawyerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LawyerProfile extends Model
{
    /** @use HasFactory<LawyerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'city_id',
        'display_name',
        'slug',
        'profile_photo',
        'bar_association',
        'license_number',
        'bio',
        'years_of_experience',
        'phone',
        'status',
        'rejection_reason',
        'submitted_for_review_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LawyerStatus::class,
            'submitted_for_review_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'lawyer_specialties');
    }

    public function services(): HasMany
    {
        return $this->hasMany(LawyerService::class);
    }

    public function consultationRequests(): HasMany
    {
        return $this->hasMany(ConsultationRequest::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    /** Publicly visible reviews only (admin-approved). */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', ReviewStatus::Approved);
    }

    public function activeServices(): HasMany
    {
        return $this->services()->where('is_active', true);
    }

    public function isVerified(): bool
    {
        return $this->status === LawyerStatus::Verified;
    }

    public function canSubmitForReview(): bool
    {
        return in_array($this->status, [LawyerStatus::Draft, LawyerStatus::Rejected], true);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        // Persian-friendly URL slug: keep letters/digits/spaces, dash the rest.
        $base = mb_strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $name) ?? ''));
        $base = str_replace(' ', '-', preg_replace('/\s+/u', ' ', trim($base)) ?? '');
        $base = $base !== '' ? $base : 'lawyer';

        $slug = $base;
        $i = 2;

        while (self::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
