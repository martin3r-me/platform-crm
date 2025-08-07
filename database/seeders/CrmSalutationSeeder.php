<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmSalutation;

class CrmSalutationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salutations = CrmSalutation::getDefaultSalutations();
        
        foreach ($salutations as $salutation) {
            CrmSalutation::firstOrCreate(
                ['code' => $salutation['code']],
                [
                    'name' => $salutation['name'],
                    'code' => $salutation['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 