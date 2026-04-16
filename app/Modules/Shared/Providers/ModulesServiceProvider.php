<?php

declare(strict_types=1);

namespace App\Modules\Shared\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class ModulesServiceProvider extends ServiceProvider
{
    /** Modules that expose routes / migrations / views / policies. */
    private const array MODULES = [
        'Shared', 'Users', 'Clubs', 'Sports', 'Players',
        'Campaigns', 'Voting', 'Results',
    ];

    public function register(): void
    {
        foreach (self::MODULES as $module) {
            $config = base_path("app/Modules/{$module}/config.php");
            if (file_exists($config)) {
                $this->mergeConfigFrom($config, strtolower($module));
            }
        }
    }

    public function boot(): void
    {
        foreach (self::MODULES as $module) {
            $this->loadRoutes($module);
            $this->loadMigrations($module);
            $this->loadViews($module);
            $this->loadPolicies($module);
        }
    }

    private function loadRoutes(string $module): void
    {
        $api = base_path("app/Modules/{$module}/routes/api.php");
        $web = base_path("app/Modules/{$module}/routes/web.php");

        if (file_exists($api)) {
            Route::middleware('api')->prefix('api')->group($api);
        }
        if (file_exists($web)) {
            Route::middleware('web')->group($web);
        }
    }

    private function loadMigrations(string $module): void
    {
        $path = base_path("app/Modules/{$module}/database/migrations");
        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function loadViews(string $module): void
    {
        $path = base_path("app/Modules/{$module}/resources/views");
        if (is_dir($path)) {
            $this->loadViewsFrom($path, strtolower($module));
        }
    }

    private function loadPolicies(string $module): void
    {
        $file = base_path("app/Modules/{$module}/policies.php");
        if (file_exists($file)) {
            $map = require $file;
            foreach ($map as $model => $policy) {
                \Illuminate\Support\Facades\Gate::policy($model, $policy);
            }
        }
    }
}
