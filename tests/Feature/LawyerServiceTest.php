<?php

use App\Enums\ConsultationType;
use App\Livewire\Dashboard\Lawyer\ServiceManager;
use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->owner = User::factory()->lawyer()->create();
    $this->intruder = User::factory()->lawyer()->create();

    $this->ownerProfile = LawyerProfile::factory()->create(['user_id' => $this->owner->id]);
    $this->foreignProfile = LawyerProfile::factory()->create(['user_id' => $this->intruder->id]);

    $this->foreignService = $this->foreignProfile->services()->create([
        'title' => 'مشاوره بیگانه',
        'consultation_type' => ConsultationType::Phone,
        'duration_minutes' => 30,
    ]);
});

it('lets a lawyer manage only their own services', function () {
    // findOwnedService() scopes by the acting lawyer's own profile -> 404.
    Livewire::actingAs($this->owner)
        ->test(ServiceManager::class);

    $component = Livewire::actingAs($this->owner)->test(ServiceManager::class);

    expect(fn () => $component->call('delete', $this->foreignService->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => $component->call('toggle', $this->foreignService->id))
        ->toThrow(ModelNotFoundException::class);

    expect(fn () => $component->call('edit', $this->foreignService->id))
        ->toThrow(ModelNotFoundException::class);

    // The foreign service must be untouched.
    expect($this->foreignService->fresh()->title)->toBe('مشاوره بیگانه');
});

it('creates a service for the authenticated lawyer only', function () {
    Livewire::actingAs($this->owner)
        ->test(ServiceManager::class)
        ->set('title', 'مشاوره آنلاین')
        ->set('consultation_type', 'online')
        ->set('duration_minutes', 45)
        ->call('create')
        ->assertHasNoErrors();

    expect($this->ownerProfile->services()->count())->toBe(1)
        ->and($this->foreignProfile->services()->count())->toBe(1); // untouched
});

it('validates service input', function () {
    Livewire::actingAs($this->owner)
        ->test(ServiceManager::class)
        ->set('title', '')
        ->set('consultation_type', 'carrier_pigeon')
        ->call('create')
        ->assertHasErrors(['title', 'consultation_type']);

    expect($this->ownerProfile->services()->count())->toBe(0);
});
