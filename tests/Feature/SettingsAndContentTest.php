<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\PaymentStatus;
use App\Livewire\Dashboard\Admin\SettingsManager;
use App\Livewire\Public\LawyerList;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->lawyerUser = User::factory()->lawyer()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);
    $this->client = User::factory()->client()->create();
});

// ---------- Settings ----------

it('stores and retrieves settings with cache invalidation on save', function () {
    expect(Setting::get('request_expiry_days', '7'))->toBe('7');

    Setting::put('request_expiry_days', '10');
    expect(Setting::get('request_expiry_days'))->toBe('10')
        ->and(Setting::get('missing_key', 'fallback'))->toBe('fallback');
});

it('lets admins edit settings and enforces validation', function () {
    Livewire::actingAs($this->admin)
        ->test(SettingsManager::class)
        ->set('request_expiry_days', '12')
        ->set('support_mobile', '09121234567')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('request_expiry_days'))->toBe('12')
        ->and(Setting::get('support_mobile'))->toBe('09121234567');

    // Invalid values refused.
    Livewire::actingAs($this->admin)
        ->test(SettingsManager::class)
        ->set('request_expiry_days', '500')
        ->call('save')
        ->assertHasErrors(['request_expiry_days']);
});

it('uses the DB-configured expiry days in the expire command', function () {
    $stale = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 's', 'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);
    $stale->forceFill(['created_at' => now()->subDays(9)])->save();

    Setting::put('request_expiry_days', '8');
    $this->artisan('consultation:expire')->assertSuccessful();

    expect($stale->fresh()->status)->toBe(ConsultationRequestStatus::Expired);
});

it('shows the support mobile in the paid-appointment cancel note', function () {
    Setting::put('support_mobile', '09121112233');

    $appt = Appointment::create([
        'consultation_request_id' => ConsultationRequest::create([
            'lawyer_profile_id' => $this->lawyer->id,
            'client_id' => $this->client->id,
            'subject' => 's', 'description' => str_repeat('د', 40),
            'status' => ConsultationRequestStatus::Accepted, 'decided_at' => now(),
        ])->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'scheduled_at' => now()->addDays(2),
        'duration_minutes' => 30,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);
    Payment::create([
        'appointment_id' => $appt->id, 'client_id' => $this->client->id,
        'amount_toman' => 100000, 'status' => PaymentStatus::Paid,
        'authority' => 'X1', 'paid_at' => now(),
    ]);

    $html = $this->actingAs($this->client)->get(route('dashboard.appointments'))->getContent();

    expect(str_contains($html, 'برای لغو با پشتیبانی تماس بگیرید'))->toBeTrue()
        ->and(str_contains($html, '09121112233'))->toBeTrue();
});

// ---------- Public name search ----------

it('searches lawyers by display-name fragment on the public listing', function () {
    $match = LawyerProfile::factory()->verified()->create([
        'display_name' => 'دکتر شهاب مرادی',
        'city_id' => City::factory(),
    ]);
    LawyerProfile::factory()->verified()->create([
        'display_name' => 'وکیل بی‌ربط خاص',
        'city_id' => City::factory(),
    ]);

    Livewire::withQueryParams(['search' => 'شهاب'])
        ->test(LawyerList::class)
        ->assertSee('دکتر شهاب مرادی')
        ->assertDontSee('وکیل بی‌ربط خاص');
});

// ---------- Admin page content smoke ----------

it('renders payments index with rows and totals for admins', function () {
    Payment::create([
        'appointment_id' => Appointment::create([
            'consultation_request_id' => ConsultationRequest::create([
                'lawyer_profile_id' => $this->lawyer->id, 'client_id' => $this->client->id,
                'subject' => 's', 'description' => str_repeat('د', 40),
                'status' => ConsultationRequestStatus::Accepted, 'decided_at' => now(),
            ])->id,
            'client_id' => $this->client->id,
            'lawyer_profile_id' => $this->lawyer->id,
            'scheduled_at' => now()->addDay(), 'duration_minutes' => 30,
            'consultation_type' => ConsultationType::Phone,
            'status' => AppointmentStatus::Scheduled,
        ])->id,
        'client_id' => $this->client->id,
        'amount_toman' => 250000,
        'gateway' => 'zarinpal', 'status' => PaymentStatus::Paid,
        'authority' => 'A1', 'ref_id' => 'R1', 'paid_at' => now(),
    ]);

    $html = $this->actingAs($this->admin)->get(route('admin.payments'))->getContent();

    expect(str_contains($html, '250,000'))->toBeTrue()
        ->and(str_contains($html, 'پرداخت‌شده'))->toBeTrue();
});
