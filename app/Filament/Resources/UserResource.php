<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\AuthorizesWithPermissions;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    // El alias deja llegar al permiso de Spatie después de las comprobaciones
    // propias; `parent::` saltaría el trait y devolvería siempre true.
    use AuthorizesWithPermissions {
        canDelete as protected canDeleteByPermission;
    }

    public static function permissionName(): string
    {
        return 'user';
    }

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios y roles';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Administrador') ?? false;
    }

    /**
     * Borrarse a uno mismo o borrar al último administrador deja el panel sin
     * dueño; se arregla solo por consola, así que se impide desde aquí.
     */
    public static function canDelete(Model $record): bool
    {
        if ($record->is(auth()->user()) || $record->isLastAdministrator()) {
            return false;
        }

        return static::canDeleteByPermission($record);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nombre')->required(),
                Forms\Components\TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('password')->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->autocomplete('new-password')
                    ->helperText('Mínimo 12 caracteres, con mayúsculas, minúsculas, números y símbolos. En blanco = sin cambios.')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create'),

                Forms\Components\Select::make('roles')->label('Roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->required()
                    // Quitarse a uno mismo el rol de Administrador es la forma
                    // más silenciosa de perder el acceso al panel.
                    ->disableOptionWhen(fn (string $value, ?Model $record): bool => $value === 'Administrador'
                        && $record?->is(auth()->user())
                        && $record->hasRole('Administrador'))
                    ->helperText(fn (?Model $record): ?string => $record?->is(auth()->user())
                        ? 'No puedes quitarte tu propio rol de Administrador.'
                        : null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Correo')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Roles')->badge(),

                // Un acceso inesperado o una cuenta que nunca entró son las dos
                // señales que se miran cuando algo huele mal.
                Tables\Columns\TextColumn::make('last_login_at')->label('Último acceso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->description(fn (User $record): ?string => $record->last_login_ip)
                    ->sortable(),

                Tables\Columns\TextColumn::make('password_changed_at')->label('Contraseña cambiada')
                    ->date('d/m/Y')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')->label('Registro')->date()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')->label('Rol')
                    ->relationship('roles', 'name')->multiple()->preload(),
                Tables\Filters\Filter::make('never_logged_in')->label('Nunca ha entrado')
                    ->query(fn ($query) => $query->whereNull('last_login_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Cerrar la sesión de alguien: si se sospecha de una cuenta,
                // cambiar la contraseña no basta mientras su sesión siga viva.
                Tables\Actions\Action::make('revokeSessions')
                    ->label('Cerrar sus sesiones')
                    ->icon('heroicon-o-power')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Se invalidan todas las sesiones abiertas de esta cuenta. Tendrá que volver a iniciar sesión.')
                    ->action(function (User $record): void {
                        // Cambiar el remember_token invalida las cookies de
                        // "recordarme"; AuthenticateSession compara el hash de
                        // la contraseña, que no se toca aquí.
                        $record->forceFill(['remember_token' => \Illuminate\Support\Str::random(60)])->saveQuietly();

                        \Illuminate\Support\Facades\DB::table('sessions')
                            ->where('user_id', $record->getKey())
                            ->delete();

                        Notification::make()
                            ->title('Sesiones cerradas para '.$record->email)
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => config('session.driver') === 'database'),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
