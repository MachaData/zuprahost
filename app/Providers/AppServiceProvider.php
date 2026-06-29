<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
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
        // Crédito del desarrollador en el pie de ambos paneles Filament.
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn (): string => Blade::render(
                '<p class="px-4 py-3 text-center text-xs text-gray-400">Desarrollado por <span class="font-medium text-gray-500">MachaData</span></p>'
            ),
        );
    }
}
