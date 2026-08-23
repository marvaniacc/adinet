<?php

use App\Models\LawyerProfile;

it('generates a slug from persian display names', function () {
    expect(LawyerProfile::uniqueSlug('دکتر علی رضایی'))->toBe('دکتر-علی-رضایی');
});

it('suffixes duplicate slugs as collisions are persisted', function () {
    LawyerProfile::factory()->create(['display_name' => 'وکیل تست', 'slug' => 'وکیل-تست']);

    expect(LawyerProfile::uniqueSlug('وکیل تست'))->toBe('وکیل-تست-2');

    // Once the second lawyer registers with that slug, the next candidate advances.
    LawyerProfile::factory()->create(['display_name' => 'وکیل تست', 'slug' => 'وکیل-تست-2']);

    expect(LawyerProfile::uniqueSlug('وکیل تست'))->toBe('وکیل-تست-3');
});

it('ignores the current record when regenerating its own slug', function () {
    $profile = LawyerProfile::factory()->create(['display_name' => 'وکیل نمونه', 'slug' => 'وکیل-نمونه']);

    expect(LawyerProfile::uniqueSlug('وکیل نمونه', $profile->id))->toBe('وکیل-نمونه');
});

it('falls back to a generic slug for unusable names', function () {
    $slug = LawyerProfile::uniqueSlug('!!!');

    expect($slug)->toBe('lawyer')
        ->and(LawyerProfile::query()->where('slug', 'lawyer')->exists())->toBeFalse();
});
