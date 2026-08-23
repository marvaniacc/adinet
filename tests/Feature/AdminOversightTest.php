<?php

use App\Enums\ConsultationRequestStatus;
use App\Livewire\Client\ProfileEdit;
use App\Livewire\Dashboard\Admin\LawyersIndex;
use App\Livewire\Dashboard\Admin\RequestOversight;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->client = User::factory()->client()->create(['mobile' => '09300000001']);
    $this->lawyerUser = User::factory()->lawyer()->create();
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);
});

it('guards all four admin oversight pages', function () {
    $routes = ['admin.lawyers.index', 'admin.clients.index', 'admin.requests.index', 'admin.appointments.index'];

    // Guests first — actingAs below persists across requests in one test.
    foreach ($routes as $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    }

    // Clients and lawyers are forbidden everywhere.
    foreach ($routes as $route) {
        $this->actingAs(User::factory()->client()->create())->get(route($route))->assertForbidden();
        $this->actingAs(User::factory()->lawyer()->create())->get(route($route))->assertForbidden();
    }

    // Admin passes everywhere.
    $this->actingAs($this->admin);
    foreach ($routes as $route) {
        $this->get(route($route))->assertOk();
    }
});

it('shows lawyers with search by name or mobile', function () {
    $this->actingAs($this->admin);

    Livewire::test(LawyersIndex::class)
        ->assertSee($this->lawyer->display_name);

    // Search by mobile fragment.
    $mobileFragment = substr($this->lawyerUser->mobile, 0, 4);

    Livewire::withQueryParams(['search' => $mobileFragment])
        ->test(LawyersIndex::class)
        ->assertSee($this->lawyer->display_name);

    // Search with garbage finds nothing.
    Livewire::withQueryParams(['search' => '@@@@'])
        ->test(LawyersIndex::class)
        ->assertDontSee($this->lawyer->display_name);
});

it('lists clients with request counts', function () {
    ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 's', 'description' => str_repeat('d', 40),
        'status' => ConsultationRequestStatus::Pending,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.clients.index'))
        ->assertOk()
        ->assertSee($this->client->fullName());
});

it('lets admin inspect consultation requests with expandable details', function () {
    $cr = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 'موضوع بازرسی',
        'description' => str_repeat('ت', 50),
        'status' => ConsultationRequestStatus::Pending,
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(RequestOversight::class)
        ->assertSee('موضوع بازرسی');

    // Expand shows the description.
    $component->call('toggle', $cr->id)->assertSee(str_repeat('ت', 50));
});

it('lets the client edit their own profile names', function () {
    Livewire::actingAs($this->client)
        ->test(ProfileEdit::class)
        ->set('first_name', 'محمدرضا')
        ->set('last_name', 'کریمی')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->client->fresh()->first_name)->toBe('محمدرضا')
        ->and($this->client->fresh()->fullName())->toContain('کریمی');
});

it('validates required names on client profile', function () {
    Livewire::actingAs($this->client)
        ->test(ProfileEdit::class)
        ->set('first_name', '')
        ->call('save')
        ->assertHasErrors(['first_name']);
});
