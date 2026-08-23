<?php

namespace Database\Factories;

use App\Enums\ConsultationType;
use App\Enums\LawyerStatus;
use App\Models\City;
use App\Models\LawyerProfile;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LawyerProfile>
 */
class LawyerProfileFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->lastName().' '.fake()->firstName();

        return [
            'user_id' => User::factory()->lawyer(),
            'city_id' => City::query()->inRandomOrder()->value('id') ?? City::factory(),
            'display_name' => 'دکتر '.$name,
            'slug' => null,
            'bar_association' => 'کانون وکلای دادگستری',
            'license_number' => (string) fake()->numberBetween(10000, 99999),
            'bio' => fake()->realText(300),
            'years_of_experience' => fake()->numberBetween(1, 30),
            'phone' => '021'.random_int(20000000, 89999999),
            'status' => LawyerStatus::Draft,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (LawyerProfile $profile) {
            $profile->slug ??= LawyerProfile::uniqueSlug($profile->display_name);
        });
    }

    public function verified(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => LawyerStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => LawyerStatus::PendingReview,
            'submitted_for_review_at' => now(),
        ]);
    }

    /**
     * Attach 1-3 random specialties + one active service of each type.
     */
    public function withServicesAndSpecialties(): static
    {
        return $this->afterCreating(function (LawyerProfile $profile) {
            $specialtyIds = Specialty::query()->inRandomOrder()->limit(random_int(1, 3))->pluck('id');
            $profile->specialties()->sync($specialtyIds);

            $types = fake()->randomElements(ConsultationType::cases(), random_int(1, 3));

            foreach ($types as $type) {
                $profile->services()->create([
                    'title' => $type->label(),
                    'description' => 'جلسه مشاوره '.$type->label().' به همراه بررسی اولیه پرونده.',
                    'consultation_type' => $type,
                    'duration_minutes' => fake()->randomElement([30, 45, 60]),
                    'price_amount_minor' => fake()->randomElement([null, 500000, 800000, 1500000]),
                    'is_active' => true,
                ]);
            }
        });
    }
}
