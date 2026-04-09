<?php

namespace Platform\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Platform\ActivityLog\Models\ActivityLogActivity;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Models\CrmEmailAddress;
use Platform\Crm\Models\CrmPhoneNumber;
use Platform\Crm\Models\CrmPostalAddress;

class ImportHubspotData extends Command
{
    protected $signature = 'crm:import-hubspot
                            {path : Path to directory containing CSV files (contacts.csv, companies.csv, etc.)}
                            {--team-id= : Team ID to import into (required)}
                            {--user-id= : User ID for created_by/owned_by (required)}
                            {--dry-run : Show what would be imported without actually importing}
                            {--skip-companies : Skip company import}
                            {--skip-contacts : Skip contact import}
                            {--skip-relations : Skip contact↔company relations}
                            {--skip-emails : Skip email addresses}
                            {--skip-phones : Skip phone numbers}
                            {--skip-addresses : Skip postal addresses}
                            {--skip-engagements : Skip engagement/activity import}';

    protected $description = 'Import HubSpot CRM data (companies, contacts, engagements) from CSV export';

    // Lookup caches (code → id)
    private array $countryMap = [];
    private array $industryMap = [];
    private array $statusMap = [];
    private array $languageMap = [];
    private array $genderMap = [];
    private array $salutationMap = [];
    private array $emailTypeMap = [];
    private array $phoneTypeMap = [];
    private array $addressTypeMap = [];
    private array $relationTypeMap = [];

    // HubSpot → CRM country name normalization
    private const COUNTRY_NORMALIZE = [
        'Netherlands' => 'NL', 'The Netherlands' => 'NL',
        'Germany' => 'DE', 'Germany ' => 'DE', 'Duitsland' => 'DE',
        'Belgium' => 'BE', 'Belgie' => 'BE',
        'France' => 'FR',
        'Switzerland' => 'CH', 'Switserland' => 'CH', 'Switzerland ' => 'CH',
        'Austria' => 'AT', 'Spain' => 'ES', 'Spain ' => 'ES',
        'Italy' => 'IT', 'Ireland' => 'IE',
        'United Kingdom' => 'GB', 'United States' => 'US',
        'Denmark' => 'DK', 'Danmark' => 'DK',
        'Norway' => 'NO', 'Portugal' => 'PT', 'Poland' => 'PL',
        'Greece' => 'GR', 'Luxembourg' => 'LU', 'Luxemboug' => 'LU',
        'Cyprus' => 'CY', 'Lithuania' => 'LT',
        'Bulgaria' => 'BG', 'Bulgary' => 'BG',
        'Sweden' => 'SE', 'India' => 'IN', 'New Zealand' => 'NZ',
    ];

    // HubSpot industry → CRM industry code
    private const INDUSTRY_MAP = [
        'FOOD_PRODUCTION' => 'FOOD_BEVERAGE', 'FOOD_BEVERAGES' => 'FOOD_BEVERAGE',
        'RESTAURANTS' => 'TOURISM', 'HOSPITALITY' => 'TOURISM', 'LEISURE_TRAVEL_TOURISM' => 'TOURISM',
        'RETAIL' => 'RETAIL', 'WHOLESALE' => 'RETAIL', 'SPORTING_GOODS' => 'RETAIL',
        'INFORMATION_TECHNOLOGY_AND_SERVICES' => 'IT_SOFTWARE', 'COMPUTER_SOFTWARE' => 'IT_SOFTWARE',
        'COMPUTER_NETWORKING' => 'IT_SOFTWARE', 'COMPUTER_NETWORK_SECURITY' => 'IT_SOFTWARE',
        'MANAGEMENT_CONSULTING' => 'CONSULTING', 'PROFESSIONAL_TRAINING_COACHING' => 'CONSULTING', 'ACCOUNTING' => 'CONSULTING',
        'FINANCIAL_SERVICES' => 'BANKING', 'BANKING' => 'BANKING', 'CAPITAL_MARKETS' => 'BANKING',
        'INSURANCE' => 'INSURANCE', 'CONSTRUCTION' => 'CONSTRUCTION', 'BUILDING_MATERIALS' => 'CONSTRUCTION',
        'HOSPITAL_HEALTH_CARE' => 'HEALTHCARE', 'HEALTH_WELLNESS_AND_FITNESS' => 'HEALTHCARE',
        'AUTOMOTIVE' => 'AUTOMOTIVE', 'MECHANICAL_OR_INDUSTRIAL_ENGINEERING' => 'MACHINERY',
        'REAL_ESTATE' => 'REAL_ESTATE', 'TELECOMMUNICATIONS' => 'TELECOM',
        'LOGISTICS_AND_SUPPLY_CHAIN' => 'LOGISTICS', 'TRANSPORTATION_TRUCKING_RAILROAD' => 'LOGISTICS',
        'AIRLINES_AVIATION' => 'LOGISTICS', 'MARITIME' => 'LOGISTICS',
        'APPAREL_FASHION' => 'TEXTILES', 'TEXTILES' => 'TEXTILES',
        'BROADCAST_MEDIA' => 'MEDIA', 'MEDIA_PRODUCTION' => 'MEDIA', 'ONLINE_MEDIA' => 'MEDIA',
        'PUBLISHING' => 'MEDIA', 'ENTERTAINMENT' => 'MEDIA',
        'OIL_ENERGY' => 'ENERGY', 'GOVERNMENT_ADMINISTRATION' => 'PUBLIC_SECTOR',
    ];

