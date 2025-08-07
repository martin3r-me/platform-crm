<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmAddressType;

class CrmAddressTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addressTypes = CrmAddressType::getDefaultAddressTypes();
        
        foreach ($addressTypes as $addressType) {
            CrmAddressType::firstOrCreate(
                ['code' => $addressType['code']],
                [
                    'name' => $addressType['name'],
                    'code' => $addressType['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 