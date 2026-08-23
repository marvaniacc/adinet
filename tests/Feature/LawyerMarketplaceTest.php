<?php

use App\Enums\ConsultationType;
use App\Enums\LawyerStatus;
use App\Livewire\Public\LawyerList;
use App\Models\City;
use App\Models\LawyerProfile;
use App\Models\Specialty;

beforeEach(function () {
    $this->cities = City::factory()->count(2)->create();
    $this->specialties = Specialty::factory()->count(2)->create();
});

/**
 * Deterministic lawyer fixture: given status, city and exactly one
 * active phone-consultation service under one given specialty.
 */
function lawyerIn(City $city, array $attributes = [], ?Specialty $specialty = null): LawyerProfile
{
    $lawyer = LawyerProfile::factory()
        ->create([...$attributes, 'city_id' => $city->id]);

    $lawyer->specialties()->attach(
        $specialty ?? Specialty::factory()->create()
    );

    $lawyer->services()->create([
        'title' => 'مشاوره تلفنی',
        'consultation_type' => ConsultationType::Phone,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    return $lawyer;
}

it('lists only verified lawyers publicly', function () {
    $visible = lawyerIn($this->cities[0], ['status' => LawyerStatus::Verified]);
    lawyerIn($this->cities[0], ['status' => LawyerStatus::Draft]);
    lawyerIn($this->cities[0], ['status' => LawyerStatus::PendingReview]);

    Livewire::test(LawyerList::class)
        ->assertSee($visible->display_name)
        ->assertDontSee('پیش‌نویس');
});

it('shows a verified lawyer profile page', function () {
    $lawyer = lawyerIn($this->cities[0], ['status' => LawyerStatus::Verified]);

    $this->get(route('lawyers.show', $lawyer->slug))
        ->assertOk()
        ->assertSee($lawyer->display_name)
        ->assertSee($lawyer->services()->first()->title);
});

it('hides unverified profiles from the public but lets the owner preview', function () {
    $draft = lawyerIn($this->cities[0], ['status' => LawyerStatus::Draft]);

    $this->get(route('lawyers.show', $draft->slug))->assertNotFound();

    $this->actingAs($draft->user)
        ->get(route('lawyers.show', $draft->slug))
        ->assertOk()
        ->assertSee('پیش‌نمایش خصوصی');
});

it('filters lawyers by city', function () {
    $inTehran = lawyerIn($this->cities[0], ['status' => LawyerStatus::Verified]);
    $other = lawyerIn($this->cities[1], ['status' => LawyerStatus::Verified]);

    Livewire::withQueryParams(['city' => (string) $this->cities[0]->id])
        ->test(LawyerList::class)
        ->assertSee($inTehran->display_name)
        ->assertDontSee($other->display_name);
});

it('filters lawyers by specialty slug', function () {
    $lawyerA = lawyerIn($this->cities[0], ['status' => LawyerStatus::Verified]);
    $lawyerB = lawyerIn($this->cities[0], ['status' => LawyerStatus::Verified]);

    // Assign disjoint specialties deterministically.
    [$s1, $s2] = $this->specialties;
    $lawyerA->specialties()->sync([$s1->id]);
    $lawyerB->specialties()->sync([$s2->id]);

    Livewire::withQueryParams(['specialty' => $s1->slug])
        ->test(LawyerList::class)
        ->assertSee($lawyerA->display_name)
        ->assertDontSee($lawyerB->display_name);
});

it('ignores crafted filter values instead of erroring', function () {
    lawyerIn($this->cities[0], ['status' => LawyerStatus::Verified]);

    Livewire::withQueryParams(['city' => '999999', 'type' => 'hack'])
        ->test(LawyerList::class)
        ->assertOk();
});
