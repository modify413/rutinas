<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
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
        // Logo global (login, barra superior y favicon). Se resuelve en cada
        // render para reflejar cambios sin reiniciar; si no hay o falla,
        // queda null y se usan los valores por defecto.
        View::composer('*', function ($view) {
            $logoUrl = null;
            try {
                if (Schema::hasTable('app_settings') && AppSetting::logoPath()) {
                    // URL estable: no caduca y no interfiere con el login
                    $logoUrl = url('/logo');
                }
            } catch (\Throwable) {
                $logoUrl = null;
            }
            $view->with('logoUrl', $logoUrl);
        });
    }
}
