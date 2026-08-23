<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['mobile' => '09120000000']);
});

function renderDashboardFor($test, User $user, string $path): string
{
    return $test->actingAs($user)->get($path)->assertOk()->getContent();
}

it('renders a persistent dashboard header carrying the user info block', function () {
    $html = renderDashboardFor($this, $this->admin, route('admin.dashboard'));

    // Sticky header exists and carries the moved user block.
    expect(str_contains($html, 'sticky top-0 z-30'))->toBeTrue('header bar missing')
        ->and(str_contains($html, $this->admin->fullName()))->toBeTrue('user name missing from header')
        ->and(str_contains($html, '09120000000'))->toBeTrue('mobile missing from header');
});

it('removes the user info block from the sidebar', function () {
    $html = renderDashboardFor($this, $this->admin, route('admin.dashboard'));

    // account_circle used to mark the sidebar user block; it now lives
    // only in the header avatar.
    expect(substr_count($html, 'account_circle'))->toBe(0);
});

it('expands the sidebar via alpine container events, not css :hover width', function () {
    $html = renderDashboardFor($this, $this->admin, route('admin.dashboard'));

    // The flicker bug shipped `md:hover:w-64`; it must not come back.
    expect(str_contains($html, 'md:hover:w-64'))->toBeFalse('css hover-width returned')
        ->and(str_contains($html, 'railOpen = true'))->toBeTrue()
        ->and(str_contains($html, 'md:w-[76px]'))->toBeTrue();
});

it('marks only the current routes nav item active', function () {
    $extract = function (string $html): array {
        preg_match_all('/<a[^>]*aria-current="page"[^>]*>(.*?)<\/a>/s', $html, $m);

        return array_map(fn ($t) => trim(strip_tags($t)), $m[1] ?? []);
    };

    $lawyerHtml = renderDashboardFor($this, $this->admin, route('admin.lawyers.index'));
    file_put_contents('/tmp/opencode/dl.html', 'COUNT='.substr_count($lawyerHtml, 'aria-current')."\n".$lawyerHtml);
    $onLawyers = $extract($lawyerHtml);
    file_put_contents('/tmp/opencode/dl.txt', json_encode($onLawyers, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    expect(collect($onLawyers)->contains(fn ($t) => str_contains($t, 'وکلا')))->toBeTrue('وکلا should be active')
        ->and(collect($onLawyers)->contains(fn ($t) => str_contains($t, 'داشبورد')))->toBeFalse('first item wrongly stuck active');

    $onHome = $extract(renderDashboardFor($this, $this->admin, route('admin.dashboard')));
    expect(collect($onHome)->contains(fn ($t) => str_contains($t, 'داشبورد')))->toBeTrue()
        ->and(collect($onHome)->contains(fn ($t) => str_contains($t, 'وکلا')))->toBeFalse();
});
