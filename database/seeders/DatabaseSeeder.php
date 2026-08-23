<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bootstrap admin account. Mobile login works via OTP;
        // set ADMIN_MOBILE in .env to receive the codes.
        User::query()->firstOrCreate(
            ['mobile' => preg_replace('/\D/', '', (string) env('ADMIN_MOBILE', '09120000000'))],
            [
                'first_name' => 'مدیر',
                'last_name' => 'آدینت',
                'role' => User::ROLE_ADMIN,
            ],
        );

        $this->call([
            CitySeeder::class,
            SpecialtySeeder::class,
        ]);
    }
}
