<?php

namespace App\Providers;

use App\Models\Addon;
use App\Models\BlockedDate;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voucher;
use App\Policies\AdminOnlyPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([
            Package::class,
            Addon::class,
            Voucher::class,
            Payment::class,
            User::class,
            BlockedDate::class,
        ] as $model) {
            Gate::policy($model, AdminOnlyPolicy::class);
        }
    }
}
