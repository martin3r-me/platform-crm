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
    }

    public function boot(): void
    {
        \Log::info('CrmServiceProvider: Boot gestartet');
        
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

        // Schritt 4: Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Schritt 5: Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/crm.php' => config_path('crm.php'),
        ], 'config');

        // Schritt 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'crm');
        $this->registerLivewireComponents();

        // Schritt 7: Model-Schemata automatisch registrieren lassen
        (new \Platform\Core\Services\ModelAutoRegistrar())->scanAndRegister();
        

        // Schritt 8: Commands registrieren
        \Log::info('CrmServiceProvider: Registriere CRM-Commands...');
        \Platform\Core\Registry\CommandRegistry::register('crm', [
            [
                'key' => 'crm.query',
                'description' => 'Generische Abfrage für CRM-Entitäten.',
                'parameters' => [
                    ['name' => 'model', 'type' => 'string', 'required' => true, 'description' => 'contacts|companies|deals|etc'],
                    ['name' => 'q', 'type' => 'string', 'required' => false],
                    ['name' => 'filters', 'type' => 'object', 'required' => false],
                    ['name' => 'sort', 'type' => 'string', 'required' => false],
                    ['name' => 'order', 'type' => 'string', 'required' => false],
                    ['name' => 'limit', 'type' => 'integer', 'required' => false],
                    ['name' => 'fields', 'type' => 'string', 'required' => false],
                ],
                'impact' => 'low',
                'confirmRequired' => false,
                'autoAllowed' => true,
                'phrases' => [
                    'suche {model} {q}',
                    'zeige {model}',
                    'übersicht {model}',
                    'meine kontakte',
                    'zeige kontakte',
                ],
                'slots' => [ ['name' => 'model'], ['name' => 'q'] ],
                'guard' => 'web',
                'handler' => ['service', \Platform\Crm\Services\CrmCommandService::class.'@query'],
                'scope' => 'read:crm',
                'examples' => [
                    ['desc' => 'Kontakte anzeigen', 'slots' => ['model' => 'crm.contacts']],
                    ['desc' => 'Unternehmen anzeigen', 'slots' => ['model' => 'crm.companies']],
                    ['desc' => 'Kontakte suchen', 'slots' => ['model' => 'crm.contacts', 'q' => 'Max']],
                ],
            ],
            [
                'key' => 'crm.open',
                'description' => 'Generisches Öffnen (Navigation) für CRM-Entitäten.',
                'parameters' => [
                    ['name' => 'model', 'type' => 'string', 'required' => true, 'description' => 'contact|company|deal'],
                    ['name' => 'id', 'type' => 'integer', 'required' => false],
                    ['name' => 'uuid', 'type' => 'string', 'required' => false],
                    ['name' => 'name', 'type' => 'string', 'required' => false],
                ],
                'impact' => 'low',
                'confirmRequired' => false,
                'autoAllowed' => true,
                'phrases' => [
                    'öffne {model} {id}',
                    'öffne {model} {name}',
                    'zeige {model} {name}',
                    'gehe zu {model} {name}',
                ],
                'slots' => [ ['name' => 'model'], ['name' => 'id'], ['name' => 'name'] ],
                'guard' => 'web',
                'handler' => ['service', \Platform\Crm\Services\CrmCommandService::class.'@open'],
                'scope' => 'read:crm',
                'examples' => [
                    ['desc' => 'Kontakt öffnen', 'slots' => ['model' => 'crm.contacts', 'name' => 'Max Mustermann']],
                    ['desc' => 'Unternehmen öffnen', 'slots' => ['model' => 'crm.companies', 'name' => 'ACME Corp']],
                ],
            ],
            [
                'key' => 'crm.create',
                'description' => 'Generisches Anlegen (schema-validiert) für CRM-Entitäten.',
                'parameters' => [
                    ['name' => 'model', 'type' => 'string', 'required' => true],
                    ['name' => 'data', 'type' => 'object', 'required' => true],
                ],
                'impact' => 'medium',
                'confirmRequired' => true,
                'autoAllowed' => false,
                'phrases' => [ 'erstelle {model}', 'lege {model} an' ],
                'slots' => [ ['name' => 'model'], ['name' => 'data'] ],
                'guard' => 'web',
                'handler' => ['service', \Platform\Crm\Services\CrmCommandService::class.'@create'],
                'scope' => 'write:crm',
                'examples' => [
                    ['desc' => 'Kontakt anlegen', 'slots' => ['model' => 'crm.contacts', 'data' => ['first_name' => 'Max', 'last_name' => 'Mustermann']]],
                    ['desc' => 'Unternehmen anlegen', 'slots' => ['model' => 'crm.companies', 'data' => ['name' => 'ACME Corp']]],
                ],
            ],
        ]);
        \Log::info('CrmServiceProvider: CRM-Commands registriert!');

        // Dynamische Routen als Tools exportieren
        \Platform\Core\Services\RouteToolExporter::registerModuleRoutes('crm');
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
    

}