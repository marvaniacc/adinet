<?php

namespace App\Models;

use App\Enums\ConsultationRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ConsultationRequest extends Model
{
    protected $fillable = [
        'lawyer_profile_id',
        'client_id',
        'service_id',
        'subject',
        'description',
        'preferred_date',
        'preferred_time',
        'status',
        'rejection_reason',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConsultationRequestStatus::class,
            'preferred_date' => 'date',
            'decided_at' => 'datetime',
        ];
    }

    public function lawyerProfile(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(LawyerService::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function isPending(): bool
    {
        return $this->status === ConsultationRequestStatus::Pending;
    }
}
