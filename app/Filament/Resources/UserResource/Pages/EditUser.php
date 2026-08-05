<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * Los roles se guardan como relación, después del formulario, así que la
     * comprobación va aquí: quien se quita a sí mismo el rol de Administrador
     * pierde el panel en la siguiente petición y ya no puede devolvérselo.
     */
    protected function afterSave(): void
    {
        $user = $this->getRecord();

        if (! $user instanceof User || ! $user->is(auth()->user())) {
            return;
        }

        $user->refresh();

        if (! $user->hasRole('Administrador')) {
            $user->assignRole('Administrador');

            Notification::make()
                ->title('Se conservó tu rol de Administrador')
                ->body('Quitártelo a ti mismo te habría dejado fuera del panel. Pídele a otro administrador que lo haga.')
                ->warning()
                ->send();
        }
    }
}
