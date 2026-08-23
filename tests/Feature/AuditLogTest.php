<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\PaymentStatus;
use App\Livewire\Dashboard\Admin\LawyerVerification;
use App\Livewire\Dashboard\Admin\PaymentIndex;
use App\Livewire\Dashboard\Admin\SettingsManager;
use App\Models\AdminAction;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => User::factory()->lawyer()->create()->id,
        'city_id' => City::factory(),
    ]);
    $this->client = User::factory()->client()->create();
});

it('records an audit entry when a lawyer is verified', function () {
    $profile = LawyerProfile::factory()->pending()->create([
        'city_id' => City::factory(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(LawyerVerification::class)
        ->call('verify', $profile->id);

    $action = AdminAction::query()->sole();

    expect($action->action)->toBe('lawyer.verify')
        ->and($action->admin_id)->toBe($this->admin->id)
        ->and($action->subject_type)->toBe(LawyerProfile::class)
        ->and((int) $action->subject_id)->toBe($profile->id);
});

it('records rejection reason in the audit meta', function () {
    $profile = LawyerProfile::factory()->pending()->create(['city_id' => City::factory()]);

    Livewire::actingAs($this->admin)
        ->test(LawyerVerification::class)
        ->set('rejection_reason', 'مدارک ناقص است')
        ->call('reject', $profile->id);

    $action = AdminAction::query()->where('action', 'lawyer.reject')->sole();

    expect($action->meta['reason'])->toBe('مدارک ناقص است');
});

it('records payment refunds and settings saves', function () {
    $appt = Appointment::create([
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
    ]);
    $payment = Payment::create([
        'appointment_id' => $appt->id, 'client_id' => $this->client->id,
        'amount_toman' => 100000, 'status' => PaymentStatus::RefundRequested,
        'authority' => 'A1',
    ]);

    Livewire::actingAs($this->admin)
        ->test(PaymentIndex::class)
        ->call('markRefunded', $payment->id);

    Livewire::actingAs($this->admin)
        ->test(SettingsManager::class)
        ->set('request_expiry_days', '14')
        ->call('save');

    expect(AdminAction::query()->pluck('action')->toArray())
        ->toBe(['payment.refund', 'settings.save']);
});

it('guards the activity log page', function () {
    $this->get(route('admin.activity'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->client()->create())->get(route('admin.activity'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.activity'))->assertOk();
});
