<?php

use App\Contracts\SmsProvider;
use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Livewire\Client\RequestCreate;
use App\Livewire\Client\RequestIndex;
use App\Livewire\Dashboard\Lawyer\RequestIndex as LawyerRequestIndex;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\Specialty;
use App\Models\User;
use App\Support\JalaliDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\RecordingSmsProvider;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->sms = new RecordingSmsProvider;
    $this->app->instance(SmsProvider::class, $this->sms);

    $this->city = City::factory()->create();
    $this->specialty = Specialty::factory()->create();

    $this->lawyerUser = User::factory()->lawyer()->create(['mobile' => '09111111111']);
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => $this->city->id,
    ]);
    $this->lawyer->specialties()->attach($this->specialty);

    $this->service = $this->lawyer->services()->create([
        'title' => 'مشاوره تلفنی',
        'consultation_type' => ConsultationType::Phone,
        'duration_minutes' => 30,
        'price_amount_minor' => 500000,
        'is_active' => true,
    ]);

    $this->client = User::factory()->client()->create(['mobile' => '09222222222']);
});

function createRequestViaPage(LawyerProfile $lawyer, array $overrides = [])
{
    return Livewire::actingAs(User::find($overrides['client_id'] ?? auth()->id() ?? throw new RuntimeException('no user')))
        ->test(RequestCreate::class, ['slug' => $lawyer->slug])
        ->set('service_id', $overrides['service_id'] ?? null)
        ->set('subject', $overrides['subject'] ?? 'اختلاف در قرارداد اجاره')
        ->set('description', $overrides['description'] ?? str_repeat('ت', 40))
        ->call('submit');
}

it('lets a verified-lawyer client submit a consultation request and notifies the lawyer', function () {
    $component = Livewire::actingAs($this->client)->test(RequestCreate::class, ['slug' => $this->lawyer->slug]);

    $component->assertSet('profile.id', $this->lawyer->id)
        ->set('service_id', $this->service->id)
        ->set('subject', 'اختلاف در قرارداد اجاره')
        ->set('description', str_repeat('ت', 40))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.requests'));

    $request = ConsultationRequest::query()->sole();

    expect($request->status)->toBe(ConsultationRequestStatus::Pending)
        ->and($request->client_id)->toBe($this->client->id)
        ->and($request->lawyer_profile_id)->toBe($this->lawyer->id)
        ->and((int) $request->service_id)->toBe($this->service->id);

    // The lawyer was SMS-notified about the new request.
    expect(collect($this->sms->sent)->contains(fn ($s) => $s['mobile'] === $this->lawyerUser->mobile))->toBeTrue();
});

it('validates that the chosen service belongs to the requested lawyer', function () {
    $otherLawyer = LawyerProfile::factory()->verified()->create();
    $foreignService = $otherLawyer->services()->create([
        'title' => 'خدمت وکیل دیگر',
        'consultation_type' => ConsultationType::Online,
        'duration_minutes' => 60,
    ]);

    Livewire::actingAs($this->client)
        ->test(RequestCreate::class, ['slug' => $this->lawyer->slug])
        ->set('service_id', $foreignService->id)
        ->set('subject', 'موضوع تستی معتبر')
        ->set('description', str_repeat('ت', 40))
        ->call('submit')
        ->assertHasErrors(['service_id']);

    expect(ConsultationRequest::count())->toBe(0);
});

it('rejects past preferred dates and too-short descriptions', function () {
    Livewire::actingAs($this->client)
        ->test(RequestCreate::class, ['slug' => $this->lawyer->slug])
        ->set('service_id', $this->service->id)
        ->set('subject', 'موضوع تستی معتبر')
        ->set('description', 'کوتاه')
        ->set('preferred_date', now()->subDay()->toDateString())
        ->call('submit')
        ->assertHasErrors(['description', 'preferred_date']);
});

function acceptRequest(ConsultationRequest $request, string $jalaliDate, string $time): void
{
    Livewire::actingAs($request->lawyerProfile->user)
        ->test(LawyerRequestIndex::class)
        ->call('openAccept', $request->id)
        ->set('accept_date_jalali', $jalaliDate)
        ->set('accept_time', $time)
        ->call('accept', $request->id)
        ->assertHasNoErrors();
}

