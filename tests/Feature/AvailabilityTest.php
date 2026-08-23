<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Livewire\Dashboard\Lawyer\AvailabilityIndex;
use App\Models\Appointment;
use App\Models\AvailabilitySlot;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\User;
use App\Services\SlotGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->lawyerUser = User::factory()->lawyer()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);
});

it('generates 30-minute bookable slots from a weekly availability window', function () {
    // Every Sunday (0) from 10:00 to 12:00 -> 4 slots of 30 minutes.
    AvailabilitySlot::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'weekday' => 0,
        'start_time' => '10:00',
        'end_time' => '12:00',
        'is_active' => true,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-08-23 09:00')); // Sunday

    $slots = app(SlotGenerator::class)->upcomingFor($this->lawyer);

    expect($slots->count())->toBeGreaterThan(8)
        ->and($slots->first()['time'])->toBe('10:00')
        ->and($slots->contains(fn ($s) => $s['time'] === '11:30'))->toBeTrue()
        ->and($slots->contains(fn ($s) => $s['time'] === '12:00'))->toBeFalse(); // end is exclusive

    Carbon::setTestNow();
});

it('excludes booked appointments and past times', function () {
    AvailabilitySlot::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'weekday' => now()->dayOfWeek,
        'start_time' => '10:00',
        'end_time' => '12:00',
    ]);

    $bookingTime = now()->addDays(7)->setTime(10, 30)->startOfMinute();

    Appointment::create([
        'consultation_request_id' => ConsultationRequest::create([
            'lawyer_profile_id' => $this->lawyer->id,
            'client_id' => User::factory()->create()->id,
            'subject' => 'x', 'description' => str_repeat('d', 40),
            'status' => ConsultationRequestStatus::Accepted,
            'decided_at' => now(),
        ])->id,
        'client_id' => User::factory()->create()->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'scheduled_at' => $bookingTime,
        'duration_minutes' => 60,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-08-23 09:00'));

    $times = app(SlotGenerator::class)->upcomingFor($this->lawyer)
        ->filter(fn ($s) => $s['datetime']->equalTo($bookingTime));

    expect($times)->toBeEmpty();

    Carbon::setTestNow();
});

it('manages availability through the lawyer dashboard', function () {
    Livewire::actingAs($this->lawyerUser)
        ->test(AvailabilityIndex::class)
        ->set('weekday', '6') // Saturday
        ->set('start_time', '16:00')
        ->set('end_time', '18:00')
        ->call('save')
        ->assertHasNoErrors();

    $slot = AvailabilitySlot::query()->sole();

    expect($slot->weekday)->toBe(6)
        ->and($slot->start_time)->toBe('16:00');

    // End before start is rejected.
    Livewire::actingAs($this->lawyerUser)
        ->test(AvailabilityIndex::class)
        ->set('weekday', '6')
        ->set('start_time', '20:00')
        ->set('end_time', '18:00')
        ->call('save')
        ->assertHasErrors(['end_time']);

    // Toggle + delete.
    Livewire::actingAs($this->lawyerUser)
        ->test(AvailabilityIndex::class)
        ->call('toggle', $slot->id);

    expect($slot->fresh()->is_active)->toBeFalse();

    Livewire::actingAs($this->lawyerUser)
        ->test(AvailabilityIndex::class)
        ->call('delete', $slot->id);

    expect(AvailabilitySlot::count())->toBe(0);
});
