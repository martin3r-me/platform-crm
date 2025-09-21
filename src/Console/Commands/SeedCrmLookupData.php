<?php

namespace Platform\Crm\Console\Commands;

use Illuminate\Console\Command;

class SeedCrmLookupData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:seed-lookup-data 
                            {--force : Force the operation to run even in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the CRM module lookup data (countries, states, salutations, etc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding CRM lookup data...');
        
        try {
            // Alle CRM Seeder direkt ausführen
            $this->call('db:seed', [
                '--class' => 'CrmSalutationSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmContactStatusSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmGenderSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmLanguageSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmAcademicTitleSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmEmailTypeSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmPhoneTypeSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmAddressTypeSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmLegalFormSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmIndustrySeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmCountrySeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmStateSeeder'
            ]);
            $this->call('db:seed', [
                '--class' => 'CrmContactRelationTypeSeeder'
            ]);
            
            $this->info('✅ CRM lookup data seeded successfully!');
            $this->line('');
            $this->line('Seeded data:');
            $this->line('  • Countries and States');
            $this->line('  • Salutations (Herr, Frau, etc.)');
            $this->line('  • Contact Statuses');
            $this->line('  • Genders');
            $this->line('  • Languages');
            $this->line('  • Academic Titles');
            $this->line('  • Email Types');
            $this->line('  • Phone Types');
            $this->line('  • Address Types');
            $this->line('  • Legal Forms');
            $this->line('  • Industries');
            $this->line('  • Contact Relation Types');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to seed CRM lookup data: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
