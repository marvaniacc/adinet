<?php

use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\LawyerStatus;
use App\Livewire\Client\RequestCreate;
use App\Livewire\Dashboard\Lawyer\RequestIndex;
use App\Livewire\Messages\Chat;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\Conversation;
use App\Models\LawyerProfile;
use App\Models\Message;
use App\Models\User;
use App\Support\PersianDate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->lawyerUser = User::factory()->lawyer()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);
    $this->client = User::factory()->client()->create();

    // Default billable service: 60 minutes (used by conflict math).
    $this->service = $this->lawyer->services()->create([
        'title' => 'مشاوره تلفنی',
        'consultation_type' => ConsultationType::Phone,
        'duration_minutes' => 60,
        'is_active' => true,
    ]);
});

function makePendingRequest(LawyerProfile $lawyer, User $client, array $attrs = []): ConsultationRequest
{
    return ConsultationRequest::create([
        ...$attrs,
        'lawyer_profile_id' => $lawyer->id,
        'client_id' => $client->id,
        'subject' => 'موضوع آزمون',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);
}

function acceptAt($test, ConsultationRequest $request, string $gregorianDate, string $time): void
{
    $jalali = PersianDate::toJalali(
        (int) substr($gregorianDate, 0, 4),
        (int) substr($gregorianDate, 5, 2),
        (int) substr($gregorianDate, 8, 2),
    );
    $jalaliStr = implode('/', array_map(fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT), $jalali));

    Livewire::actingAs($request->lawyerProfile->user)
        ->test(RequestIndex::class)
        ->call('openAccept', $request->id)
        ->set('accept_date_jalali', $jalaliStr)
        ->set('accept_time', $time)
        ->call('accept', $request->id);
}

it('blocks a suspended lawyer from accepting, rejecting or messaging', function () {
    $request = makePendingRequest($this->lawyer, $this->client);
    $conversation = Conversation::create([
        'consultation_request_id' => $request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
    ]);

    $this->lawyer->forceFill(['status' => LawyerStatus::Suspended])->save();
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00'));

    acceptAt($this, $request, '2026-09-05', '10:00');

    Livewire::actingAs($this->lawyerUser)->test(RequestIndex::class)
        ->call('openReject', $request->id)
        ->set('rejection_reason', 'ظرفیت ندارم')
        ->call('reject', $request->id);

    expect($request->fresh()->status)->toBe(ConsultationRequestStatus::Pending);

    // Livewire turns action-level denial into an error response, so we
    // assert on OUTCOME: no message may be persisted.
    $before = Message::count();

    Livewire::actingAs($this->lawyerUser)
        ->test(Chat::class, ['conversationId' => $conversation->id])
        ->set('body', 'پیام از وکیل تعلیق‌شده')
        ->call('send');

    expect(Message::count())->toBe($before);

    Carbon::setTestNow();
});

it('prevents accepting into a time that overlaps another scheduled appointment', function () {
    Carbon::setTestNow(Carbon::parse('2026-09-01 09:00'));

    $firstClient = User::factory()->client()->create();
    $first = makePendingRequest($this->lawyer, $firstClient, ['service_id' => $this->service->id]);
    acceptAt($this, $first, '2026-09-05', '10:00'); // 10:00–11:00 (60 min)

    $secondClient = User::factory()->client()->create();
    $second = makePendingRequest($this->lawyer, $secondClient);

    // Overlap attempt at 10:30.
    acceptAt($this, $second, '2026-09-05', '10:30');
    expect($second->fresh()->status)->toBe(ConsultationRequestStatus::Pending)
        ->and(Appointment::count())->toBe(1);

    // Same day, non-overlapping slot succeeds.
    acceptAt($this, $second, '2026-09-05', '12:00');
    expect($second->fresh()->status)->toBe(ConsultationRequestStatus::Accepted)
        ->and(Appointment::count())->toBe(2);

    Carbon::setTestNow();
});

it('blocks duplicate open requests per lawyer and allows after closure', function () {
    $componentFor = fn (LawyerProfile $lp) => Livewire::actingAs($this->client)
        ->test(RequestCreate::class, ['slug' => $lp->slug]);

    $c1 = $componentFor($this->lawyer)
        ->set('service_id', $this->service->id)
        ->set('subject', 'موضوع اول آزمون')
        ->set('description', str_repeat('د', 40))
        ->call('submit');
    expect(ConsultationRequest::count())->toBe(1);

    // Duplicate open request to the SAME lawyer refused.
    $c1->call('submit')->assertHasErrors(['subject']);
    expect(ConsultationRequest::count())->toBe(1);

    // A different lawyer still receives it.
    $other = LawyerProfile::factory()->verified()->create(['city_id' => City::factory()]);
    $componentFor($other)
        ->set('service_id', $other->services()->create([
            'title' => 'خدمت دیگر', 'consultation_type' => ConsultationType::Online,
            'duration_minutes' => 30, 'is_active' => true,
        ])->id)
        ->set('subject', 'برای وکیل دیگر')
        ->set('description', str_repeat('د', 40))
        ->call('submit');
    expect(ConsultationRequest::count())->toBe(2);
});

it('rate limits request creation to five per hour per client', function () {
    $others = LawyerProfile::factory()->count(6)->verified()->create(['city_id' => City::factory()]);

    foreach ($others as $i => $lp) {
        $svc = $lp->services()->create([
            'title' => "s$i", 'consultation_type' => ConsultationType::Phone,
            'duration_minutes' => 30, 'is_active' => true,
        ]);

        $component = Livewire::actingAs($this->client)
            ->test(RequestCreate::class, ['slug' => $lp->slug])
            ->set('service_id', $svc->id)
            ->set('subject', "درخواست شماره $i آزمون")
            ->set('description', str_repeat('د', 40))
            ->call('submit');

        if ($i < 5) {
            $component->assertHasNoErrors();
        } else {
            $component->assertHasErrors(['subject']);
        }
    }

    expect(ConsultationRequest::count())->toBe(5);
});
