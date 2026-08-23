<?php

namespace App\Livewire\Messages;

use App\Models\Conversation;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class Chat extends Component
{
    use WithFileUploads;

    public Conversation $conversation;

    public string $body = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public function mount(int $conversationId): void
    {
        $this->conversation = Conversation::with([
            'consultationRequest:id,subject,status,client_id,lawyer_profile_id',
            'client:id,first_name,last_name,mobile',
            'lawyerProfile:id,display_name,slug,user_id',
        ])->findOrFail($conversationId);

        $this->authorize('view', $this->conversation);
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:2000'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'فقط فایل‌های PDF، JPG و PNG مجاز هستند.',
            'file.max' => 'حجم فایل حداکثر ۵ مگابایت است.',
        ];
    }

    public function send(): void
    {
        // Light anti-spam ceiling per user.
        if (RateLimiter::tooManyAttempts('chat:'.Auth::id(), 30)) {
            $this->addError('body', 'تعداد پیام‌ها بیش از حد مجاز است. چند لحظه بعد تلاش کنید.');

            return;
        }

        $validated = $this->validate();

        $documentId = null;

        if ($this->file) {
            $documentId = $this->storeDocument();
        }

        $trimmed = trim($this->body);

        if ($trimmed === '' && $documentId === null) {
            $this->addError('body', 'متن پیام خالی است.');

            return;
        }

        $messageBody = $trimmed !== ''
            ? $trimmed
            : '📎 سند پیوست شد: '.$this->storedOriginalName;

        $this->conversation->messages()->create([
            'sender_id' => Auth::id(),
            'body' => mb_substr($messageBody, 0, 2000),
        ]);

        $this->conversation->forceFill(['last_message_at' => now()])->save();
        RateLimiter::hit('chat:'.Auth::id(), 60);

        $this->reset('body');
    }

    private ?string $storedOriginalName = null;

    private function storeDocument(): int
    {
        $file = $this->file;
        $this->storedOriginalName = $file->getClientOriginalName();

        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = 'documents/'.$this->conversation->consultation_request_id.'/'.Str::uuid().'.'.$extension;

        $file->storeAs(dirname($path), basename($path), 'local');

        $document = Document::create([
            'consultation_request_id' => $this->conversation->consultation_request_id,
            'uploaded_by' => Auth::id(),
            'original_name' => mb_substr($this->storedOriginalName, 0, 180),
            'path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
        ]);

        $this->reset('file');

        return $document->id;
    }

    public function deleteDocument(int $id): void
    {
        $document = Document::query()
            ->where('consultation_request_id', $this->conversation->consultation_request_id)
            ->findOrFail($id);

        $this->authorize('delete', $document);

        Storage::disk('local')->delete($document->path);
        $document->delete();
    }

    public function render()
    {
        // Opening/refreshing marks the counterpart's messages as read.
        $this->conversation->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('livewire.messages.chat', [
            'messages' => $this->conversation->messages()->with('sender:id,role,first_name,last_name')->limit(200)->get(),
            'documents' => $this->conversation->consultationRequest->documents()->with('uploader:id,role')->latest()->get(),
            'isClientSide' => $this->conversation->client_id === Auth::id(),
        ]);
    }
}
