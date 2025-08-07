<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmAcademicTitle;

class CrmAcademicTitleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $titles = CrmAcademicTitle::getDefaultTitles();
        
        foreach ($titles as $title) {
            CrmAcademicTitle::firstOrCreate(
                ['code' => $title['code']],
                [
                    'name' => $title['name'],
                    'code' => $title['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 