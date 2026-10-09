<?php

namespace App\Providers;

use App\Http\Controllers\GifUploadController;
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
        // Logo global (login, barra superior y favicon). Si no hay o falla,
        // queda null y se usan los valores por defecto.
        $logoUrl = null;
        try {
            if (Schema::hasTable('app_settings') && ($path = AppSetting::logoPath())) {
                $logoUrl = GifUploadController::publicUrl($path);
            }
        } catch (\Throwable) {
            $logoUrl = null;
        }
        View::share('logoUrl', $logoUrl);
    }
}
