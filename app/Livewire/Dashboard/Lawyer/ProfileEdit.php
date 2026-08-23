<?php

namespace App\Livewire\Dashboard\Lawyer;

use App\Enums\LawyerStatus;
use App\Models\City;
use App\Models\LawyerProfile;
use App\Models\Specialty;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.dashboard')]
class ProfileEdit extends Component
{
    use WithFileUploads;

    public LawyerProfile $profile;

    public string $display_name = '';

    public $city_id = '';

    public string $bar_association = '';

    public string $license_number = '';

    public string $bio = '';

    public $years_of_experience = 0;

    public string $phone = '';

    /** @var array<int, int> */
    public array $specialty_ids = [];

    public $photo;

    public bool $profileCreated = false;

    public function mount(): void
    {
        $user = Auth::user();

        // First visit as lawyer: create the draft profile shell.
        $this->profile = $user->lawyerProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: 'وکیل آدینت',
                'slug' => LawyerProfile::uniqueSlug($user->last_name ?: 'vakil'),
                'status' => LawyerStatus::Draft,
            ],
        );

        if (! $this->profile->wasRecentlyCreated) {
            $this->fillFromProfile();
        } else {
            $this->display_name = $this->profile->display_name;
        }
    }

    protected function fillFromProfile(): void
    {
        $this->display_name = $this->profile->display_name;
        $this->city_id = $this->profile->city_id ?? '';
        $this->bar_association = $this->profile->bar_association ?? '';
        $this->license_number = $this->profile->license_number ?? '';
        $this->bio = $this->profile->bio ?? '';
        $this->years_of_experience = $this->profile->years_of_experience;
        $this->phone = $this->profile->phone ?? '';
        $this->specialty_ids = $this->profile->specialties()->pluck('specialty_id')->all();
    }

    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:120'],
            'city_id' => ['nullable', Rule::exists('cities', 'id')->where('is_active', true)],
            'bar_association' => ['required', 'string', 'max:120'],
            'license_number' => ['required', 'string', 'max:40'],
            'bio' => ['required', 'string', 'min:50', 'max:5000'],
            'years_of_experience' => ['required', 'integer', 'min:0', 'max:70'],
            'phone' => ['required', 'string', 'max:20'],
            'specialty_ids' => ['required', 'array', 'min:1'],
            'specialty_ids.*' => [Rule::exists('specialties', 'id')->where('is_active', true)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'نام نمایشی الزامی است.',
            'bar_association.required' => 'مرجع صلاحیت‌دار (کانون وکلا) الزامی است.',
            'license_number.required' => 'شماره پروانه وکالت الزامی است.',
            'bio.required' => 'معرفی حرفه‌ای الزامی است.',
            'bio.min' => 'معرفی حرفه‌ای باید حداقل ۵۰ کاراکتر باشد.',
            'specialty_ids.required' => 'حداقل یک تخصص انتخاب کنید.',
            'photo.image' => 'فایل تصویر معتبر نیست.',
            'photo.max' => 'حجم تصویر حداکثر ۲ مگابایت است.',
        ];
    }

    public function save(): void
    {
        // Ownership is implicit here (own dashboard), but stay defensive.
        $this->authorize('manage', $this->profile);

        $data = $this->validate();

        if ($this->photo) {
            if ($this->profile->profile_photo) {
                Storage::disk('public')->delete($this->profile->profile_photo);
            }
            $data['profile_photo'] = $this->photo->store('lawyer-photos', 'public');
        }

        unset($data['photo']);

        $slug = $this->profile->slug;
        if ($this->display_name !== $this->profile->getOriginal('display_name')) {
            $slug = LawyerProfile::uniqueSlug($this->display_name, $this->profile->id);
        }

        $this->profile->update($data + ['slug' => $slug]);
        $this->profile->specialties()->sync($this->specialty_ids);

        $this->photo = null;
        $this->fillFromProfile();
        $this->profileCreated = true;
        $this->dispatch('profile-saved');
    }

    public function submitForReview(): void
    {
        $this->save();

        $this->authorize('submitForReview', $this->profile);

        $this->profile->forceFill([
            'status' => LawyerStatus::PendingReview,
            'submitted_for_review_at' => now(),
            'rejection_reason' => null,
        ])->save();

        session()->flash('status', 'پروفایل شما برای بررسی ارسال شد.');
        $this->redirect(route('dashboard.lawyer.profile'), navigate: true);
    }

    public function render()
    {
        return view('livewire.dashboard.lawyer.profile-edit', [
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'specialties' => Specialty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
