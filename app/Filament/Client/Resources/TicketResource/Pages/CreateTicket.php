<?php

namespace App\Filament\Client\Resources\TicketResource\Pages;

use App\Filament\Client\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['client_id'] = auth()->user()->client?->id;
        $data['user_id'] = auth()->id();
        $data['code'] = Ticket::generateCode();
        $data['status'] = 'abierto';

        return $data;
    }

    protected function afterCreate(): void
    {
        $message = $this->data['initial_message'] ?? null;

        if ($message) {
            $this->record->replies()->create([
                'user_id' => auth()->id(),
                'message' => $message,
                'is_staff' => false,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
