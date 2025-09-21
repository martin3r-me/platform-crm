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
        
        // Seeder registrieren
        $this->app->singleton('CrmSalutationSeeder', \Platform\Crm\Database\Seeders\CrmSalutationSeeder::class);
        $this->app->singleton('CrmContactStatusSeeder', \Platform\Crm\Database\Seeders\CrmContactStatusSeeder::class);
        $this->app->singleton('CrmGenderSeeder', \Platform\Crm\Database\Seeders\CrmGenderSeeder::class);
        $this->app->singleton('CrmLanguageSeeder', \Platform\Crm\Database\Seeders\CrmLanguageSeeder::class);
        $this->app->singleton('CrmAcademicTitleSeeder', \Platform\Crm\Database\Seeders\CrmAcademicTitleSeeder::class);
        $this->app->singleton('CrmEmailTypeSeeder', \Platform\Crm\Database\Seeders\CrmEmailTypeSeeder::class);
        $this->app->singleton('CrmPhoneTypeSeeder', \Platform\Crm\Database\Seeders\CrmPhoneTypeSeeder::class);
        $this->app->singleton('CrmAddressTypeSeeder', \Platform\Crm\Database\Seeders\CrmAddressTypeSeeder::class);
        $this->app->singleton('CrmLegalFormSeeder', \Platform\Crm\Database\Seeders\CrmLegalFormSeeder::class);
        $this->app->singleton('CrmIndustrySeeder', \Platform\Crm\Database\Seeders\CrmIndustrySeeder::class);
        $this->app->singleton('CrmCountrySeeder', \Platform\Crm\Database\Seeders\CrmCountrySeeder::class);
        $this->app->singleton('CrmStateSeeder', \Platform\Crm\Database\Seeders\CrmStateSeeder::class);
        $this->app->singleton('CrmContactRelationTypeSeeder', \Platform\Crm\Database\Seeders\CrmContactRelationTypeSeeder::class);
        
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