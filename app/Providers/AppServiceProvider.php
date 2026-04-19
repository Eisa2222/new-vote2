<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Shared\Services\SettingsService;
use App\Modules\Shared\Support\MailConfig;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Use the brand-styled paginator view everywhere, so the
        // list screens share the same look without each view
        // having to call ->links('vendor.pagination.brand').
        Paginator::defaultView('vendor.pagination.brand');
        Paginator::defaultSimpleView('vendor.pagination.brand');

        // Apply SMTP settings stored in the DB (Settings → Mail) on top
        // of the .env-driven defaults. Wrapped in a try/catch so a bad
        // / missing settings row never crashes the whole request — in
        // the worst case we fall back to .env (which is usually
        // MAIL_MAILER=log for local and real SMTP in prod).
        try {
            MailConfig::apply($this->app->make(SettingsService::class));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
