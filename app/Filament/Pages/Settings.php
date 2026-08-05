<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Branding;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Configuración';

    protected static ?string $title = 'Configuración';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    /**
     * Clave => grupo con el que se guarda en la tabla `settings`. El grupo
     * sirve para saber de dónde salió cada valor cuando se revisa la tabla.
     *
     * @var array<string, string>
     */
    protected array $keys = [
        'brand_name' => 'marca',
        'brand_logo' => 'marca',
        'brand_favicon' => 'marca',
        'brand_color' => 'marca',
        'apisperu_enabled' => 'apisperu',
        'apisperu_token' => 'apisperu',
        'apisperu_base_url' => 'apisperu',
        'company_ruc' => 'empresa',
        'company_name' => 'empresa',
        'company_address' => 'empresa',
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Administrador') ?? false;
    }

    public function mount(): void
    {
        $data = [];
        foreach (array_keys($this->keys) as $key) {
            $data[$key] = Setting::get($key);
        }

        $data['apisperu_enabled'] = (bool) ($data['apisperu_enabled'] ?? false);
        $data['apisperu_base_url'] ??= config('apisperu.base_url');
        $data['brand_name'] = $data['brand_name'] ?: Branding::DEFAULT_NAME;
        $data['brand_color'] = $data['brand_color'] ?: Branding::DEFAULT_COLOR;

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Marca')
                    ->description('El logo y el color se aplican al panel de administración, al portal del cliente y a los comprobantes en PDF.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('brand_name')
                            ->label('Nombre comercial')
                            ->maxLength(60)
                            ->required(),

                        ColorPicker::make('brand_color')
                            ->label('Color principal')
                            ->hex()
                            ->helperText('Se usa en botones y enlaces del portal.'),

                        FileUpload::make('brand_logo')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->imageEditor()
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'])
                            ->helperText('PNG o SVG con fondo transparente. Alto recomendado: 80 px. Máximo 1 MB.'),

                        FileUpload::make('brand_favicon')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('branding')
                            ->visibility('public')
                            ->maxSize(256)
                            ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/svg+xml'])
                            ->helperText('Cuadrado, 64×64 px o más. El icono de la pestaña del navegador.'),
                    ]),

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
                        TextInput::make('apisperu_token')->label('Token JWT')->password()->revealable()->columnSpanFull(),
                        TextInput::make('apisperu_base_url')->label('URL base de la API')->url(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        // getState() valida y, de paso, mueve los archivos subidos del
        // directorio temporal al disco público. Las rutas definitivas quedan
        // en $this->data, no en el valor que devuelve.
        $this->form->getState();

        foreach ($this->keys as $key => $group) {
            Setting::set($key, $this->normalize($this->data[$key] ?? null), $group);
        }

        Notification::make()->title('Configuración guardada')->success()->send();
    }

    /**
     * FileUpload guarda su estado como [uuid => ruta]; el resto de campos son
     * escalares. Todo acaba en la tabla como texto.
     */
    protected function normalize(mixed $value): string
    {
        if (is_array($value)) {
            $value = Arr::first($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) ($value ?? '');
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
