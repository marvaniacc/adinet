<?php

namespace App\Livewire\Client;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class AppointmentIndex extends Component
{
    use WithPagination;

    public function cancel(int $id): void
    {
        $appointment = Appointment::query()
            ->where('client_id', Auth::id())
            ->findOrFail($id);

        $this->authorize('cancel', $appointment);

        $appointment->forceFill(['status' => AppointmentStatus::Cancelled])->save();

        session()->flash('status', 'نوبت لغو شد.');
    }

    public function render()
    {
        return view('livewire.client.appointment-index', [
            'appointments' => Auth::user()->appointments()
                ->with(['lawyerProfile:id,display_name,slug', 'service:id,title,price_amount_minor', 'consultationRequest.review', 'payment:id,appointment_id,status,ref_id,paid_at'])
                ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")
                ->orderBy('scheduled_at')
                ->paginate(10),
        ]);
    }
}
