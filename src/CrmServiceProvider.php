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