<?php

namespace App\Models;

use App\Enums\ConsultationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerService extends Model
{
    protected $fillable = [
        'lawyer_profile_id',
        'title',
        'description',
        'consultation_type',
        'duration_minutes',
        'price_amount_minor',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'consultation_type' => ConsultationType::class,
            'price_amount_minor' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function lawyerProfile(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class);
    }

    /** Informational price in Toman (minor unit = Toman itself for MVP). */
    public function priceLabel(): ?string
    {
        if ($this->price_amount_minor === null) {
            return null;
        }

        return number_format($this->price_amount_minor).' تومان';
    }
}
