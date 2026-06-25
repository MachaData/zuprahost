<?php

namespace App\Filament\Client\Pages;

use App\Models\Client;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class BillingProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Facturación';

    protected static ?string $title = 'Datos de facturación';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.client.pages.billing-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $client = auth()->user()->client;

        $this->form->fill([
            'wants_invoice' => (bool) $client?->wants_invoice,
            'document_type' => $client?->document_type ?? 'dni',
            'document_number' => $client?->document_number,
            'name' => $client?->name,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('¿Deseas comprobante electrónico?')
                    ->description('Activa esta opción si necesitas factura o boleta electrónica por tus pagos.')
                    ->schema([
                        Toggle::make('wants_invoice')
                            ->label('Sí, deseo facturación electrónica')
                            ->live(),
                    ]),
                Section::make('Datos para el comprobante')
                    ->description('Necesarios para emitir tu factura o boleta ante SUNAT.')
                    ->columns(2)
                    ->visible(fn (Get $get) => (bool) $get('wants_invoice'))
                    ->schema([
                        Select::make('document_type')
                            ->label('Tipo de documento')
                            ->options([
                                'ruc' => 'RUC (factura)',
                                'dni' => 'DNI (boleta)',
                                'ce' => 'Carné de extranjería (boleta)',
                            ])
                            ->default('dni')
                            ->live()
                            ->required(),
                        TextInput::make('document_number')
                            ->label('Número de documento')
                            ->required(),
                        TextInput::make('name')
                            ->label(fn (Get $get) => $get('document_type') === 'ruc' ? 'Razón social' : 'Nombre completo')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        /** @var Client $client */
        $client = auth()->user()->client;

        if (! $client) {
            Notification::make()->title('No se encontró tu perfil de cliente.')->danger()->send();

            return;
        }

        $state = $this->form->getState();

        $client->fill([
            'wants_invoice' => $state['wants_invoice'] ?? false,
        ]);

        if ($state['wants_invoice'] ?? false) {
            $client->fill([
                'document_type' => $state['document_type'],
                'document_number' => $state['document_number'],
                'name' => $state['name'],
            ]);
        }

        $client->save();

        Notification::make()->title('Preferencias de facturación guardadas')->success()->send();
    }
}
