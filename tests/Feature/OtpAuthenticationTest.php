<?php

use App\Contracts\SmsProvider;
use App\Livewire\Auth\OtpLogin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportTesting\Testable;
use Tests\Support\RecordingSmsProvider;

beforeEach(function () {
    $this->sms = new RecordingSmsProvider;
    $this->app->instance(SmsProvider::class, $this->sms);
});

function loginThroughComponent(RecordingSmsProvider $sms, string $mobile, string $intent = 'client'): Testable
{
    $component = Livewire::test(OtpLogin::class, ['intent' => $intent])
        ->set('mobile', $mobile)
        ->call('sendOtp');

    if ($component->get('step') !== 'code') {
        return $component;
    }

    return $component->set('code', $sms->lastCode())->call('verifyOtp');
}

it('shows the mobile step on the login page for guests', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('شماره موبایل');
});

it('redirects authenticated users away from login', function () {
    Auth::login(User::factory()->create());

    Livewire::test(OtpLogin::class)
        ->assertRedirect(route('dashboard'));
});

it('rejects an invalid mobile number without sending sms', function () {
    Livewire::test(OtpLogin::class)
        ->set('mobile', '12345')
        ->call('sendOtp')
        ->assertHasErrors(['mobile']);

    expect($this->sms->sent)->toBeEmpty()
        ->and(User::count())->toBe(0);
});

it('moves to the code step after a valid request', function () {
    Livewire::test(OtpLogin::class)
        ->set('mobile', '09123456789')
        ->call('sendOtp')
        ->assertSet('step', 'code');

    expect($this->sms->sent)->toHaveCount(1);
});

it('blocks sms pumping across different numbers from one ip', function () {
    foreach (range(1, 10) as $i) {
        Livewire::test(OtpLogin::class)
            ->set('mobile', sprintf('09%09d', $i))
            ->call('sendOtp')
            ->assertSet('step', 'code');
    }

    expect($this->sms->sent)->toHaveCount(10);

    // The 11th distinct number from the same IP is refused.
    Livewire::test(OtpLogin::class)
        ->set('mobile', '09399999999')
        ->call('sendOtp')
        ->assertHasErrors(['mobile']);

    expect($this->sms->sent)->toHaveCount(10);
});

it('rejects a wrong code and keeps the guest logged out', function () {
    Livewire::test(OtpLogin::class)
        ->set('mobile', '09123456789')
        ->call('sendOtp')
        ->assertSet('step', 'code')
        ->set('code', '000000')
        ->call('verifyOtp')
        ->assertHasErrors(['code']);

    expect(Auth::check())->toBeFalse()
        ->and(User::count())->toBe(0);
});

it('creates a client account and logs in with a correct code', function () {
    loginThroughComponent($this->sms, '09123456789')
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->role)->toBe(User::ROLE_CLIENT)
        ->and(Auth::user()->mobile)->toBe('09123456789');
});

it('assigns the lawyer role when registering through the lawyer intent', function () {
    loginThroughComponent($this->sms, '0935 123 4567', 'lawyer')
        ->assertRedirect(route('dashboard.lawyer.index'));

    expect(Auth::user()->role)->toBe(User::ROLE_LAWYER)
        ->and(Auth::user()->mobile)->toBe('09351234567');
});

it('passes the lawyer intent to the component through the real registration route', function () {
    // Guards against the regression where mount() silently defaulted
    // to 'client' because the route never supplied the parameter.
    $this->get(route('lawyer.register'))
        ->assertOk()
        ->assertSee('registrationRole&quot;:&quot;lawyer', false);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('registrationRole&quot;:&quot;client', false);
});

it('redirects each role to its own dashboard after login', function () {
    $client = User::factory()->create(); // default role: client
    $lawyer = User::factory()->lawyer()->create();
    $admin = User::factory()->admin()->create();

    expect($client->dashboardUrl())->toBe(route('dashboard'))
        ->and($lawyer->dashboardUrl())->toBe(route('dashboard.lawyer.index'))
        ->and($admin->dashboardUrl())->toBe(route('admin.dashboard'));

    // Already-authenticated visitors of /login are sent to their own dashboard.
    Livewire::actingAs($admin)->test(OtpLogin::class)->assertRedirect($admin->dashboardUrl());
});

it('enforces server-side access to each role dashboard', function () {
    // Guests first: actingAs() below would otherwise persist across requests.
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('dashboard.lawyer.index'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

    $client = User::factory()->create();
    $lawyer = User::factory()->lawyer()->create();
    $admin = User::factory()->admin()->create();

    // Client dashboard: clients only.
    $this->actingAs($client)->get(route('dashboard'))->assertOk();
    $this->actingAs($lawyer)->get(route('dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('dashboard'))->assertForbidden();

    // Lawyer dashboard home: lawyers only.
    $this->actingAs($lawyer)->get(route('dashboard.lawyer.index'))->assertOk();
    $this->actingAs($client)->get(route('dashboard.lawyer.index'))->assertForbidden();

    // Admin dashboard home: admins only.
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($lawyer)->get(route('admin.dashboard'))->assertForbidden();
});

it('logs an existing user into the same account regardless of number format', function () {
    $existing = User::factory()->create(['mobile' => '09123456789']);

    loginThroughComponent($this->sms, '+989123456789')
        ->assertRedirect(route('dashboard'));

    expect(Auth::id())->toBe($existing->id)
        ->and(User::query()->where('mobile', '09123456789')->count())->toBe(1);
});

it('logs the user out and invalidates the session', function () {
    Auth::login(User::factory()->create());

    $this->post(route('logout'))->assertRedirect(route('home'));

    expect(Auth::check())->toBeFalse();
});

it('protects the dashboard behind authentication', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});
