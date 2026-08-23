<?php

namespace App\Livewire\Dashboard\Admin;

use App\Models\City;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class CityManager extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public function rules(): array
    {
        $ignore = $this->editingId ? ','.$this->editingId : '';

        return [
            'name' => ['required', 'string', 'max:60', 'unique:cities,name'.$ignore],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'نام شهر الزامی است.',
            'name.unique' => 'شهری با این نام وجود دارد.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            City::findOrFail($this->editingId)->update(['name' => $data['name']]);
            session()->flash('status', 'شهر ویرایش شد.');
        } else {
            City::create(['name' => $data['name']]);
            session()->flash('status', 'شهر جدید اضافه شد.');
        }

        $this->reset('name', 'editingId');
    }

    public function edit(int $id): void
    {
        $city = City::findOrFail($id);
        $this->editingId = $id;
        $this->name = $city->name;
        $this->resetErrorBag();
    }

    public function toggle(int $id): void
    {
        $city = City::findOrFail($id);
        $city->update(['is_active' => ! $city->is_active]);
    }

    public function render()
    {
        return view('livewire.dashboard.admin.city-manager', [
            'cities' => City::query()->withCount('lawyerProfiles')->orderBy('name')->get(),
        ]);
    }
}
