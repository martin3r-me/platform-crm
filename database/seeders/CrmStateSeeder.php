<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmState;
use Platform\Crm\Models\CrmCountry;

class CrmStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $germany = CrmCountry::where('code', 'DE')->first();
        
        if (!$germany) {
            return;
        }
        
        $germanStates = [
            ['name' => 'Baden-Württemberg', 'code' => 'BW'],
            ['name' => 'Bayern', 'code' => 'BY'],
            ['name' => 'Berlin', 'code' => 'BE'],
            ['name' => 'Brandenburg', 'code' => 'BB'],
            ['name' => 'Bremen', 'code' => 'HB'],
            ['name' => 'Hamburg', 'code' => 'HH'],
            ['name' => 'Hessen', 'code' => 'HE'],
            ['name' => 'Mecklenburg-Vorpommern', 'code' => 'MV'],
            ['name' => 'Niedersachsen', 'code' => 'NI'],
            ['name' => 'Nordrhein-Westfalen', 'code' => 'NW'],
            ['name' => 'Rheinland-Pfalz', 'code' => 'RP'],
            ['name' => 'Saarland', 'code' => 'SL'],
            ['name' => 'Sachsen', 'code' => 'SN'],
            ['name' => 'Sachsen-Anhalt', 'code' => 'ST'],
            ['name' => 'Schleswig-Holstein', 'code' => 'SH'],
            ['name' => 'Thüringen', 'code' => 'TH'],
        ];
        
        foreach ($germanStates as $state) {
            CrmState::firstOrCreate(
                ['code' => $state['code'], 'country_id' => $germany->id],
                [
                    'name' => $state['name'],
                    'code' => $state['code'],
                    'country_id' => $germany->id,
                    'is_active' => true,
                ]
            );
        }
    }
} 