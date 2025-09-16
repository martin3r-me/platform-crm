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

class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Falls in Zukunft Artisan Commands o.ä. nötig sind, hier rein
        
        // Services registrieren
        $this->app->singleton(\Platform\Crm\Services\ContactLinkService::class);

        // Core Contracts binden (überschreiben Null-Implementierungen)
        $this->app->singleton(CrmCompanyOptionsProviderInterface::class, fn() => new CoreCrmCompanyOptionsProvider());
        $this->app->singleton(CrmCompanyResolverInterface::class, fn() => new CoreCrmCompanyResolver());
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