    // HubSpot lifecycle → CRM contact status code
    private const LIFECYCLE_MAP = [
        'customer' => 'CUSTOMER', 'lead' => 'INTERESTED', 'subscriber' => 'ACTIVE',
        'marketingqualifiedlead' => 'INTERESTED', 'salesqualifiedlead' => 'INTERESTED',
        'opportunity' => 'INTERESTED', 'evangelist' => 'PARTNER', 'other' => 'ACTIVE',
    ];

    // HubSpot language → CRM language code
    private const LANG_MAP = [
        'nl' => 'nl', 'nl-nl' => 'nl', 'de' => 'de', 'en' => 'en',
        'fr' => 'fr', 'fr-fr' => 'fr', 'fr-lu' => 'fr', 'it' => 'it', 'es' => 'es',
    ];

    public function handle(): int
    {
        $path = rtrim($this->argument('path'), '/');
        $teamId = (int) $this->option('team-id');
        $userId = (int) $this->option('user-id');
        $dryRun = $this->option('dry-run');

        if (!$teamId || !$userId) {
            $this->error('--team-id and --user-id are required.');
            return 1;
        }

        if (!is_dir($path)) {
            $this->error("Directory not found: {$path}");
            return 1;
        }

        $this->info("HubSpot Import: team_id={$teamId}, user_id={$userId}" . ($dryRun ? ' [DRY RUN]' : ''));

        // Load lookup tables
        $this->loadLookups();

        // Track HS ID → CRM ID mappings
        $companyMap = []; // hs_object_id → crm_company_id
        $contactMap = []; // hs_object_id → crm_contact_id

        // 1. Import Companies
        if (!$this->option('skip-companies') && file_exists($path . '/companies.csv')) {
            $companyMap = $this->importCompanies($path . '/companies.csv', $teamId, $userId, $dryRun);
        }

        // 2. Import Contacts (with relations to companies)
        if (!$this->option('skip-contacts') && file_exists($path . '/contacts.csv')) {
            $contactMap = $this->importContacts($path . '/contacts.csv', $teamId, $userId, $dryRun, $companyMap);
        }

        // 3. Import Engagements (notes, calls, meetings, tasks → activity_log_activities)
        $engagementCounts = ['notes' => 0, 'calls' => 0, 'meetings' => 0, 'tasks' => 0];
        if (!$this->option('skip-engagements')) {
            $engagementCounts = $this->importEngagements($path, $userId, $dryRun, $companyMap, $contactMap);
        }

        $this->newLine();
        $this->info('Import complete.');
        $this->table(['Entity', 'Count'], [
            ['Companies', count($companyMap)],
            ['Contacts', count($contactMap)],
            ['Notes', $engagementCounts['notes']],
            ['Calls', $engagementCounts['calls']],
            ['Meetings', $engagementCounts['meetings']],
            ['Tasks', $engagementCounts['tasks']],
        ]);

        return 0;
    }

