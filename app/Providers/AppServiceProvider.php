<?php

namespace App\Providers;

use App\Models\BusinessSetting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;

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
        $this->configureDefaults();

        View::composer('components.app-mark', function (ViewInstance $view): void {
            $logoPath = Schema::hasTable('business_settings')
                ? BusinessSetting::query()->value('logo_path')
                : null;

            $hasLogo = $logoPath && Storage::disk('public')->exists($logoPath);

            $view->with('businessLogoUrl', $hasLogo ? route('branding.logo') : null);
        });

        View::composer('partials.head', function (ViewInstance $view): void {
            $businessName = Schema::hasTable('business_settings')
                ? BusinessSetting::query()->value('business_name')
                : null;

            $view->with('businessName', $businessName);
        });
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
