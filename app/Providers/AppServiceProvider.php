<?php

namespace App\Providers;

use App\Contracts\BillingGateway;
use App\Contracts\Fakes\FakeBillingGateway;
use App\Contracts\Fakes\FakePacProvider;
use App\Contracts\Fakes\FakeRoutingProvider;
use App\Contracts\Fakes\FakeTelematicsProvider;
use App\Contracts\Fakes\FakeTollCatalog;
use App\Contracts\PacProvider;
use App\Contracts\RoutingProvider;
use App\Contracts\TelematicsProvider;
use App\Contracts\TollCatalog;
use App\Data\TeamContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TeamContext::class);

        // Every external provider sits behind a contract with a fake, so the
        // domain never depends on a vendor SDK. Real adapters are bound in
        // production as the fiscal (P6), tracking (P5), optimization (P8), and
        // SaaS-billing (P9) phases land.
        $this->app->bind(PacProvider::class, FakePacProvider::class);
        $this->app->bind(TelematicsProvider::class, FakeTelematicsProvider::class);
        $this->app->bind(RoutingProvider::class, FakeRoutingProvider::class);
        $this->app->bind(TollCatalog::class, FakeTollCatalog::class);
        $this->app->bind(BillingGateway::class, FakeBillingGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