it('creates an appointment when the lawyer accepts and notifies the client', function () {
    $request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'service_id' => $this->service->id,
        'subject' => 'موضوع آزمون',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-09-01 10:00'));
    // 1405/06/14 == 2026-09-05
    acceptRequest($request, '1405/06/14', '15:30');

    $appointment = $request->fresh()->appointment;

    expect($request->fresh()->status)->toBe(ConsultationRequestStatus::Accepted)
        ->and($appointment)->not->toBeNull()
        ->and($appointment->client_id)->toBe($this->client->id)
        ->and($appointment->lawyer_profile_id)->toBe($this->lawyer->id)
        ->and((int) $appointment->service_id)->toBe($this->service->id)
        ->and($appointment->scheduled_at->format('Y-m-d H:i'))->toBe('2026-09-05 15:30')
        ->and($appointment->duration_minutes)->toBe(30)
        ->and($appointment->consultation_type)->toBe(ConsultationType::Phone)
        ->and($appointment->status)->toBe(AppointmentStatus::Scheduled);

    // Client received the acceptance SMS.
    expect(collect($this->sms->sent)->contains(fn ($s) => $s['mobile'] === $this->client->mobile && str_contains($s['message'], 'پذیرفت')))->toBeTrue();

    Carbon::setTestNow();
});

it('prevents a lawyer from accepting another lawyer\'s request or re-deciding a closed one', function () {
    $request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'service_id' => $this->service->id,
        'subject' => 'موضوع آزمون',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);

    // Foreign lawyer is scoped out entirely.
    $intruder = User::factory()->lawyer()->create();
    Livewire::actingAs($intruder)
        ->test(LawyerRequestIndex::class)
        ->call('accept', $request->id);

    expect(ConsultationRequest::query()->whereKey($request->id)->where('status', ConsultationRequestStatus::Accepted)->exists())->toBeFalse();

    // Owner accepts once; second decision attempt must not duplicate/change anything.
    acceptRequest(
        $request,
        JalaliDate::formatShort(now()->addDays(2)),
        now()->addDays(2)->format('H:i'),
    );

    Livewire::actingAs($this->lawyerUser)
        ->test(LawyerRequestIndex::class)
        ->call('reject', $request->id);

    expect($request->fresh()->status)->toBe(ConsultationRequestStatus::Accepted)
        ->and(Appointment::count())->toBe(1);
});

it('rejects with a reason and notifies the client', function () {
    $request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'service_id' => $this->service->id,
        'subject' => 'موضوع آزمون',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);

    Livewire::actingAs($this->lawyerUser)
        ->test(LawyerRequestIndex::class)
        ->call('openReject', $request->id)
        ->set('rejection_reason', 'در حال حاضر ظرفیت پذیرش ندارم.')
        ->call('reject', $request->id)
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe(ConsultationRequestStatus::Rejected)
        ->and($request->fresh()->rejection_reason)->toContain('ظرفیت');

    expect(collect($this->sms->sent)->contains(fn ($s) => $s['mobile'] === $this->client->mobile && str_contains($s['message'], 'رد کرد')))->toBeTrue();
});

it('cancelling an accepted request also cancels its scheduled appointment', function () {
    $request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'service_id' => $this->service->id,
        'subject' => 'موضوع آزمون',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    $appointment = Appointment::create([
        'consultation_request_id' => $request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'service_id' => $this->service->id,
        'scheduled_at' => now()->addDays(3),
        'duration_minutes' => 30,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);

    Livewire::actingAs($this->client)
        ->test(RequestIndex::class)
        ->call('cancel', $request->id);

    expect($request->fresh()->status)->toBe(ConsultationRequestStatus::Cancelled)
        ->and($appointment->fresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('expires stale pending requests via the command', function () {
    $stale = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'service_id' => $this->service->id,
        'subject' => 'قدیمی',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);
    $stale->forceFill(['created_at' => now()->subDays(8)])->save();

    $fresh = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'service_id' => $this->service->id,
        'subject' => 'تازه',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);

    $this->artisan('consultation:expire')->assertSuccessful();

    expect($stale->fresh()->status)->toBe(ConsultationRequestStatus::Expired)
        ->and($fresh->fresh()->status)->toBe(ConsultationRequestStatus::Pending);
});
