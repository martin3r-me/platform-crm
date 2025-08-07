<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmEmailType;

class CrmEmailTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emailTypes = CrmEmailType::getDefaultEmailTypes();
        
        foreach ($emailTypes as $emailType) {
            CrmEmailType::firstOrCreate(
                ['code' => $emailType['code']],
                [
                    'name' => $emailType['name'],
                    'code' => $emailType['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 