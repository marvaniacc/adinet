<?php

namespace App\Livewire\Dashboard\Lawyer;

use App\Enums\ConsultationType;
use App\Enums\LawyerStatus;
use App\Models\LawyerProfile;
use App\Models\LawyerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class ServiceManager extends Component
{
    public LawyerProfile $profile;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public string $consultation_type = 'phone';

    public $duration_minutes = 30;

    public $price_toman = '';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->profile = Auth::user()->lawyerProfile()->firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'display_name' => 'وکیل آدینت',
                'slug' => LawyerProfile::uniqueSlug('vakil'),
                'status' => LawyerStatus::Draft,
            ],
        );
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'consultation_type' => ['required', Rule::in(ConsultationType::values())],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'price_toman' => ['nullable', 'numeric', 'min:0', 'max:100000000000'],
            'is_active' => ['boolean'],
        ];
    }

    public function create(): void
    {
        $data = $this->validate();

        $this->profile->services()->create([
            ...$data,
            'price_amount_minor' => $data['price_toman'] !== '' && $data['price_toman'] !== null
                ? (int) $data['price_toman']
                : null,
        ]);

        $this->closeForm();
        session()->flash('status', 'خدمت جدید اضافه شد.');
    }

    public function edit(int $id): void
    {
        $service = $this->findOwnedService($id);

        $this->editingId = $service->id;
        $this->title = $service->title;
        $this->description = $service->description ?? '';
        $this->consultation_type = $service->consultation_type->value;
        $this->duration_minutes = $service->duration_minutes;
        $this->price_toman = $service->price_amount_minor ?? '';
        $this->is_active = $service->is_active;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function update(): void
    {
        $service = $this->findOwnedService($this->editingId);
        $data = $this->validate();

        $service->update([
            ...$data,
            'price_amount_minor' => $data['price_toman'] !== '' && $data['price_toman'] !== null
                ? (int) $data['price_toman']
                : null,
        ]);

        $this->closeForm();
        session()->flash('status', 'خدمت به‌روزرسانی شد.');
    }

    public function delete(int $id): void
    {
        $this->findOwnedService($id)->delete();
        session()->flash('status', 'خدمت حذف شد.');
    }

    public function toggle(int $id): void
    {
        $service = $this->findOwnedService($id);
        $service->update(['is_active' => ! $service->is_active]);
    }

    protected function findOwnedService(int $id): LawyerService
    {
        return LawyerService::query()
            ->where('lawyer_profile_id', $this->profile->id)
            ->findOrFail($id);
    }

    public function openCreateForm(): void
    {
        $this->reset('editingId', 'title', 'description', 'consultation_type', 'duration_minutes', 'price_toman');
        $this->consultation_type = ConsultationType::Phone->value;
        $this->duration_minutes = 30;
        $this->is_active = true;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->reset('editingId', 'title', 'description', 'consultation_type', 'duration_minutes', 'price_toman');
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.dashboard.lawyer.service-manager', [
            'services' => $this->profile->services()->latest()->get(),
            'types' => ConsultationType::cases(),
        ]);
    }
}
