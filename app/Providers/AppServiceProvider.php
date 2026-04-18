<?php

declare(strict_types=1);

namespace App\Providers;

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
    }
}