    private function loadLookups(): void
    {
        $this->info('Loading lookup tables...');

        foreach (DB::table('crm_countries')->get() as $row) {
            $this->countryMap[$row->code] = $row->id;
        }
        foreach (DB::table('crm_industries')->get() as $row) {
            $this->industryMap[$row->code] = $row->id;
        }
        foreach (DB::table('crm_contact_statuses')->get() as $row) {
            $this->statusMap[$row->code] = $row->id;
        }
        foreach (DB::table('crm_languages')->get() as $row) {
            $this->languageMap[$row->code] = $row->id;
        }
        foreach (DB::table('crm_genders')->get() as $row) {
            $this->genderMap[$row->code] = $row->id;
        }
        foreach (DB::table('crm_salutations')->get() as $row) {
            $this->salutationMap[$row->code] = $row->id;
        }
        foreach (DB::table('crm_email_types')->get() as $row) {
            $this->emailTypeMap[$row->code ?? $row->name] = $row->id;
        }
        foreach (DB::table('crm_phone_types')->get() as $row) {
            $this->phoneTypeMap[$row->code ?? $row->name] = $row->id;
        }
        foreach (DB::table('crm_address_types')->get() as $row) {
            $this->addressTypeMap[$row->code ?? $row->name] = $row->id;
        }
        foreach (DB::table('crm_contact_relation_types')->get() as $row) {
            $this->relationTypeMap[$row->code ?? $row->name] = $row->id;
        }

        $this->info(sprintf(
            '  Loaded: %d countries, %d industries, %d statuses, %d languages',
            count($this->countryMap), count($this->industryMap),
            count($this->statusMap), count($this->languageMap)
        ));
    }

    // ─── Companies ───────────────────────────────────────────────

