<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmContactStatus;

class CrmContactStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = CrmContactStatus::getDefaultContactStatuses();
        
        foreach ($statuses as $status) {
            CrmContactStatus::firstOrCreate(
                ['code' => $status['code']],
                [
                    'name' => $status['name'],
                    'code' => $status['code'],
                    'is_active' => true,
                ]
            );
        }
    }
} 