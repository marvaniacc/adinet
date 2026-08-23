<?php

namespace App\Livewire\Public;

use App\Enums\ConsultationType;
use App\Enums\LawyerStatus;
use App\Models\City;
use App\Models\LawyerProfile;
use App\Models\Specialty;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class LawyerList extends Component
{
    use WithPagination;

    #[Url]
    public $city = '';

    #[Url]
    public $specialty = '';

    #[Url]
    public $type = '';

    public function updatedCity(): void
    {
        $this->resetPage();
    }

    public function updatedSpecialty(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Lenient handling of crafted URLs: unknown values simply match nothing,
        // instead of throwing validation errors.
        $cityId = is_numeric($this->city) ? (int) $this->city : null;

        $specialtySlug = Specialty::query()
            ->where('slug', (string) $this->specialty)
            ->where('is_active', true)
            ->value('slug');

        $type = in_array((string) $this->type, ConsultationType::values(), true)
            ? ConsultationType::from($this->type)
            : null;

        $query = LawyerProfile::query()
            ->where('status', LawyerStatus::Verified)
            ->with(['city:id,name', 'specialties:id,name,slug'])
            ->withMin('services as min_price', 'price_amount_minor')
            ->withCount('services');

        if ($cityId !== null && City::query()->whereKey($cityId)->exists()) {
            $query->where('city_id', $cityId);
        }

        if ($specialtySlug !== null) {
            $query->whereHas('specialties', fn ($q) => $q->where('slug', $specialtySlug));
        }

        if ($type !== null) {
            $query->whereHas('services', fn ($q) => $q
                ->where('consultation_type', $type)
                ->where('is_active', true));
        }

        $lawyers = $query
            ->orderByDesc('years_of_experience')
            ->orderBy('display_name')
            ->paginate(12);

        return view('livewire.public.lawyer-list', [
            'lawyers' => $lawyers,
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'specialties' => Specialty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'types' => ConsultationType::cases(),
        ]);
    }
}
