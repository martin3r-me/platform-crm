<?php

namespace Platform\Crm;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;
use Platform\Crm\Services\CoreCrmCompanyOptionsProvider;
use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Crm\Services\CoreCrmCompanyResolver;
use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Crm\Services\CoreCrmContactOptionsProvider;
use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Crm\Services\CoreCrmContactResolver;
use Platform\Core\Contracts\CrmCompanyContactsProviderInterface;
use Platform\Crm\Services\CoreCrmCompanyContactsProvider;
use Illuminate\Support\Facades\Gate;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Policies\CrmContactPolicy;
use Platform\Crm\Policies\CrmCompanyPolicy;

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Commands registrieren
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Crm\Console\Commands\SeedCrmLookupData::class,
            ]);
        }
        
        // Services registrieren
        $this->app->singleton(\Platform\Crm\Services\ContactLinkService::class);

        // Core Contracts binden (überschreiben Null-Implementierungen)
        $this->app->singleton(CrmCompanyOptionsProviderInterface::class, fn() => new CoreCrmCompanyOptionsProvider());
        $this->app->singleton(CrmCompanyResolverInterface::class, fn() => new CoreCrmCompanyResolver());
        $this->app->singleton(CrmContactOptionsProviderInterface::class, fn() => new CoreCrmContactOptionsProvider());
        $this->app->singleton(CrmContactResolverInterface::class, fn() => new CoreCrmContactResolver());
        $this->app->singleton(CrmCompanyContactsProviderInterface::class, fn() => new CoreCrmCompanyContactsProvider());
    }

    public function boot(): void
    {
        // Debug-Logs entfernt
        
        // Schritt 1: Config laden
        $this->mergeConfigFrom(__DIR__.'/../config/crm.php', 'crm');
        


        // Schritt 2: Existenzprüfung (config jetzt verfügbar)
        if (
            config()->has('crm.routing') &&
            config()->has('crm.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'crm',
                'title'      => 'Crm',
                'routing'    => config('crm.routing'),
                'guard'      => config('crm.guard'),
                'navigation' => config('crm.navigation'),
                'sidebar'    => config('crm.sidebar'),
            ]);
        }

        // Schritt 3: Wenn Modul registriert, Routes laden
        if (PlatformCore::getModule('crm')) {
            ModuleRouter::group('crm', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/guest.php');
            }, requireAuth: false);

            ModuleRouter::group('crm', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Comms Webhook Routes (ohne Auth, da externe Webhooks)
        $this->loadRoutesFrom(__DIR__.'/../routes/comms-webhooks.php');

        // Schritt 4: Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Schritt 5: Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/crm.php' => config_path('crm.php'),
        ], 'config');

        // Schritt 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'crm');
        $this->registerLivewireComponents();

        // Modelle-Scan & Schema-Registry entfernt (war für Agent)

        // Commands entfernt - Sidebar soll leer sein

        // RouteToolExporter entfernt - Sidebar soll leer sein
        
        // Tools registrieren (loose gekoppelt - für AI/Chat)
        $this->registerTools();

        // Policies registrieren (wie Planner: Gate/Policies in Tools nutzbar machen)
        $this->registerPolicies();

        // CommsContactResolver registrieren (für WhatsApp, Email etc.)
        $this->registerCommsContactResolver();

        // WhatsApp Channel Sync Listener registrieren
        $this->registerWhatsAppChannelSyncListener();

        // ModalComms Livewire Komponente registrieren
        \Livewire\Livewire::component('crm.modal-comms', \Platform\Crm\Livewire\ModalComms::class);
    }

    /**
     * Registriert den WhatsApp Channel Sync Listener.
     */
    protected function registerWhatsAppChannelSyncListener(): void
    {
        try {
            if (class_exists(\Platform\Integrations\Events\WhatsAppAccountsSynced::class)) {
                \Illuminate\Support\Facades\Event::listen(
                    \Platform\Integrations\Events\WhatsAppAccountsSynced::class,
                    \Platform\Crm\Listeners\SyncWhatsAppChannelsListener::class
                );
            }
        } catch (\Throwable $e) {
            // Silent fail - Integrations module might not be installed
        }
    }
    
    /**
     * Registriert CRM-Tools für die AI/Chat-Funktionalität
     * 
     * HINWEIS: Tools werden auch automatisch via Auto-Discovery gefunden,
     * aber manuelle Registrierung stellt sicher, dass sie verfügbar sind.
     */
    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);
            
            // Company-Tools
            $registry->register(new \Platform\Crm\Tools\ListCompaniesTool());
            $registry->register(new \Platform\Crm\Tools\GetCompanyTool());
            $registry->register(new \Platform\Crm\Tools\CreateCompanyTool());
            $registry->register(new \Platform\Crm\Tools\UpdateCompanyTool());
            $registry->register(new \Platform\Crm\Tools\DeleteCompanyTool());
            
            // Contact-Tools
            $registry->register(new \Platform\Crm\Tools\ListContactsTool());
            $registry->register(new \Platform\Crm\Tools\GetContactTool());
            $registry->register(new \Platform\Crm\Tools\CreateContactTool());
            $registry->register(new \Platform\Crm\Tools\UpdateContactTool());
            $registry->register(new \Platform\Crm\Tools\DeleteContactTool());

            // Communication Tools (Phone/Email)
            $registry->register(new \Platform\Crm\Tools\CreateEmailAddressTool());
            $registry->register(new \Platform\Crm\Tools\UpdateEmailAddressTool());
            $registry->register(new \Platform\Crm\Tools\DeleteEmailAddressTool());
            $registry->register(new \Platform\Crm\Tools\CreatePhoneNumberTool());
            $registry->register(new \Platform\Crm\Tools\UpdatePhoneNumberTool());
            $registry->register(new \Platform\Crm\Tools\DeletePhoneNumberTool());

            // Postal Address Tools
            $registry->register(new \Platform\Crm\Tools\CreatePostalAddressTool());
            $registry->register(new \Platform\Crm\Tools\UpdatePostalAddressTool());
            $registry->register(new \Platform\Crm\Tools\DeletePostalAddressTool());

            // Bulk Contact-Tools
            $registry->register(new \Platform\Crm\Tools\BulkCreateContactsTool());
            $registry->register(new \Platform\Crm\Tools\BulkUpdateContactsTool());
            $registry->register(new \Platform\Crm\Tools\BulkDeleteContactsTool());

            // Bulk Company-Tools
            $registry->register(new \Platform\Crm\Tools\BulkCreateCompaniesTool());
            $registry->register(new \Platform\Crm\Tools\BulkUpdateCompaniesTool());
            $registry->register(new \Platform\Crm\Tools\BulkDeleteCompaniesTool());

            // Contact↔Company Relations
            $registry->register(new \Platform\Crm\Tools\CreateContactRelationTool());
            $registry->register(new \Platform\Crm\Tools\UpdateContactRelationTool());
            $registry->register(new \Platform\Crm\Tools\DeleteContactRelationTool());

            // Lookup Tools (IDs/Codes deterministisch nachschlagen – niemals raten)
            $registry->register(new \Platform\Crm\Tools\CrmLookupsTool());
            $registry->register(new \Platform\Crm\Tools\GetLookupTool());

            // Communication Tools (crm.comms.*)
            $registry->register(new \Platform\Crm\Tools\Comms\CommsOverviewTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ListChannelsTool());
            $registry->register(new \Platform\Crm\Tools\Comms\CreateChannelTool());
            $registry->register(new \Platform\Crm\Tools\Comms\UpdateChannelTool());
            $registry->register(new \Platform\Crm\Tools\Comms\DeleteChannelTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ListEmailThreadsTool());
            $registry->register(new \Platform\Crm\Tools\Comms\CreateEmailThreadTool());
            $registry->register(new \Platform\Crm\Tools\Comms\UpdateEmailThreadTool());
            $registry->register(new \Platform\Crm\Tools\Comms\DeleteEmailThreadTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ListEmailMessagesTool());
            $registry->register(new \Platform\Crm\Tools\Comms\SendEmailMessageTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ListWhatsAppThreadsTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ListWhatsAppMessagesTool());
            $registry->register(new \Platform\Crm\Tools\Comms\SendWhatsAppMessageTool());
            $registry->register(new \Platform\Crm\Tools\Comms\UpdateWhatsAppThreadTool());

            // WhatsApp LLM-Tools (wa_contacts, wa_threads, wa_messages, wa_overview)
            $registry->register(new \Platform\Crm\Tools\Comms\ListWhatsAppContactsTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ShowWhatsAppContactTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ListWhatsAppConversationThreadsTool());
            $registry->register(new \Platform\Crm\Tools\Comms\CreateWhatsAppConversationThreadTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ShowWhatsAppConversationThreadTool());
            $registry->register(new \Platform\Crm\Tools\Comms\ShowWhatsAppMessageTool());
            $registry->register(new \Platform\Crm\Tools\Comms\SearchWhatsAppMessagesTool());
            $registry->register(new \Platform\Crm\Tools\Comms\WhatsAppOverviewTool());
        } catch (\Throwable $e) {
            // Silent fail - ToolRegistry möglicherweise nicht verfügbar
            \Log::warning('CRM: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Registriert Policies für das CRM-Modul
     */
    protected function registerPolicies(): void
    {
        $policies = [
            CrmContact::class => CrmContactPolicy::class,
            CrmCompany::class => CrmCompanyPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }


    /**
     * Registriert den CRM CommsContactResolver für Inbound-Kommunikation.
     *
     * Ermöglicht WhatsApp, Email etc. Kontakte im CRM zu finden/erstellen.
     */
    protected function registerCommsContactResolver(): void
    {
        try {
            $registry = resolve(\Platform\Core\Services\Comms\ContactResolverRegistry::class);
            $registry->register(new \Platform\Crm\Services\CrmCommsContactResolver());
        } catch (\Throwable $e) {
            // Silent fail - Registry möglicherweise nicht verfügbar
            \Log::debug('CRM: CommsContactResolver-Registrierung übersprungen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Crm\\Livewire';
        $prefix = 'crm';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            // crm.contact.index aus crm + contact/index.php
            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    protected function registerCrmModels(): void
    {
        $baseNs = 'Platform\\Crm\\Models\\';
        $baseDir = __DIR__ . '/Models';
        if (!is_dir($baseDir)) {
            return;
        }
        foreach (scandir($baseDir) as $file) {
            if (!str_ends_with($file, '.php')) continue;
            $class = $baseNs . pathinfo($file, PATHINFO_FILENAME);
            if (!class_exists($class)) continue;
            try {
                $model = new $class();
                if (!method_exists($model, 'getTable')) continue;
                $table = $model->getTable();
                if (!\Illuminate\Support\Facades\Schema::hasTable($table)) continue;
                $moduleKey = \Illuminate\Support\Str::before($table, '_');
                $entityKey = \Illuminate\Support\Str::after($table, '_');
                if ($moduleKey !== 'crm' || $entityKey === '') continue;
                $modelKey = $moduleKey.'.'.$entityKey;
                $this->registerModel($modelKey, $class);
            } catch (\Throwable $e) {
                \Log::info('CrmServiceProvider: Scan-Registrierung übersprungen für '.$class.': '.$e->getMessage());
                continue;
            }
        }
    }

    protected function registerModel(string $modelKey, string $eloquentClass): void
    {
        if (!class_exists($eloquentClass)) { return; }

        $model = new $eloquentClass();
        $table = $model->getTable();
        if (!\Illuminate\Support\Facades\Schema::hasTable($table)) { return; }

        // Basis-Daten
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing($table);
        $fields = array_values($columns);
        $selectable = array_values(array_slice($fields, 0, 6));
        $writable = $model->getFillable();
        $sortable = array_values(array_intersect($fields, ['id','name','title','created_at','updated_at']));
        $filterable = array_values(array_intersect($fields, ['id','uuid','name','title','team_id','user_id','status','is_done']));
        $labelKey = in_array('name', $fields, true) ? 'name' : (in_array('title', $fields, true) ? 'title' : 'id');

        // Required-Felder per Doctrine DBAL
        $required = [];
        try {
            $connection = \DB::connection();
            $schemaManager = method_exists($connection, 'getDoctrineSchemaManager')
                ? $connection->getDoctrineSchemaManager()
                : ($connection->getDoctrineSchemaManager ?? null);
            if ($schemaManager) {
                $doctrineTable = $schemaManager->listTableDetails($table);
                foreach ($doctrineTable->getColumns() as $col) {
                    $name = $col->getName();
                    if ($name === 'id' || $col->getAutoincrement()) continue;
                    $notNull = !$col->getNotnull(); // Doctrine returns true for nullable
                    $hasDefault = $col->getDefault() !== null;
                    if ($notNull && !$hasDefault) {
                        $required[] = $name;
                    }
                }
                $required = array_values(array_intersect($required, $fields));
            }
        } catch (\Throwable $e) {
            $required = [];
        }

        // Relations (belongsTo) per Reflection
        $relations = [];
        $foreignKeys = [];
        try {
            $ref = new \ReflectionClass($eloquentClass);
            foreach ($ref->getMethods() as $method) {
                if (!$method->isPublic() || $method->isStatic()) continue;
                if ($method->getNumberOfParameters() > 0) continue;
                if ($method->getDeclaringClass()->getName() !== $eloquentClass) continue;
                $name = $method->getName();

                // DocComment für belongsTo-Relationen parsen
                $docComment = $method->getDocComment();
                if ($docComment && preg_match('/@return \s*\\\\Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo<([^>]+)>/', $docComment, $matches)) {
                    $targetClass = $matches[1];
                    if (class_exists($targetClass)) {
                        $targetModel = new $targetClass();
                        $targetTable = $targetModel->getTable();
                        $targetModuleKey = \Illuminate\Support\Str::before($targetTable, '_');
                        $targetEntityKey = \Illuminate\Support\Str::after($targetTable, '_');
                        $targetModelKey = $targetModuleKey . '.' . $targetEntityKey;

                        // Versuche, foreign_key und owner_key zu erraten
                        $fk = \Illuminate\Support\Str::snake($name) . '_id';
                        $ownerKey = 'id';

                        // Überprüfung, ob die Spalte im aktuellen Modell existiert
                        if (in_array($fk, $fields, true)) {
                            $relations[$name] = [
                                'type' => 'belongsTo',
                                'target' => $targetModelKey,
                                'foreign_key' => $fk,
                                'owner_key' => $ownerKey,
                                'fields' => ['id', \Platform\Core\Schema\ModelSchemaRegistry::meta($targetModelKey, 'label_key') ?: 'name'],
                            ];
                            $foreignKeys[$fk] = [
                                'references' => $targetModelKey,
                                'field' => $ownerKey,
                                'label_key' => \Platform\Core\Schema\ModelSchemaRegistry::meta($targetModelKey, 'label_key') ?: 'name',
                            ];
                        }
                    }
                }
                // HasMany erkennen
                if ($docComment && preg_match('/@return \s*\\\\Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany<([^>]+)>/', $docComment, $m2)) {
                    $tClass = $m2[1];
                    if (class_exists($tClass)) {
                        $tModel = new $tClass();
                        $tTable = $tModel->getTable();
                        $tMod = \Illuminate\Support\Str::before($tTable, '_');
                        $tEnt = \Illuminate\Support\Str::after($tTable, '_');
                        $tKey = $tMod.'.'.$tEnt;
                        $relations[$name] = [ 'type' => 'hasMany', 'target' => $tKey ];
                    }
                }
                // BelongsToMany erkennen
                if ($docComment && preg_match('/@return \s*\\\\Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsToMany<([^>]+)>/', $docComment, $m3)) {
                    $tClass = $m3[1];
                    if (class_exists($tClass)) {
                        $tModel = new $tClass();
                        $tTable = $tModel->getTable();
                        $tMod = \Illuminate\Support\Str::before($tTable, '_');
                        $tEnt = \Illuminate\Support\Str::after($tTable, '_');
                        $tKey = $tMod.'.'.$tEnt;
                        $relations[$name] = [ 'type' => 'belongsToMany', 'target' => $tKey ];
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::info("CrmServiceProvider: Fehler beim Ermitteln der Relationen für {$eloquentClass}: " . $e->getMessage());
        }

        // Enums und sprachmodell-relevante Daten
        $enums = [];
        $descriptions = [];
        try {
            $ref = new \ReflectionClass($eloquentClass);
            foreach ($ref->getProperties() as $property) {
                $docComment = $property->getDocComment();
                if ($docComment) {
                    // Enum-Definitionen finden
                    if (preg_match('/@var\s+([A-Za-z0-9\\\\]+)/', $docComment, $matches)) {
                        $type = $matches[1];
                        if (str_contains($type, 'Enum') || str_contains($type, 'Status')) {
                            $enums[$property->getName()] = $type;
                        }
                    }
                    // Beschreibungen finden
                    if (preg_match('/@description\s+(.+)/', $docComment, $matches)) {
                        $descriptions[$property->getName()] = $matches[1];
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Schema-Registry Registrierung entfernt
    }

}