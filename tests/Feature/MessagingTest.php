<?php

use App\Enums\AppointmentStatus;
use App\Enums\ConsultationRequestStatus;
use App\Enums\ConsultationType;
use App\Livewire\Messages\Chat;
use App\Models\Appointment;
use App\Models\City;
use App\Models\ConsultationRequest;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\LawyerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->lawyerUser = User::factory()->lawyer()->create(['mobile' => '09111111111']);
    $this->lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $this->lawyerUser->id,
        'city_id' => City::factory(),
    ]);
    $this->client = User::factory()->client()->create(['mobile' => '09222222222']);

    $this->request = ConsultationRequest::create([
        'lawyer_profile_id' => $this->lawyer->id,
        'client_id' => $this->client->id,
        'subject' => 'موضوع آزمون گفتگو',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    $this->conversation = Conversation::create([
        'consultation_request_id' => $this->request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
    ]);

    Appointment::create([
        'consultation_request_id' => $this->request->id,
        'client_id' => $this->client->id,
        'lawyer_profile_id' => $this->lawyer->id,
        'scheduled_at' => now()->addDays(2),
        'duration_minutes' => 30,
        'consultation_type' => ConsultationType::Phone,
        'status' => AppointmentStatus::Scheduled,
    ]);
});

it('allows both parties to open the conversation and blocks outsiders', function () {
    Chat::class; // import sanity

    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->assertOk()
        ->assertSee($this->request->subject);

    Livewire::actingAs($this->lawyerUser)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->assertOk();

    // Another client / lawyer must not gain access (real URL-level check).
    $stranger = User::factory()->client()->create();
    $this->actingAs($stranger)
        ->get(route('messages.show', ['conversationId' => $this->conversation->id]))
        ->assertForbidden();

    $foreignLawyer = User::factory()->lawyer()->create();
    $this->actingAs($foreignLawyer)
        ->get(route('dashboard.lawyer.messages.show', ['conversationId' => $this->conversation->id]))
        ->assertForbidden();
});

it('sends messages and marks them read when the counterpart opens the chat', function () {
    // Client sends a message.
    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->set('body', 'سلام، وقتتون بخیر. سوال داشتن درباره مدارک.')
        ->call('send')
        ->assertHasNoErrors();

    expect($this->conversation->messages()->count())->toBe(1);

    $message = $this->conversation->messages()->first();
    expect($message->sender_id)->toBe($this->client->id)
        ->and($message->read_at)->toBeNull();

    // Lawyer opens the chat: message becomes read.
    Livewire::actingAs($this->lawyerUser)
        ->test(Chat::class, ['conversationId' => $this->conversation->id]);

    expect($message->fresh()->read_at)->not->toBeNull();
});

it('rejects empty and oversized message bodies', function () {
    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->set('body', '')
        ->call('send')
        ->assertHasErrors(['body']);

    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->set('body', str_repeat('ا', 2001))
        ->call('send')
        ->assertHasErrors(['body']);

    expect($this->conversation->messages()->count())->toBe(0);
});

it('stores documents privately with randomized names and authorizes downloads', function () {
    Storage::fake('local');

    // Client uploads a PDF through the chat.
    $component = Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->set('file', UploadedFile::fake()->create('قرارداد-اجاره.pdf', 500, 'application/pdf'))
        ->call('send');

    $component->assertHasNoErrors();

    $document = Document::query()->sole();
    $storedPath = Storage::disk('local')->path($document->path);

    expect($document->uploaded_by)->toBe($this->client->id)
        ->and(str_contains($document->path, 'documents/'.$this->request->id.'/'))->toBeTrue()
        // Randomized storage name - original Persian name only in DB metadata.
        ->and(str_contains($document->path, 'قرارداد'))->toBeFalse()
        ->and(! str_starts_with($storedPath, public_path()))->toBeTrue() // never inside public/
        ->and(file_exists($storedPath))->toBeTrue();

    // Guests cannot download documents (checked in its own test below).
    // Both parties can download via the policy-checked route.
    $this->actingAs($this->client)->get(route('documents.download', $document))->assertOk();
    $this->actingAs($this->lawyerUser)->get(route('documents.download', $document))->assertOk();

    // Strangers cannot.
    $stranger = User::factory()->client()->create();
    $this->actingAs($stranger)->get(route('documents.download', $document))->assertForbidden();
});

it('bounces guests away from document downloads', function () {
    Storage::fake('local');

    $client = User::factory()->client()->create(['mobile' => '09333333333']);
    $lawyerUser = User::factory()->lawyer()->create();
    $lawyer = LawyerProfile::factory()->verified()->create([
        'user_id' => $lawyerUser->id,
        'city_id' => City::factory(),
    ]);

    $request = ConsultationRequest::create([
        'lawyer_profile_id' => $lawyer->id,
        'client_id' => $client->id,
        'subject' => 'سند مهم',
        'description' => str_repeat('د', 40),
        'status' => ConsultationRequestStatus::Accepted,
        'decided_at' => now(),
    ]);

    Storage::disk('local')->put($path = 'documents/'.$request->id.'/test.pdf', 'content');

    $document = Document::create([
        'consultation_request_id' => $request->id,
        'uploaded_by' => $client->id,
        'original_name' => 'test.pdf',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size_bytes' => 7,
    ]);

    $this->get(route('documents.download', $document))->assertRedirect(route('login'));
});

it('rejects disallowed file types for documents', function () {
    Storage::fake('local');

    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->set('file', UploadedFile::fake()->create('script.php', 10))
        ->call('send')
        ->assertHasErrors(['file']);

    expect(Document::count())->toBe(0);
});

it('lets only the uploader delete their own document', function () {
    Storage::fake('local');

    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->set('file', UploadedFile::fake()->image('scan.jpg'))
        ->call('send');

    $document = Document::query()->sole();
    $storedPath = Storage::disk('local')->path($document->path);

    // Lawyer cannot delete someone else's upload.
    Livewire::actingAs($this->lawyerUser)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->call('deleteDocument', $document->id);

    expect(Document::count())->toBe(1)->and(file_exists($storedPath))->toBeTrue();

    // Uploader can.
    Livewire::actingAs($this->client)
        ->test(Chat::class, ['conversationId' => $this->conversation->id])
        ->call('deleteDocument', $document->id);

    expect(Document::count())->toBe(0)->and(file_exists($storedPath))->toBeFalse();
});
