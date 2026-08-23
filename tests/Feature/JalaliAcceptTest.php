<?php

use App\Enums\ConsultationRequestStatus;
use App\Livewire\Dashboard\Lawyer\RequestIndex;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\User;
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

    $this->request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 'موضوع پذیرش شمسی',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);
});

it('accepts a Jalali date + time and stores the correct Gregorian datetime', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-23 09:00'));

    Livewire::actingAs($this->lawyerUser)
        ->test(RequestIndex::class)
        ->call('openAccept', $this->request->id)
        ->set('accept_date_jalali', '۱۴۰۵/۰۶/۲۵') // persian digits
        ->set('accept_time', '16:45')
        ->call('accept', $this->request->id)
        ->assertHasNoErrors();

    expect($this->request->fresh()->status)->toBe(ConsultationRequestStatus::Accepted)
        ->and($this->request->fresh()->appointment->scheduled_at->format('Y-m-d H:i'))->toBe('2026-09-16 16:45');

    Carbon::setTestNow();
});

it('rejects an invalid jalali date with a friendly error', function () {
    Livewire::actingAs($this->lawyerUser)
        ->test(RequestIndex::class)
        ->call('openAccept', $this->request->id)
        ->set('accept_date_jalali', '1405/13/99')
        ->set('accept_time', '10:00')
        ->call('accept', $this->request->id)
        ->assertHasErrors(['accept_date_jalali']);

    expect($this->request->fresh()->status)->toBe(ConsultationRequestStatus::Pending)
        ->and($this->request->fresh()->appointment)->toBeNull();
});
