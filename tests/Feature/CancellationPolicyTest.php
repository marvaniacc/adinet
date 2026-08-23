<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\PaymentStatus;
use App\Livewire\Client\AppointmentIndex;
use App\Livewire\Client\RequestIndex;
use App\Livewire\Dashboard\Admin\PaymentIndex;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->lawyerUser = User::factory()->lawyer()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);
    $this->client = User::factory()->client()->create();

    $this->request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 'موضوع سیاست لغو',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    $this->appointment = Appointment::create([
        'consultation_request_id' => $this->request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'scheduled_at' => now()->addDays(3),
        'duration_minutes' => 30,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);
});

function payForAppointment(Appointment $appointment, User $client): Payment
{
    return Payment::create([
        'appointment_id' => $appointment->id,
        'client_id' => $client->id,
        'amount_toman' => 500000,
        'gateway' => 'zarinpal',
        'authority' => 'A-TEST-'.strtoupper(Str::random(10)),
        'ref_id' => 'REF-1',
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);
}

it('forbids client self-cancellation of a PAID appointment', function () {
    payForAppointment($this->appointment, $this->client);

    Livewire::actingAs($this->client)
        ->test(AppointmentIndex::class)
        ->call('cancel', $this->appointment->id);

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled);
});

it('still allows client cancellation of an UNPAID scheduled appointment', function () {
    Livewire::actingAs($this->client)
        ->test(AppointmentIndex::class)
        ->call('cancel', $this->appointment->id);

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('denies cancelling a request whose appointment is already paid', function () {
    payForAppointment($this->appointment, $this->client);

    Livewire::actingAs($this->client)
        ->test(RequestIndex::class)
        ->call('cancel', $this->request->id);

    // Nothing changes: request stays accepted, appointment stays scheduled+paid.
    expect($this->request->fresh()->status)->toBe(ConsultationRequestStatus::Accepted)
        ->and($this->appointment->fresh()->status)->toBe(AppointmentStatus::Scheduled)
        ->and($this->appointment->fresh()->payment->status)->toBe(PaymentStatus::Paid);
});

it('flags refund when the LAWYER cancels a paid appointment', function () {
    payForAppointment($this->appointment, $this->client);

    Livewire::actingAs($this->lawyerUser)
        ->test(App\Livewire\Dashboard\Lawyer\AppointmentIndex::class)
        ->call('mark', $this->appointment->id, 'cancelled');

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and($this->appointment->fresh()->payment->fresh()->status)->toBe(PaymentStatus::RefundRequested);
});

it('lets only admins mark a refund as completed', function () {
    $payment = payForAppointment($this->appointment, $this->client);
    $payment->forceFill(['status' => PaymentStatus::RefundRequested])->save();

    // Client cannot.
    Livewire::actingAs($this->client)
        ->test(PaymentIndex::class);

    $this->actingAs($this->client)->get(route('admin.payments'))->assertForbidden();

    // Admin can.
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(PaymentIndex::class)
        ->call('markRefunded', $payment->id);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});
