<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmGender;

class CrmGenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $genders = CrmGender::getDefaultGenders();
        
        foreach ($genders as $gender) {
            CrmGender::firstOrCreate(
                ['code' => $gender['code']],
                [
                    'name' => $gender['name'],
                    'code' => $gender['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 