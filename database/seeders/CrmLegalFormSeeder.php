<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmLegalForm;

class CrmLegalFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legalForms = CrmLegalForm::getDefaultLegalForms();
        
        foreach ($legalForms as $legalForm) {
            CrmLegalForm::firstOrCreate(
                ['code' => $legalForm['code']],
                [
                    'name' => $legalForm['name'],
                    'code' => $legalForm['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 