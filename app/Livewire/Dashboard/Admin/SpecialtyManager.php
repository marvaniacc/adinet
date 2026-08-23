<?php

namespace App\Livewire\Dashboard\Admin;

use App\Models\Specialty;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class SpecialtyManager extends Component
{
    public string $name = '';

    public string $slug = '';

    public ?int $editingId = null;

    public function rules(): array
    {
        $ignore = $this->editingId ? ','.$this->editingId : '';

        return [
            'name' => ['required', 'string', 'max:60', 'unique:specialties,name'.$ignore],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9-]*$/', 'unique:specialties,slug'.$ignore],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام تخصص الزامی است.',
            'name.unique' => 'تخصصی با این نام وجود دارد.',
            'slug.unique' => 'اسلاگ تکراری است.',
            'slug.regex' => 'اسلاگ فقط می‌تواند حروف لاتین کوچک، عدد و خط تیره باشد.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $specialty = Specialty::findOrFail($this->editingId);
            $specialty->update([
                'name' => $data['name'],
                'slug' => $data['slug'] !== '' ? $data['slug'] : $specialty->slug,
            ]);
            session()->flash('status', 'تخصص ویرایش شد.');
        } else {
            Specialty::create([
                'name' => $data['name'],
                'slug' => $data['slug'] !== '' ? $data['slug'] : Str::random(8),
            ]);
            session()->flash('status', 'تخصص جدید اضافه شد.');
        }

        $this->reset('name', 'slug', 'editingId');
    }

    public function edit(int $id): void
    {
        $specialty = Specialty::findOrFail($id);
        $this->editingId = $id;
        $this->name = $specialty->name;
        $this->slug = $specialty->slug;
        $this->resetErrorBag();
    }

    public function toggle(int $id): void
    {
        $specialty = Specialty::findOrFail($id);
        $specialty->update(['is_active' => ! $specialty->is_active]);
    }

    public function delete(int $id): void
    {
        Specialty::findOrFail($id)->delete();
        session()->flash('status', 'تخصص حذف شد.');
    }

    public function render()
    {
        return view('livewire.dashboard.admin.specialty-manager', [
            'specialties' => Specialty::query()->withCount('lawyerProfiles')->orderBy('name')->get(),
        ]);
    }
}
