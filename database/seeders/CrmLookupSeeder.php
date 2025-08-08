<?php

namespace Platform\Crm\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\Crm\Models\CrmSalutation;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmEmailType;
use Platform\Crm\Models\CrmPhoneType;
use Platform\Crm\Models\CrmAddressType;
use Platform\Crm\Models\CrmLegalForm;
use Platform\Crm\Models\CrmIndustry;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmState;

class CrmLookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CrmSalutationSeeder::class,
            CrmContactStatusSeeder::class,
            CrmGenderSeeder::class,
            CrmLanguageSeeder::class,
            CrmAcademicTitleSeeder::class,
            CrmEmailTypeSeeder::class,
            CrmPhoneTypeSeeder::class,
            CrmAddressTypeSeeder::class,
            CrmLegalFormSeeder::class,
            CrmIndustrySeeder::class,
            CrmCountrySeeder::class,
            CrmStateSeeder::class,
            CrmContactRelationTypeSeeder::class,
        ]);
    }
} 