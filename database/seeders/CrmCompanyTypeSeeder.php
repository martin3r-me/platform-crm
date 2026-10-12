<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmCompanyType;

class CrmCompanyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = CrmCompanyType::getDefaultCompanyTypes();

        foreach ($types as $type) {
            CrmCompanyType::firstOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'code' => $type['code'],
                    'is_active' => true,
                ]
            );
        }
    }
}
