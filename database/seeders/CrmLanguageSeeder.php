<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmLanguage;

class CrmLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = CrmLanguage::getDefaultLanguages();
        
        foreach ($languages as $language) {
            CrmLanguage::firstOrCreate(
                ['code' => $language['code']],
                [
                    'name' => $language['name'],
                    'code' => $language['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 