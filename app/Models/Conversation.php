<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'consultation_request_id',
        'client_id',
        'lawyer_profile_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    /** Messages sent by the other party that this user has not read yet. */
    public function unreadFor(int $userId): HasMany
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at');
    }

    public function involves(User $user): bool
    {
        if ($this->client_id === $user->id) {
            return true;
        }

        return $user->isLawyer() && $this->lawyer_profile_id === $user->lawyerProfile?->id;
    }
}
