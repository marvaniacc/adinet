<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class Library extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    public $requestId = '';

    public string $deleteConfirm = '';

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'requestId' => ['required', Rule::in($this->myRequestIds())],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'انتخاب فایل الزامی است.',
            'file.mimes' => 'فقط PDF، JPG و PNG مجاز است.',
            'file.max' => 'حجم فایل حداکثر ۵ مگابایت است.',
            'requestId.in' => 'درخواست انتخابی معتبر نیست.',
        ];
    }

    private function myRequestIds(): array
    {
        return Auth::user()->consultationRequests()->pluck('id')->all();
    }

    public function upload(): void
    {
        $data = $this->validate();

        $path = 'documents/'.$data['requestId'].'/'.Str::uuid().'.'.strtolower($this->file->getClientOriginalExtension() ?: 'bin');

        $this->file->storeAs(dirname($path), basename($path), 'local');

        Document::create([
            'consultation_request_id' => (int) $data['requestId'],
            'uploaded_by' => Auth::id(),
            'original_name' => mb_substr($this->file->getClientOriginalName(), 0, 180),
            'path' => $path,
            'mime_type' => (string) $this->file->getMimeType(),
            'size_bytes' => (int) $this->file->getSize(),
        ]);

        $this->reset('file');
        session()->flash('status', 'سند با موفقیت بارگذاری شد.');
    }

    public function deleteDocument(int $id): void
    {
        $document = Document::query()
            ->whereIn('consultation_request_id', $this->myRequestIds())
            ->findOrFail($id);

        abort_unless(Auth::id() === $document->uploaded_by, 403);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        session()->flash('status', 'سند حذف شد.');
    }

    public function render()
    {
        $isLawyer = Auth::user()->isLawyer();

        $documents = Document::query()
            ->with(['uploader:id,role,first_name,last_name', 'consultationRequest:id,subject,client_id,lawyer_profile_id,status'])
            ->whereHas('consultationRequest', fn ($q) => $isLawyer
                ? $q->where('lawyer_profile_id', Auth::user()->lawyerProfile?->id)
                : $q->where('client_id', Auth::id()))
            ->latest()
            ->paginate(20);

        return view('livewire.documents.library', [
            'documents' => $documents,
            'requests' => $isLawyer ? collect() : Auth::user()->consultationRequests()
                ->select('id', 'subject')->latest()->get(),
            'isLawyer' => $isLawyer,
        ]);
    }
}
