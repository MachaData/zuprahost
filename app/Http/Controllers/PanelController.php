<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(Request $request): View
    {
        $client = $request->user()?->client;

        abort_unless($client, 403, 'Tu usuario no tiene un perfil de cliente.');

        $activeServices = $client->services()->where('status', 'activo')->count();

        $nextService = $client->services()
            ->whereIn('status', ['activo', 'pendiente'])
            ->whereNotNull('ends_at')
            ->orderBy('ends_at')
            ->first();

        $pendingInvoices = $client->invoices()
            ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
            ->get();

        $openTickets = $client->tickets()
            ->whereIn('status', ['abierto', 'en_proceso', 'esperando_cliente'])
            ->count();

        $services = $client->services()
            ->with('domain')
            ->orderByRaw("CASE status WHEN 'activo' THEN 0 WHEN 'pendiente' THEN 1 WHEN 'suspendido' THEN 2 WHEN 'vencido' THEN 3 ELSE 4 END")
            ->orderBy('ends_at')
            ->limit(4)
            ->get();

        $expiringDomains = $client->domains()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->addDays(30))
            ->count();

        return view('panel.dashboard', [
            'client' => $client,
            'activeServices' => $activeServices,
            'nextService' => $nextService,
            'pendingInvoices' => $pendingInvoices,
            'pendingInvoicesTotal' => (float) $pendingInvoices->sum('total'),
            'openTickets' => $openTickets,
            'services' => $services,
            'expiringDomains' => $expiringDomains,
        ]);
    }
}
