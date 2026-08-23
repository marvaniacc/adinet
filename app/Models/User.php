<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['mobile', 'first_name', 'last_name', 'role'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    final public const ROLE_CLIENT = 'client';

    final public const ROLE_LAWYER = 'lawyer';

    final public const ROLE_ADMIN = 'admin';

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function lawyerProfile()
    {
        return $this->hasOne(LawyerProfile::class);
    }

    public function consultationRequests()
    {
        return $this->hasMany(ConsultationRequest::class, 'client_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function isClient(): bool
    {
        return $this->role === self::ROLE_CLIENT;
    }

    public function isLawyer(): bool
    {
        return $this->role === self::ROLE_LAWYER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: 'کاربر آدینت';
    }

    /**
     * Server-authoritative post-login destination, resolved from the
     * persisted role - never from client-supplied state.
     */
    public function dashboardUrl(): string
    {
        return match (true) {
            $this->isAdmin() => route('admin.dashboard'),
            $this->isLawyer() => route('dashboard.lawyer.index'),
            default => route('dashboard'),
        };
    }

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }
}
