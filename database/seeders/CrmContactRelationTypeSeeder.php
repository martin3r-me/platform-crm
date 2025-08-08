<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmContactRelationType;

class CrmContactRelationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $relationTypes = CrmContactRelationType::getDefaultRelationTypes();
        
        foreach ($relationTypes as $relationType) {
            CrmContactRelationType::firstOrCreate(
                ['code' => $relationType['code']],
                [
                    'name' => $relationType['name'],
                    'code' => $relationType['code'],
                    'is_active' => true,
                ]
            );
        }
    }
}
