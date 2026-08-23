<?php

namespace Database\Seeders;

use App\Models\LawyerProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Demo marketplace content for development/staging environments.
 * Run manually: php artisan db:seed --class=DemoMarketplaceSeeder
 */
class DemoMarketplaceSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Verified lawyers visible in the public marketplace.
        LawyerProfile::factory()
            ->count(8)
            ->verified()
            ->withServicesAndSpecialties()
            ->create();

        // A pending lawyer so admins have something to review.
        LawyerProfile::factory()->pending()->withServicesAndSpecialties()->create();
    }
}
