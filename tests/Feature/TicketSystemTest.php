<?php

use App\Enums\TicketStatus;
use App\Livewire\Support\TicketIndex;
use App\Livewire\Support\TicketView;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->client = User::factory()->client()->create();
    $this->lawyer = User::factory()->lawyer()->create();
});

function createTicket(User $user, string $subject = 'مشکل در پرداخت'): Ticket
{
    $ticket = Ticket::create([
        'user_id' => $user->id,
        'subject' => $subject,
        'category' => 'billing',
        'status' => TicketStatus::Open,
        'last_reply_at' => now(),
    ]);
    $ticket->messages()->create(['user_id' => $user->id, 'body' => 'پرداخت انجام شد ولی نوبت ثبت نشد.']);

    return $ticket;
}

it('creates a ticket with the first message from the form', function () {
    Livewire::actingAs($this->client)
        ->test(TicketIndex::class)
        ->set('subject', 'خطا هنگام آپلود سند')
        ->set('category', 'technical')
        ->set('body', 'هنگام ارسال تصویر خطای فرمت می‌گیرم.')
        ->call('store')
        ->assertHasNoErrors()
        ->assertRedirect();

    $ticket = Ticket::query()->sole();

    expect($ticket->status)->toBe(TicketStatus::Open)
        ->and($ticket->category)->toBe('technical')
        ->and($ticket->messages()->count())->toBe(1)
        ->and($ticket->messages()->first()->user_id)->toBe($this->client->id);
});

it('validates ticket input', function () {
    Livewire::actingAs($this->client)
        ->test(TicketIndex::class)
        ->set('subject', 'ک') // too short
        ->set('body', 'کوتاه') // too short
        ->call('store')
        ->assertHasErrors(['subject', 'body']);

    expect(Ticket::count())->toBe(0);
});

it('lets only the owner and admins view a ticket thread', function () {
    $ticket = createTicket($this->client);

    $this->actingAs($this->client)->get(route('tickets.show', $ticket))->assertOk();
    $this->actingAs($this->admin)->get(route('tickets.show', $ticket))->assertOk();

    $stranger = User::factory()->client()->create();
    $this->actingAs($stranger)->get(route('tickets.show', $ticket))->assertForbidden();
});

it('flips status: admin reply answers, owner reply re-opens', function () {
    $ticket = createTicket($this->client);

    // Admin replies -> answered.
    Livewire::actingAs($this->admin)
        ->test(App\Livewire\Dashboard\Admin\TicketView::class, ['ticketId' => $ticket->id])
        ->set('body', 'مشکل را بررسی کردیم؛ تا ۲۴ ساعت حل می‌شود.')
        ->call('reply')
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Answered);

    // Owner replies again -> re-opened.
    Livewire::actingAs($this->client)
        ->test(TicketView::class, ['ticketId' => $ticket->id])
        ->set('body', 'هنوز مشکل دارم.')
        ->call('reply')
        ->assertHasNoErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Open)
        ->and($ticket->messages()->count())->toBe(3);
});

it('blocks replies on closed tickets for owners but allows admin', function () {
    $ticket = createTicket($this->client);
    $ticket->forceFill(['status' => TicketStatus::Closed])->save();
    $count = TicketMessage::count(); // 1 (first message)

    // Owner blocked by policy.
    Livewire::actingAs($this->client)
        ->test(TicketView::class, ['ticketId' => $ticket->id])
        ->set('body', 'یکی دیگر لطفاً')
        ->call('reply')
        ->assertHasErrors(['body']);

    // Admin can still post a closing note.
    Livewire::actingAs($this->admin)
        ->test(App\Livewire\Dashboard\Admin\TicketView::class, ['ticketId' => $ticket->id])
        ->set('body', 'بابت تاخیر عذرخواهی؛ موضوع پیگیری شد.')
        ->call('reply')
        ->assertHasNoErrors();

    expect(TicketMessage::count())->toBe($count + 1);
});

it('lets either party close, and only admins reopen', function () {
    $ticket = createTicket($this->client);

    // Owner closes.
    Livewire::actingAs($this->client)
        ->test(TicketView::class, ['ticketId' => $ticket->id])
        ->call('close');

    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);

    // Admin reopens then closes again.
    Livewire::actingAs($this->admin)
        ->test(App\Livewire\Dashboard\Admin\TicketView::class, ['ticketId' => $ticket->id])
        ->call('reopen');
    expect($ticket->fresh()->status)->toBe(TicketStatus::Open);

    Livewire::actingAs($this->admin)
        ->test(App\Livewire\Dashboard\Admin\TicketView::class, ['ticketId' => $ticket->id])
        ->call('closeTicket');
    expect($ticket->fresh()->status)->toBe(TicketStatus::Closed);
});

it('lists all tickets for admin with search and shows own tickets to users', function () {
    createTicket($this->client, 'تیکت موکل شماره هفت');
    createTicket($this->lawyer, 'تیکت وکیل');

    // Admin sees both via oversight page.
    $this->actingAs($this->admin)
        ->get(route('admin.tickets.index'))
        ->assertOk()
        ->assertSee('تیکت موکل شماره هفت')
        ->assertSee('تیکت وکیل');

    // Client sees only their own on support index.
    $html = $this->actingAs($this->client)->get(route('tickets.index'))->getContent();
    expect(str_contains($html, 'تیکت موکل شماره هفت'))->toBeTrue()
        ->and(str_contains($html, 'تیکت وکیل'))->toBeFalse();
});
