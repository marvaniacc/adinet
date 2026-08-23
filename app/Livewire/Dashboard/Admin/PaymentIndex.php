<?php

namespace App\Livewire\Dashboard\Admin;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class PaymentIndex extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.dashboard.admin.payment-index', [
            'payments' => Payment::query()
                ->with(['client:id,first_name,last_name,mobile', 'appointment:id,consultation_request_id,scheduled_at'])
                ->latest()
                ->paginate(15),
            'totals' => [
                'paid_count' => Payment::query()->where('status', PaymentStatus::Paid)->count(),
                'paid_sum' => Payment::query()->where('status', PaymentStatus::Paid)->sum('amount_toman'),
            ],
        ]);
    }
}
