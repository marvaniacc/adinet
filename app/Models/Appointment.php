<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'consultation_request_id',
        'client_id',
        'lawyer_profile_id',
        'service_id',
        'scheduled_at',
        'duration_minutes',
        'consultation_type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'consultation_type' => ConsultationType::class,
            'status' => AppointmentStatus::class,
        ];
    }

    public function consultationRequest(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function lawyerProfile(): BelongsTo
    {
        return $this->belongsTo(LawyerProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(LawyerService::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
