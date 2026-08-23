<?php

namespace App\Livewire\Dashboard\Admin;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class SettingsManager extends Component
{
    public string $request_expiry_days = '7';

    public string $support_mobile = '';

    public function mount(): void
    {
        $this->request_expiry_days = Setting::get('request_expiry_days', '7');
        $this->support_mobile = Setting::get('support_mobile', '');
    }

    protected function rules(): array
    {
        return [
            'request_expiry_days' => ['required', 'integer', 'min:1', 'max:90'],
            'support_mobile' => ['nullable', 'string', 'regex:/^0\d{10}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_expiry_days.required' => 'تعداد روز انقضا الزامی است.',
            'request_expiry_days.integer' => 'تعداد روز باید عدد باشد.',
            'support_mobile.regex' => 'شماره پشتیبانی معتبر نیست (نمونه: 09121234567).',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        Setting::put('request_expiry_days', $data['request_expiry_days']);
        Setting::put('support_mobile', $data['support_mobile'] ?: '');

        session()->flash('status', 'تنظیمات ذخیره شد.');
    }

    public function render()
    {
        return view('livewire.dashboard.admin.settings-manager');
    }
}
