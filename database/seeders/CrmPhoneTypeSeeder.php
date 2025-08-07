<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmPhoneType;

class CrmPhoneTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $phoneTypes = CrmPhoneType::getDefaultPhoneTypes();
        
        foreach ($phoneTypes as $phoneType) {
            CrmPhoneType::firstOrCreate(
                ['code' => $phoneType['code']],
                [
                    'name' => $phoneType['name'],
                    'code' => $phoneType['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 