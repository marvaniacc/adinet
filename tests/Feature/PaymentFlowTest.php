<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        'subject' => 'موضوع پرداخت',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    $this->service = $this->lawyer->services()->create([
        'title' => 'مشاوره تلفنی',
        'consultation_type' => ConsultationType::Phone,
        'duration_minutes' => 30,
        'price_amount_minor' => 500000, // 500,000 Toman
        'is_active' => true,
    ]);

    $this->appointment = Appointment::create([
        'consultation_request_id' => $this->request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'service_id' => $this->service->id,
        'scheduled_at' => now()->addDays(3),
        'duration_minutes' => 30,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);
});

it('redirects the client to the gateway and marks payment redirected', function () {
    // FakeGateway driver is bound by default in tests (mode=fake).
    $response = $this->actingAs($this->client)->get(route('payments.start', $this->appointment));

    $payment = Payment::query()->sole();

    expect($response->isRedirection())->toBeTrue()
        ->and($payment->amount_toman)->toBe(500000)
        ->and($payment->status)->toBe(PaymentStatus::Redirected)
        ->and(str_starts_with($payment->authority, 'FAKE-'))->toBeTrue()
        ->and(str_contains($response->headers->get('Location'), 'payments.fake') || str_contains($response->getTargetUrl(), '/dev/payments/simulate/'))->toBeTrue();
});

it('verifies a successful callback and records ref id', function () {
    // Simulate a started payment.
    $this->actingAs($this->client)->get(route('payments.start', $this->appointment));
    $payment = Payment::query()->sole();

    // Gateway redirects back with OK.
    $response = $this->get(route('payments.callback', ['Authority' => $payment->authority, 'Status' => 'OK']));

    $response->assertRedirect(route('dashboard.appointments'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->ref_id)->not->toBeNull()
        ->and($payment->fresh()->paid_at)->not->toBeNull();

    // Double callback (code 101 semantics) stays paid without error.
    $this->get(route('payments.callback', ['Authority' => $payment->authority, 'Status' => 'OK']))
        ->assertRedirect(route('dashboard.appointments'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('marks payment failed on NOK callback', function () {
    $this->actingAs($this->client)->get(route('payments.start', $this->appointment));
    $payment = Payment::query()->sole();

    $this->get(route('payments.callback', ['Authority' => $payment->authority, 'Status' => 'NOK']))
        ->assertRedirect(route('dashboard.appointments'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($payment->fresh()->paid_at)->toBeNull();
});

it('blocks strangers from starting payments for appointments they do not own', function () {
    $this->actingAs(User::factory()->client()->create())
        ->get(route('payments.start', $this->appointment))
        ->assertForbidden();

    expect(Payment::count())->toBe(0);
});

it('rejects unknown authorities with 404', function () {
    $this->get(route('payments.callback', ['Authority' => 'UNKNOWN-123', 'Status' => 'OK']))
        ->assertNotFound();
});
