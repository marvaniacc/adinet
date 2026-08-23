<?php

use App\Enums\LawyerStatus;
use App\Livewire\Dashboard\Admin\LawyerVerification;
use App\Livewire\Dashboard\Lawyer\ProfileEdit;
use App\Livewire\Public\LawyerList;
use App\Models\City;
use App\Models\LawyerProfile;
use App\Models\Specialty;
use App\Models\User;

beforeEach(function () {
    $this->city = City::factory()->create();
});

function lawyerProfileFor(User $user, array $attributes = []): LawyerProfile
{
    return LawyerProfile::factory()
        ->create([...$attributes, 'user_id' => $user->id, 'city_id' => City::factory()]);
}

it('guards the lawyer dashboard pages', function () {
    // Guest -> redirected to login.
    $this->get(route('dashboard.lawyer.profile'))->assertRedirect(route('login'));

    // Client -> forbidden.
    $this->actingAs(User::factory()->client()->create())
        ->get(route('dashboard.lawyer.profile'))
        ->assertForbidden();

    // Lawyer -> allowed.
    $this->actingAs(User::factory()->lawyer()->create())
        ->get(route('dashboard.lawyer.profile'))
        ->assertOk();
});

it('guards the admin verification panel', function () {
    $this->get(route('admin.lawyers.verification'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->lawyer()->create())
        ->get(route('admin.lawyers.verification'))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.lawyers.verification'))
        ->assertOk();
});

it('submits a draft profile for review and admin verifies it into the marketplace', function () {
    $lawyer = User::factory()->lawyer()->create();
    $profile = lawyerProfileFor($lawyer, [
        'status' => LawyerStatus::Draft,
        'display_name' => 'وکیل آزمون',
        'bar_association' => 'کانون وکلای دادگستری مرکز',
        'license_number' => '12345',
        'bio' => str_repeat('ت', 60),
        'phone' => '02112345678',
        'years_of_experience' => 5,
    ]);
    $profile->specialties()->attach(Specialty::factory()->create());

    // Lawyer submits for review from their own dashboard component.
    Livewire::actingAs($lawyer)
        ->test(ProfileEdit::class)
        ->call('submitForReview');

    expect($profile->fresh()->status)->toBe(LawyerStatus::PendingReview);

    // Admin verifies through the admin panel.
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(LawyerVerification::class)
        ->call('verify', $profile->id);

    expect($profile->fresh()->status)->toBe(LawyerStatus::Verified)
        ->and($profile->fresh()->verified_at)->not->toBeNull();

    // Now visible on the public listing.
    Livewire::test(LawyerList::class)
        ->assertSee('وکیل آزمون');
});

it('rejects with a reason the lawyer can see and resubmits', function () {
    $lawyer = User::factory()->lawyer()->create();
    // A genuinely submitted profile always had valid data incl. a specialty.
    $profile = lawyerProfileFor($lawyer, ['status' => LawyerStatus::PendingReview]);
    $profile->specialties()->attach(Specialty::factory()->create());
    $profile->specialties()->attach(Specialty::factory()->create()); // complete profile

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(LawyerVerification::class)
        ->set('rejection_reason', 'شماره پروانه معتبر نیست؛ تصویر پروانه را بارگذاری کنید.')
        ->call('reject', $profile->id)
        ->assertHasNoErrors();

    expect($profile->fresh()->status)->toBe(LawyerStatus::Rejected)
        ->and($profile->fresh()->rejection_reason)->toContain('پروانه');

    // Lawyer sees the rejection reason in their dashboard.
    Livewire::actingAs($lawyer)
        ->test(ProfileEdit::class)
        ->assertSee('شماره پروانه معتبر نیست');

    // And can resubmit after rejection.
    Livewire::actingAs($lawyer)
        ->test(ProfileEdit::class)
        ->call('submitForReview');

    expect($profile->fresh()->status)->toBe(LawyerStatus::PendingReview);
});

it('suspends a verified lawyer who disappears from the public listing', function () {
    $profile = LawyerProfile::factory()->verified()->create(['display_name' => 'وکیل معلق‌شدنی']);

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(LawyerVerification::class)
        ->call('suspend', $profile->id);

    expect($profile->fresh()->status)->toBe(LawyerStatus::Suspended);

    Livewire::test(LawyerList::class)
        ->assertDontSee('وکیل معلق‌شدنی');

    // Reinstate restores public visibility.
    Livewire::actingAs($admin)
        ->test(LawyerVerification::class)
        ->call('reinstate', $profile->id);

    expect($profile->fresh()->status)->toBe(LawyerStatus::Verified);
});
