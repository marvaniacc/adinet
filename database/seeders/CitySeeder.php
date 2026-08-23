<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'تهران', 'مشهد', 'اصفهان', 'شیراز', 'تبریز',
            'کرج', 'قم', 'اهواز', 'رشت', 'یزد',
        ];

        foreach ($cities as $name) {
            City::query()->firstOrCreate(['name' => $name]);
        }
    }
}
