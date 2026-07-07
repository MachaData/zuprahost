<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * El panel del cliente usa el dashboard a medida en /panel.
 * Esta página solo redirige al entrar (por ejemplo, tras iniciar sesión).
 */
class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        redirect('/panel');
    }
}
