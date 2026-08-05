<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Validation\Rules\Password;

/**
 * Perfil propio de la cuenta, para los dos paneles.
 *
 * Filament trae esta página, pero permite cambiar la contraseña sin conocer
 * la actual: quien encuentre una sesión abierta se queda con la cuenta. Aquí
 * se exige la contraseña vigente y se aplica la política de la aplicación.
 */
class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Mi cuenta';

    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),

            TextInput::make('current_password')
                ->label('Contraseña actual')
                ->password()
                ->revealable()
                ->currentPassword()
                ->autocomplete('current-password')
                ->helperText('Obligatoria solo si vas a cambiar la contraseña.')
                ->required(fn (Get $get): bool => filled($get('password')))
                ->dehydrated(false),

            $this->getPasswordFormComponent()
                ->label('Nueva contraseña')
                ->rule(Password::defaults())
                ->helperText('Déjala en blanco para conservar la actual.'),

            $this->getPasswordConfirmationFormComponent()
                ->label('Repite la nueva contraseña'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['password'] ?? null)) {
            $data['password_changed_at'] = now();
        }

        return $data;
    }
}
