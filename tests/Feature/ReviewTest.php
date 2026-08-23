<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Enums\ReviewStatus;
use App\Livewire\Client\ReviewCreate;
use App\Livewire\Dashboard\Admin\ReviewModeration;
use App\Livewire\Dashboard\Lawyer\AppointmentIndex;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        'subject' => 'موضوع آزمون نظر',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    $this->appointment = Appointment::create([
        'consultation_request_id' => $this->request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'scheduled_at' => now()->subDay(),
        'duration_minutes' => 30,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);
});

it('completes the consultation request when the lawyer completes the appointment', function () {
    Livewire::actingAs($this->lawyerUser)
        ->test(AppointmentIndex::class)
        ->call('mark', $this->appointment->id, 'completed');

    expect($this->appointment->fresh()->status)->toBe(AppointmentStatus::Completed)
        ->and($this->request->fresh()->status)->toBe(ConsultationRequestStatus::Completed);
});

it('lets the client review a completed consultation once', function () {
    $this->request->forceFill(['status' => ConsultationRequestStatus::Completed])->save();

    Livewire::actingAs($this->client)
        ->test(ReviewCreate::class, ['requestId' => $this->request->id])
        ->set('rating', 5)
        ->set('comment', 'مشاوره بسیار دقیق و صادقانه بود؛ ممنون.')
        ->call('store')
        ->assertHasNoErrors()
        ->assertRedirect(route('reviews.index'));

    $review = Review::query()->sole();

    expect($review->rating)->toBe(5)
        ->and($review->status)->toBe(ReviewStatus::Pending)
        ->and($review->lawyer_profile_id)->toBe($this->lawyer->id);

    // Second attempt is refused (unique per consultation).
    $this->actingAs($this->client)
        ->get(route('reviews.create', ['requestId' => $this->request->id]))
        ->assertForbidden();

    expect(Review::count())->toBe(1);
});

it('forbids reviewing before completion and by non-owners', function () {
    // Not completed yet.
    Livewire::actingAs($this->client)
        ->test(ReviewCreate::class, ['requestId' => $this->request->id])
        ->assertForbidden();

    // Completed, but a stranger client cannot use someone else's request id.
    $this->request->forceFill(['status' => ConsultationRequestStatus::Completed])->save();

    expect(fn () => Livewire::actingAs(User::factory()->client()->create())
        ->test(ReviewCreate::class, ['requestId' => $this->request->id]))
        ->toThrow(ModelNotFoundException::class);

    expect(Review::count())->toBe(0);
});

it('validates the rating range and comment length', function () {
    $this->request->forceFill(['status' => ConsultationRequestStatus::Completed])->save();

    Livewire::actingAs($this->client)
        ->test(ReviewCreate::class, ['requestId' => $this->request->id])
        ->set('rating', 7)
        ->set('comment', str_repeat('ک', 2500))
        ->call('store')
        ->assertHasErrors(['rating', 'comment']);

    expect(Review::count())->toBe(0);
});

it('shows only approved reviews publicly with the average rating', function () {
    $this->request->forceFill(['status' => ConsultationRequestStatus::Completed])->save();

    Review::create([
        'consultation_request_id' => $this->request->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'rating' => 4,
        'comment' => 'بررسی پرونده دقیق بود.',
        'status' => ReviewStatus::Pending,
    ]);

    // Pending reviews are hidden.
    $this->get(route('lawyers.show', $this->lawyer->slug))
        ->assertOk()
        ->assertDontSee('دقیق بود');

    // Admin approves -> becomes public.
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ReviewModeration::class)
        ->call('decide', Review::query()->sole()->id, 'approve');

    expect(Review::query()->sole()->status)->toBe(ReviewStatus::Approved);

    $this->get(route('lawyers.show', $this->lawyer->slug))
        ->assertOk()
        ->assertSee('بررسی پرونده دقیق بود.')
        ->assertSee('نظرات موکلان');
});

it('rejects a review so it never appears publicly', function () {
    $this->request->forceFill(['status' => ConsultationRequestStatus::Completed])->save();

    $review = Review::create([
        'consultation_request_id' => $this->request->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'rating' => 2,
        'comment' => 'نامرتبط.',
        'status' => ReviewStatus::Pending,
    ]);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ReviewModeration::class)
        ->call('decide', $review->id, 'reject');

    expect($review->fresh()->status)->toBe(ReviewStatus::Rejected);

    $this->get(route('lawyers.show', $this->lawyer->slug))->assertDontSee('نامرتبط');
});

it('guards moderation to admins only', function () {
    $this->get(route('admin.reviews'))->assertRedirect(route('login'));

    $this->actingAs($this->client)->get(route('admin.reviews'))->assertForbidden();
});
