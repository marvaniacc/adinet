<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        // Stable Latin slugs keep URLs shareable; names stay Persian.
        $specialties = [
            'خانواده' => 'family',
            'طلاق' => 'divorce',
            'مهریه' => 'mahr',
            'ارث' => 'inheritance',
            'کیفری' => 'criminal',
            'حقوقی' => 'civil',
            'ملکی' => 'property',
            'قراردادها' => 'contracts',
            'تجاری' => 'commercial',
            'شرکت‌ها' => 'corporate',
            'کار' => 'labor',
            'چک و سفته' => 'cheque-promissory',
            'تصادفات' => 'accidents',
            'ثبت اسناد' => 'document-registration',
            'مهاجرت' => 'immigration',
        ];

        foreach ($specialties as $name => $slug) {
            Specialty::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name],
            );
        }
    }
}
