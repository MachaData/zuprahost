<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    protected array $keys = [
        'apisperu_enabled', 'apisperu_token', 'apisperu_base_url',
        'company_ruc', 'company_name', 'company_address',
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Administrador') ?? false;
    }

    public function mount(): void
    {
        $data = [];
        foreach ($this->keys as $key) {
            $data[$key] = Setting::get($key);
        }
        $data['apisperu_enabled'] = (bool) ($data['apisperu_enabled'] ?? false);
        $data['apisperu_base_url'] ??= config('apisperu.base_url');

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Empresa emisora')
                    ->description('Datos usados en los comprobantes electrónicos.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('company_ruc')->label('RUC de la empresa'),
                        TextInput::make('company_name')->label('Razón social'),
                        TextInput::make('company_address')->label('Dirección fiscal')->columnSpanFull(),
                    ]),
                Section::make('Facturación electrónica (APISPERU)')
                    ->description('Configura la integración con APISPERU para emitir comprobantes ante SUNAT.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('apisperu_enabled')->label('Activar envío real a SUNAT')
                            ->helperText('Si está desactivado, los comprobantes se simulan sin enviarse.')
                            ->columnSpanFull(),
                        TextInput::make('apisperu_token')->label('Token JWT')->password()->columnSpanFull(),
                        TextInput::make('apisperu_base_url')->label('URL base de la API')->url(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : (string) ($value ?? ''), 'apisperu');
        }

        Notification::make()->title('Configuración guardada')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Guardar')
                ->submit('save'),
        ];
    }
}
