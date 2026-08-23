<?php

namespace App\Livewire\Client;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class ProfileEdit extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $mobile = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->mobile = $user->mobile;
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        Auth::user()->forceFill($data)->save();

        session()->flash('status', 'پروفایل ذخیره شد.');
    }

    public function render()
    {
        return view('livewire.client.profile-edit');
    }
}
