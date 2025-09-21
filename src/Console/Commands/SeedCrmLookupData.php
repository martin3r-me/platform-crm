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
            // Seeder direkt instanziieren und ausführen
            $seeder = app(\Platform\Crm\Database\Seeders\CrmLookupSeeder::class);
            $seeder->run();
            
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
