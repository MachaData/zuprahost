<?php

namespace App\Providers;

use App\Listeners\RecordAuthenticationActivity;
use App\Models\Payment;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Observers\UserObserver;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Payment::observe(PaymentObserver::class);
        User::observe(UserObserver::class);

        $this->configureSecurity();

        Event::listen(Login::class, [RecordAuthenticationActivity::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordAuthenticationActivity::class, 'handleFailed']);

        // Crédito del desarrollador en el pie de ambos paneles Filament.
        FilamentView::registerRenderHook(
            PanelsRenderHook::FOOTER,
            fn (): string => Blade::render(
                '<p class="px-4 py-3 text-center text-xs text-gray-400">Desarrollado por <span class="font-medium text-gray-500">MachaData</span></p>'
            ),
        );
    }

    protected function configureSecurity(): void
    {
        // Política única de contraseñas: la comparten el alta de usuarios, el
        // perfil propio y el comando de consola, así que no queda una puerta
        // más débil que las otras.
        Password::defaults(function (): Password {
            $rule = Password::min(12)->letters()->mixedCase()->numbers()->symbols();

            // La comprobación contra filtraciones conocidas consulta un
            // servicio externo; en pruebas y desarrollo sobra y ralentiza.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // Railway termina el TLS en su proxy y habla HTTP con el contenedor.
        // Sin esto, los enlaces salen con http:// dentro de una página https://
        // y el navegador bloquea el envío de formularios.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
