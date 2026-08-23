<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const CATEGORIES = [
        'support' => 'پشتیبانی عمومی',
        'billing' => 'پرداخت و مالی',
        'technical' => 'مشکل فنی',
        'other' => 'سایر',
    ];

    protected $fillable = ['user_id', 'subject', 'category', 'status', 'last_reply_at'];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'last_reply_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }

    public function isOpen(): bool
    {
        return $this->status === TicketStatus::Open;
    }
}
