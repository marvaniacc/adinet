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
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'services.zarinpal.mode' => 'sandbox',
        'services.zarinpal.merchant_id' => 'test-merchant-id',
    ]);

    // Successful-gateway stubs are declared per-test (Http::fake merges,
    // so a leftover success stub here would shadow test-level overrides).
    Http::preventStrayRequests();

    $this->lawyerUser = User::factory()->lawyer()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);

    $this->client = User::factory()->client()->create();

    $this->request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 'موضوع پرداخت واقعی',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    $this->service = $this->lawyer->services()->create([
        'title' => 'مشاوره تلفنی',
        'consultation_type' => ConsultationType::Phone,
        'duration_minutes' => 30,
        'price_amount_minor' => 750000,
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

it('drives the FULL real zarinpal journey: start -> gateway redirect -> callback -> paid', function () {
    Http::fake([
        'sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
            'data' => ['authority' => 'A000000000000000000000000000Test01', 'code' => 100],
            'errors' => [],
        ]),
        'sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
            'data' => ['code' => 100, 'ref_id' => 987654],
            'errors' => [],
        ]),
    ]);

    // 1) Client starts checkout.
    $start = $this->actingAs($this->client)->get(route('payments.start', $this->appointment));

    // Redirects away to the real sandbox gateway URL.
    $start->assertRedirect()
        ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/A000000000000000000000000000Test01');

    $payment = Payment::query()->sole();

    expect($payment->status)->toBe(PaymentStatus::Redirected)
        ->and($payment->authority)->toBe('A000000000000000000000000000Test01')
        ->and($payment->gateway)->toBe('zarinpal')
        ->and($payment->amount_toman)->toBe(750000);

    // Correct payload went to the real request endpoint (amount converted to RIALS).
    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'payment/request.json')
            && $request['merchant_id'] === 'test-merchant-id'
            && $request['amount'] === 7_500_000 // 750,000 Toman × 10
            && str_contains($request['callback_url'], '/payments/callback');
    });

    // 2) Gateway redirects back with OK -> server verifies against real verify endpoint.
    [$c, $body] = [$this->get(route('payments.callback', [
        'Authority' => $payment->authority,
        'Status' => 'OK',
    ])), null];

    $c->assertRedirect(route('dashboard.appointments'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->ref_id)->toBe('987654')
        ->and($payment->fresh()->paid_at)->not->toBeNull();

    // Verify hit the REAL endpoint with matching amount.
    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'payment/verify.json')
            && $request['authority'] === 'A000000000000000000000000000Test01'
            && $request['amount'] === 7_500_000;
    });
});

it('marks failed without calling verify when gateway returns NOK', function () {
    Http::fake([
        'sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
            'data' => ['authority' => 'ANOK00000000000000000000000Test01', 'code' => 100],
            'errors' => [],
        ]),
    ]);

    $this->actingAs($this->client)->get(route('payments.start', $this->appointment));
    $payment = Payment::query()->sole();

    $this->get(route('payments.callback', ['Authority' => $payment->authority, 'Status' => 'NOK']))
        ->assertRedirect(route('dashboard.appointments'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed);

    // Verify endpoint never contacted on NOK; only the start call happened.
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'verify.json'));
    Http::assertSentCount(1);
});

it('surfaces a friendly error when the merchant id is not configured yet', function () {
    config(['services.zarinpal.merchant_id' => '']);

    $response = $this->actingAs($this->client)
        ->from(route('dashboard.appointments'))
        ->get(route('payments.start', $this->appointment));

    $response->assertRedirect(route('dashboard.appointments'));
    $this->followRedirects($response)->assertSee('درگاه پرداخت');

    expect(Payment::count())->toBe(0);
});

it('handles gateway rejection (errors envelope) gracefully', function () {
    Http::fake([
        'sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
            'data' => [],
            'errors' => ['code' => -9, 'message' => 'خطای اعتبارسنجی'],
        ]),
    ]);

    $this->actingAs($this->client)
        ->from(route('dashboard.appointments'))
        ->get(route('payments.start', $this->appointment))
        ->assertRedirect(route('dashboard.appointments'));

    expect(Payment::count())->toBe(0);
});
