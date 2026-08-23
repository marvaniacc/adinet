<?php

namespace App\Livewire\Client;

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Models\ConsultationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
class RequestIndex extends Component
{
    use WithPagination;

    public function cancel(int $id): void
    {
        $consultationRequest = ConsultationRequest::query()
            ->where('client_id', Auth::id())
            ->findOrFail($id);

        $this->authorize('cancel', $consultationRequest);

        DB::transaction(function () use ($consultationRequest) {
            // Cancelling an accepted request also cancels its scheduled appointment.
            if ($consultationRequest->status === ConsultationRequestStatus::Accepted) {
                $consultationRequest->appointment()
                    ->where('status', AppointmentStatus::Scheduled)
                    ->update(['status' => AppointmentStatus::Cancelled->value]);
            }

            $consultationRequest->forceFill([
                'status' => ConsultationRequestStatus::Cancelled,
                'decided_at' => now(),
            ])->save();
        });

        session()->flash('status', 'درخواست لغو شد.');
    }

    public function render()
    {
        return view('livewire.client.request-index', [
            'requests' => Auth::user()->consultationRequests()
                ->with(['lawyerProfile:id,display_name,slug', 'service:id,title', 'conversation:id,consultation_request_id'])
                ->latest()
                ->paginate(10),
        ]);
    }
}
