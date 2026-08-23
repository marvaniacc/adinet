<?php

namespace App\Livewire\Dashboard\Lawyer;

use App\Enums\LawyerStatus;
use App\Models\AvailabilitySlot;
use App\Models\LawyerProfile;
use App\Services\SlotGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class AvailabilityIndex extends Component
{
    public ?int $editingId = null;

    public $weekday = '6';

    public string $start_time = '';

    public string $end_time = '';

    public function rules(): array
    {
        return [
            'weekday' => ['required', Rule::in(array_keys(AvailabilitySlot::weekdayOptions()))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.required' => 'ساعت شروع الزامی است.',
            'end_time.required' => 'ساعت پایان الزامی است.',
            'end_time.after' => 'ساعت پایان باید بعد از ساعت شروع باشد.',
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        $profile = Auth::user()->lawyerProfile()->firstOrCreate(
            ['user_id' => Auth::id()],
            [
                'display_name' => 'وکیل آدینت',
                'slug' => LawyerProfile::uniqueSlug('vakil'),
                'status' => LawyerStatus::Draft->value,
            ],
        );

        if ($this->editingId) {
            $slot = AvailabilitySlot::query()
                ->where('lawyer_profile_id', $profile->id)
                ->findOrFail($this->editingId);

            $slot->update($data);
            session()->flash('status', 'بازه کاری ویرایش شد.');
        } else {
            $profile->availabilitySlots()->create($data);
            session()->flash('status', 'بازه کاری جدید اضافه شد.');
        }

        $this->reset('editingId', 'weekday', 'start_time', 'end_time');
        $this->weekday = '6';
    }

    public function edit(int $id): void
    {
        $slot = AvailabilitySlot::query()
            ->where('lawyer_profile_id', Auth::user()->lawyerProfile?->id ?? -1)
            ->findOrFail($id);

        $this->editingId = $id;
        $this->weekday = (string) $slot->weekday;
        $this->start_time = $slot->start_time;
        $this->end_time = $slot->end_time;
        $this->resetErrorBag();
    }

    public function toggle(int $id): void
    {
        $slot = AvailabilitySlot::query()
            ->where('lawyer_profile_id', Auth::user()->lawyerProfile?->id ?? -1)
            ->findOrFail($id);

        $slot->update(['is_active' => ! $slot->is_active]);
    }

    public function delete(int $id): void
    {
        AvailabilitySlot::query()
            ->where('lawyer_profile_id', Auth::user()->lawyerProfile?->id ?? -1)
            ->findOrFail($id)
            ->delete();

        session()->flash('status', 'بازه حذف شد.');
    }

    public function render()
    {
        $slots = Auth::user()->lawyerProfile?->availabilitySlots()
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get()
            ?? collect();

        // Preview of the next bookable slots this pattern generates.
        $preview = $slots->where('is_active')->isNotEmpty()
            ? app(SlotGenerator::class)->upcomingFor(Auth::user()->lawyerProfile)
            : collect();

        return view('livewire.dashboard.lawyer.availability-index', [
            'slots' => $slots,
            'weekdays' => AvailabilitySlot::weekdayOptions(),
            'preview' => $preview->take(12),
            'previewCount' => $preview->count(),
        ]);
    }
}
