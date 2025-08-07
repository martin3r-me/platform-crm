<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmIndustry;

class CrmIndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = CrmIndustry::getDefaultIndustries();
        
        foreach ($industries as $industry) {
            CrmIndustry::firstOrCreate(
                ['code' => $industry['code']],
                [
                    'name' => $industry['name'],
                    'code' => $industry['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 