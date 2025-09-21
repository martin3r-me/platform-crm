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
            $forceFlag = $this->option('force') ? ['--force' => true] : [];
            
            $this->call('db:seed', array_merge([
                '--class' => 'CrmSalutationSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmContactStatusSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmGenderSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmLanguageSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmAcademicTitleSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmEmailTypeSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmPhoneTypeSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmAddressTypeSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmLegalFormSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmIndustrySeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmCountrySeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmStateSeeder'
            ], $forceFlag));
            $this->call('db:seed', array_merge([
                '--class' => 'CrmContactRelationTypeSeeder'
            ], $forceFlag));
            
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
