<?php
namespace App\Providers;
use App\View\Composers\AuthUserComposer;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        // Railway (dan platform serupa) menangani HTTPS di proxy terluar,
        // lalu meneruskan request ke Laravel sebagai HTTP biasa. Tanpa ini,
        // Laravel generate semua URL asset/link pakai http:// meski
        // situsnya sebenarnya diakses lewat https:// — menyebabkan browser
        // memblokir CSS/JS sebagai "mixed content".
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('partials.dashboard.topbar', AuthUserComposer::class);
    }
}