    private function importCompanies(string $file, int $teamId, int $userId, bool $dryRun): array
    {
        $this->newLine();
        $this->info('Importing Companies...');

        $rows = $this->readCsv($file);
        $this->info("  Found {$rows->count()} companies in CSV");

        $map = [];
        $created = 0;
        $errors = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $hsId = $row['hs_object_id'] ?? '';
            $name = $row['name'] ?? $row['domain'] ?? "Company {$hsId}";

            if (empty($name) || $name === '') {
                $errors++;
                $bar->advance();
                continue;
            }

            $countryCode = self::COUNTRY_NORMALIZE[trim($row['country'] ?? '')] ?? null;
            $countryId = $countryCode ? ($this->countryMap[$countryCode] ?? null) : null;

            $industryHs = trim($row['industry'] ?? '');
            $industryCrm = self::INDUSTRY_MAP[$industryHs] ?? null;
            $industryId = $industryCrm ? ($this->industryMap[$industryCrm] ?? null) : null;

            $notesParts = [];
            if (!empty($row['description'])) {
                $notesParts[] = mb_substr($row['description'], 0, 500);
            }
            $notesParts[] = "[HubSpot Import] ID: {$hsId}";
            if ($industryHs && !$industryCrm) {
                $notesParts[] = "HubSpot Industry: {$industryHs}";
            }

            $data = [
                'name' => $name,
                'team_id' => $teamId,
                'created_by_user_id' => $userId,
                'owned_by_user_id' => $userId,
                'contact_status_id' => $this->statusMap['CUSTOMER'] ?? 3,
                'notes' => implode("\n", $notesParts),
                'is_active' => true,
            ];

            if (!empty($row['domain'])) {
                $domain = $row['domain'];
                $data['website'] = str_starts_with($domain, 'http') ? $domain : "https://{$domain}";
            }
            if ($countryId) $data['country_id'] = $countryId;
            if ($industryId) $data['industry_id'] = $industryId;

            if ($dryRun) {
                $map[$hsId] = 0;
                $created++;
                $bar->advance();
                continue;
            }

            try {
                $company = CrmCompany::create($data);
                $map[$hsId] = $company->id;
                $created++;

                if (!empty($row['phone']) && !$this->option('skip-phones')) {
                    $this->createPhone($company, 'company', trim($row['phone']));
                }

                if (!$this->option('skip-addresses') && (!empty($row['address']) || !empty($row['city']))) {
                    $this->createAddress($company, 'company', $row, $countryId);
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning("HubSpot Import: Company error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Companies: {$created} created, {$errors} errors");

        return $map;
    }

    // ─── Contacts ────────────────────────────────────────────────

    private function importContacts(string $file, int $teamId, int $userId, bool $dryRun, array $companyMap): array
    {
        $this->newLine();
        $this->info('Importing Contacts...');

        $rows = $this->readCsv($file);
        $this->info("  Found {$rows->count()} contacts in CSV");

        $map = [];
        $created = 0;
        $errors = 0;
        $relationsCreated = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $hsId = $row['hs_object_id'] ?? '';
            $firstName = trim($row['firstname'] ?? '');
            $lastName = trim($row['lastname'] ?? '');

            if (empty($firstName) && empty($lastName)) {
                $firstName = $row['email'] ?? "Contact {$hsId}";
            }

            // Lifecycle → Status
            $lifecycle = strtolower(trim($row['lifecyclestage'] ?? ''));
            $statusCode = self::LIFECYCLE_MAP[$lifecycle] ?? 'ACTIVE';
            $statusId = $this->statusMap[$statusCode] ?? $this->statusMap['ACTIVE'] ?? 1;

            // Language
            $langRaw = strtolower(trim($row['hs_language'] ?? ''));
            $langCode = self::LANG_MAP[$langRaw] ?? null;
            $langId = $langCode ? ($this->languageMap[$langCode] ?? null) : null;

            // Gender → Gender + Salutation
            $genderRaw = trim($row['gender'] ?? '');
            $genderId = null;
            $salutationId = null;
            if (strtolower($genderRaw) === 'male') {
                $genderId = $this->genderMap['MALE'] ?? null;
                $salutationId = $this->salutationMap['HERR'] ?? null;
            } elseif (strtolower($genderRaw) === 'female') {
                $genderId = $this->genderMap['FEMALE'] ?? null;
                $salutationId = $this->salutationMap['FRAU'] ?? null;
            }

            // Country
            $countryCode = self::COUNTRY_NORMALIZE[trim($row['country'] ?? '')] ?? null;
            $countryId = $countryCode ? ($this->countryMap[$countryCode] ?? null) : null;

            // Notes with HubSpot metadata
            $notesParts = ["[HubSpot Import] ID: {$hsId}"];
            if (!empty($row['hs_lead_status'])) $notesParts[] = "Lead-Status: {$row['hs_lead_status']}";
            if (!empty($row['interested_in'])) $notesParts[] = "Interested in: {$row['interested_in']}";
            if (!empty($row['beurs_contact'])) $notesParts[] = "Beurs contact: {$row['beurs_contact']}";
            if (!empty($row['wholesaler'])) $notesParts[] = "Wholesaler: {$row['wholesaler']}";
            if (!empty($row['ort'])) $notesParts[] = "Ort: {$row['ort']}";

            $data = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'team_id' => $teamId,
                'created_by_user_id' => $userId,
                'owned_by_user_id' => $userId,
                'contact_status_id' => $statusId,
                'notes' => implode("\n", $notesParts),
                'is_active' => true,
                'is_blacklisted' => false,
            ];

            if ($langId) $data['language_id'] = $langId;
            if ($genderId) $data['gender_id'] = $genderId;
            if ($salutationId) $data['salutation_id'] = $salutationId;
            if ($countryId) $data['country_id'] = $countryId;

            if ($dryRun) {
                $map[$hsId] = 0;
                $created++;
                $bar->advance();
                continue;
            }

            try {
                $contact = CrmContact::create($data);
                $map[$hsId] = $contact->id;
                $created++;

                if (!empty($row['email']) && !$this->option('skip-emails')) {
                    $this->createEmail($contact, 'contact', trim($row['email']));
                }

                if (!empty($row['phone']) && !$this->option('skip-phones')) {
                    $this->createPhone($contact, 'contact', trim($row['phone']));
                }

                if (!$this->option('skip-addresses') && (!empty($row['address']) || !empty($row['city']))) {
                    $this->createAddress($contact, 'contact', $row, $countryId);
                }

                // Contact ↔ Company relation
                if (!$this->option('skip-relations') && !empty($row['associatedcompanyid'])) {
                    $assocHsId = trim($row['associatedcompanyid']);
                    $crmCompanyId = $companyMap[$assocHsId] ?? null;

                    if ($crmCompanyId) {
                        try {
                            CrmContactRelation::create([
                                'contact_id' => $contact->id,
                                'company_id' => $crmCompanyId,
                                'position' => !empty($row['jobtitle']) ? mb_substr($row['jobtitle'], 0, 255) : null,
                                'is_primary' => true,
                            ]);
                            $relationsCreated++;
                        } catch (\Throwable $e) {
                            Log::debug("HubSpot Import: Relation error", [
                                'contact_hs' => $hsId, 'company_hs' => $assocHsId,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning("HubSpot Import: Contact error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Contacts: {$created} created, {$errors} errors, {$relationsCreated} relations");

        return $map;
    }

    // ─── Engagements ─────────────────────────────────────────────

    private function importEngagements(string $path, int $userId, bool $dryRun, array $companyMap, array $contactMap): array
    {
        $this->newLine();
        $this->info('Importing Engagements (Notes, Calls, Meetings, Tasks)...');

        $counts = ['notes' => 0, 'calls' => 0, 'meetings' => 0, 'tasks' => 0];

        // Notes
        if (file_exists($path . '/notes.csv')) {
            $counts['notes'] = $this->importNotes($path . '/notes.csv', $userId, $dryRun, $companyMap, $contactMap);
        }

        // Calls
        if (file_exists($path . '/calls.csv')) {
            $counts['calls'] = $this->importCalls($path . '/calls.csv', $userId, $dryRun, $companyMap, $contactMap);
        }

        // Meetings
        if (file_exists($path . '/meetings.csv')) {
            $counts['meetings'] = $this->importMeetings($path . '/meetings.csv', $userId, $dryRun, $companyMap, $contactMap);
        }

        // Tasks
        if (file_exists($path . '/tasks.csv')) {
            $counts['tasks'] = $this->importTasks($path . '/tasks.csv', $userId, $dryRun, $companyMap, $contactMap);
        }

        return $counts;
    }

    private function importNotes(string $file, int $userId, bool $dryRun, array $companyMap, array $contactMap): int
    {
        $rows = $this->readCsv($file);
        $this->info("  Notes: {$rows->count()} in CSV");

        $created = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $body = $this->stripHtml($row['hs_note_body'] ?? '');
            $timestamp = $this->parseTimestamp($row['hs_timestamp'] ?? $row['hs_createdate'] ?? '');
            $hsId = $row['hs_object_id'] ?? '';

            $subject = $this->resolveActivitySubject($row, $companyMap, $contactMap);

            if (!$subject || $dryRun) {
                if ($dryRun && $subject) $created++;
                $bar->advance();
                continue;
            }

            try {
                $subject->activities()->create([
                    'activity_type' => 'hubspot_import',
                    'name' => 'hubspot_note',
                    'message' => $body ?: null,
                    'user_id' => $userId,
                    'metadata' => array_filter([
                        'hubspot_id' => $hsId,
                        'hubspot_owner_id' => $row['hubspot_owner_id'] ?? null,
                        'timestamp' => $timestamp,
                        'assoc_contacts' => $this->parseAssocIds($row['assoc_contacts'] ?? ''),
                        'assoc_companies' => $this->parseAssocIds($row['assoc_companies'] ?? ''),
                        'assoc_deals' => $this->parseAssocIds($row['assoc_deals'] ?? ''),
                    ]),
                    'created_at' => $timestamp ?? now(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                Log::debug("HubSpot Import: Note error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Notes: {$created} created");

        return $created;
    }

    private function importCalls(string $file, int $userId, bool $dryRun, array $companyMap, array $contactMap): int
    {
        $rows = $this->readCsv($file);
        $this->info("  Calls: {$rows->count()} in CSV");

        $created = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $body = $this->stripHtml($row['hs_call_body'] ?? '');
            $title = $row['hs_call_title'] ?? '';
            $timestamp = $this->parseTimestamp($row['hs_timestamp'] ?? $row['hs_createdate'] ?? '');
            $hsId = $row['hs_object_id'] ?? '';

            $subject = $this->resolveActivitySubject($row, $companyMap, $contactMap);

            if (!$subject || $dryRun) {
                if ($dryRun && $subject) $created++;
                $bar->advance();
                continue;
            }

            $message = $title ? "{$title}\n\n{$body}" : $body;

            try {
                $subject->activities()->create([
                    'activity_type' => 'hubspot_import',
                    'name' => 'hubspot_call',
                    'message' => $message ?: null,
                    'user_id' => $userId,
                    'metadata' => array_filter([
                        'hubspot_id' => $hsId,
                        'hubspot_owner_id' => $row['hubspot_owner_id'] ?? null,
                        'timestamp' => $timestamp,
                        'direction' => $row['hs_call_direction'] ?? null,
                        'duration_ms' => $row['hs_call_duration'] ?? null,
                        'status' => $row['hs_call_status'] ?? null,
                        'assoc_contacts' => $this->parseAssocIds($row['assoc_contacts'] ?? ''),
                        'assoc_companies' => $this->parseAssocIds($row['assoc_companies'] ?? ''),
                        'assoc_deals' => $this->parseAssocIds($row['assoc_deals'] ?? ''),
                    ]),
                    'created_at' => $timestamp ?? now(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                Log::debug("HubSpot Import: Call error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Calls: {$created} created");

        return $created;
    }

    private function importMeetings(string $file, int $userId, bool $dryRun, array $companyMap, array $contactMap): int
    {
        $rows = $this->readCsv($file);
        $this->info("  Meetings: {$rows->count()} in CSV");

        $created = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $body = $this->stripHtml($row['hs_meeting_body'] ?? '');
            $title = $row['hs_meeting_title'] ?? '';
            $timestamp = $this->parseTimestamp($row['hs_meeting_start_time'] ?? $row['hs_timestamp'] ?? $row['hs_createdate'] ?? '');
            $hsId = $row['hs_object_id'] ?? '';

            $subject = $this->resolveActivitySubject($row, $companyMap, $contactMap);

            if (!$subject || $dryRun) {
                if ($dryRun && $subject) $created++;
                $bar->advance();
                continue;
            }

            $message = $title ? "{$title}\n\n{$body}" : $body;

            try {
                $subject->activities()->create([
                    'activity_type' => 'hubspot_import',
                    'name' => 'hubspot_meeting',
                    'message' => $message ?: null,
                    'user_id' => $userId,
                    'metadata' => array_filter([
                        'hubspot_id' => $hsId,
                        'hubspot_owner_id' => $row['hubspot_owner_id'] ?? null,
                        'timestamp' => $timestamp,
                        'start_time' => $this->parseTimestamp($row['hs_meeting_start_time'] ?? ''),
                        'end_time' => $this->parseTimestamp($row['hs_meeting_end_time'] ?? ''),
                        'location' => $row['hs_meeting_location'] ?? null,
                        'outcome' => $row['hs_meeting_outcome'] ?? null,
                        'assoc_contacts' => $this->parseAssocIds($row['assoc_contacts'] ?? ''),
                        'assoc_companies' => $this->parseAssocIds($row['assoc_companies'] ?? ''),
                        'assoc_deals' => $this->parseAssocIds($row['assoc_deals'] ?? ''),
                    ]),
                    'created_at' => $timestamp ?? now(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                Log::debug("HubSpot Import: Meeting error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Meetings: {$created} created");

        return $created;
    }

    private function importTasks(string $file, int $userId, bool $dryRun, array $companyMap, array $contactMap): int
    {
        $rows = $this->readCsv($file);
        $this->info("  Tasks: {$rows->count()} in CSV");

        $created = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $body = $this->stripHtml($row['hs_task_body'] ?? '');
            $taskSubject = $row['hs_task_subject'] ?? '';
            $timestamp = $this->parseTimestamp($row['hs_timestamp'] ?? $row['hs_createdate'] ?? '');
            $hsId = $row['hs_object_id'] ?? '';

            $subject = $this->resolveActivitySubject($row, $companyMap, $contactMap);

            if (!$subject || $dryRun) {
                if ($dryRun && $subject) $created++;
                $bar->advance();
                continue;
            }

            $message = $taskSubject ? "{$taskSubject}\n\n{$body}" : $body;

            try {
                $subject->activities()->create([
                    'activity_type' => 'hubspot_import',
                    'name' => 'hubspot_task',
                    'message' => $message ?: null,
                    'user_id' => $userId,
                    'metadata' => array_filter([
                        'hubspot_id' => $hsId,
                        'hubspot_owner_id' => $row['hubspot_owner_id'] ?? null,
                        'timestamp' => $timestamp,
                        'status' => $row['hs_task_status'] ?? null,
                        'priority' => $row['hs_task_priority'] ?? null,
                        'task_type' => $row['hs_task_type'] ?? null,
                        'assoc_contacts' => $this->parseAssocIds($row['assoc_contacts'] ?? ''),
                        'assoc_companies' => $this->parseAssocIds($row['assoc_companies'] ?? ''),
                        'assoc_deals' => $this->parseAssocIds($row['assoc_deals'] ?? ''),
                    ]),
                    'created_at' => $timestamp ?? now(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                Log::debug("HubSpot Import: Task error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Tasks: {$created} created");

        return $created;
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Resolve the primary subject (CrmCompany or CrmContact) for an engagement.
     * Priority: company first (most engagements are company-scoped), then contact.
     */
    private function resolveActivitySubject(array $row, array $companyMap, array $contactMap): ?object
    {
        // Try company first
        $companyIds = $this->parseAssocIds($row['assoc_companies'] ?? '');
        foreach ($companyIds as $hsCompanyId) {
            $crmId = $companyMap[$hsCompanyId] ?? null;
            if ($crmId) {
                return CrmCompany::find($crmId);
            }
        }

        // Fallback to contact
        $contactIds = $this->parseAssocIds($row['assoc_contacts'] ?? '');
        foreach ($contactIds as $hsContactId) {
            $crmId = $contactMap[$hsContactId] ?? null;
            if ($crmId) {
                return CrmContact::find($crmId);
            }
        }

        return null;
    }

    /**
     * Parse semicolon-separated association IDs from CSV.
     */
    private function parseAssocIds(string $value): array
    {
        $value = trim($value);
        if ($value === '') return [];

        return array_filter(array_map('trim', explode(';', $value)));
    }

    private function stripHtml(string $html): string
    {
        if (empty($html)) return '';

        // Convert <br>, <p>, <div> to newlines before stripping
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/(p|div|li)>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Collapse multiple blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function parseTimestamp(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function createEmail($model, string $entityType, string $email): void
    {
        try {
            $model->emailAddresses()->create([
                'emailable_type' => $entityType === 'contact'
                    ? CrmContact::class
                    : CrmCompany::class,
                'email_address' => $email,
                'email_type_id' => $this->emailTypeMap['WORK'] ?? $this->emailTypeMap['Geschäftlich'] ?? array_values($this->emailTypeMap)[0] ?? 1,
                'is_primary' => true,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            Log::debug("HubSpot Import: Email error for {$entityType}#{$model->id}", ['error' => $e->getMessage()]);
        }
    }

    private function createPhone($model, string $entityType, string $phone): void
    {
        try {
            $model->phoneNumbers()->create([
                'phoneable_type' => $entityType === 'contact'
                    ? CrmContact::class
                    : CrmCompany::class,
                'raw_input' => $phone,
                'phone_type_id' => $this->phoneTypeMap['WORK'] ?? $this->phoneTypeMap['Geschäftlich'] ?? array_values($this->phoneTypeMap)[0] ?? 1,
                'is_primary' => true,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            Log::debug("HubSpot Import: Phone error for {$entityType}#{$model->id}", ['error' => $e->getMessage()]);
        }
    }

    private function createAddress($model, string $entityType, array $row, ?int $countryId): void
    {
        try {
            $model->postalAddresses()->create([
                'addressable_type' => $entityType === 'contact'
                    ? CrmContact::class
                    : CrmCompany::class,
                'street' => trim($row['address'] ?? ''),
                'postal_code' => trim($row['zip'] ?? ''),
                'city' => trim($row['city'] ?? ''),
                'country_id' => $countryId,
                'address_type_id' => $this->addressTypeMap['BUSINESS'] ?? $this->addressTypeMap['Geschäftlich'] ?? array_values($this->addressTypeMap)[0] ?? 1,
                'is_primary' => true,
                'is_active' => true,
            ]);
        } catch (\Throwable $e) {
            Log::debug("HubSpot Import: Address error for {$entityType}#{$model->id}", ['error' => $e->getMessage()]);
        }
    }

    private function readCsv(string $file): \Illuminate\Support\Collection
    {
        $rows = collect();
        if (($handle = fopen($file, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) === count($headers)) {
                    $rows->push(array_combine($headers, $data));
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}
