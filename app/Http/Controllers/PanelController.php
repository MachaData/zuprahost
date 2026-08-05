<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PanelController extends Controller
{
    /**
     * Ventana del centro de renovaciones: lo que vence dentro de 60 días,
     * más lo ya vencido, que también aparece.
     */
    protected const RENEWAL_HORIZON_DAYS = 60;

    public function index(Request $request): View
    {
        $client = $this->client($request);

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
            ->orderByRaw($this->serviceStatusOrder())
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

    public function services(Request $request): View
    {
        $client = $this->client($request);

        return view('panel.services', [
            'client' => $client,
            'services' => $client->services()
                ->with('domain')
                ->orderByRaw($this->serviceStatusOrder())
                ->orderBy('ends_at')
                ->paginate(15),
            'activeCount' => $client->services()->where('status', 'activo')->count(),
        ]);
    }

    public function domains(Request $request): View
    {
        $client = $this->client($request);

        return view('panel.domains', [
            'client' => $client,
            'domains' => $client->domains()
                ->orderByRaw('expires_at is null, expires_at asc')
                ->paginate(15),
            'expiringCount' => $client->domains()
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', now()->addDays(30))
                ->count(),
            // La preferencia de vista viaja en la URL: se puede compartir el
            // enlace y no hace falta JavaScript ni sesión.
            'view' => $request->query('vista') === 'lista' ? 'lista' : 'tarjetas',
        ]);
    }

    /**
     * Centro de renovaciones: todo lo que vence pronto —servicios, dominios y
     * licencias— en una sola lista ordenada por urgencia.
     */
    public function renewals(Request $request): View
    {
        $client = $this->client($request);
        $horizon = now()->addDays(self::RENEWAL_HORIZON_DAYS);

        $items = collect()
            ->concat($client->services()
                ->whereIn('status', ['activo', 'pendiente', 'vencido'])
                ->whereNotNull('ends_at')->whereDate('ends_at', '<=', $horizon)
                ->with('domain')->get()
                ->map(fn ($service) => [
                    'type' => 'Servicio',
                    'name' => $service->name,
                    'detail' => $service->domain?->name,
                    'expires_at' => $service->ends_at,
                    'price' => (float) $service->price,
                    'auto_renew' => (bool) $service->auto_renew,
                    'status' => $service->status,
                    'url' => route('panel.service', $service),
                ]))
            // Los dominios que el cliente renueva por su cuenta no entran:
            // nosotros no se los vamos a cobrar.
            ->concat($client->domains()
                ->where('renewal_managed', true)
                ->whereNotNull('expires_at')->whereDate('expires_at', '<=', $horizon)
                ->get()
                ->map(fn ($domain) => [
                    'type' => 'Dominio',
                    'name' => $domain->name,
                    'detail' => $domain->provider,
                    'expires_at' => $domain->expires_at,
                    'price' => (float) $domain->renewal_price,
                    'auto_renew' => (bool) $domain->auto_renew,
                    'status' => $domain->status,
                    'url' => url('/panel/dominios'),
                ]))
            ->concat($client->licenses()
                ->whereNotNull('expires_at')->whereDate('expires_at', '<=', $horizon)
                ->with('product')->get()
                ->map(fn ($license) => [
                    'type' => 'Licencia',
                    'name' => $license->product?->name ?? 'Licencia',
                    'detail' => $license->license_key,
                    'expires_at' => $license->expires_at,
                    'price' => (float) ($license->product?->price ?? 0),
                    'auto_renew' => false,
                    'status' => $license->status,
                    'url' => url('/panel/licencias'),
                ]))
            ->sortBy('expires_at')
            ->values();

        return view('panel.renewals', [
            'client' => $client,
            'items' => $items,
            'horizonDays' => self::RENEWAL_HORIZON_DAYS,
            'expiredCount' => $items->filter(fn ($i) => $i['expires_at']?->isPast())->count(),
            'total' => (float) $items->sum('price'),
            'pendingBalance' => $client->outstandingBalance(),
        ]);
    }

    public function payments(Request $request): View
    {
        $client = $this->client($request);

        // Dos listas con propósitos distintos: una para pagar, otra para
        // buscar un comprobante de algo ya cerrado.
        $tab = $request->query('estado') === 'pagadas' ? 'pagadas' : 'por_pagar';

        $invoices = $client->invoices()
            ->when($tab === 'pagadas', fn ($q) => $q->settled(), fn ($q) => $q->outstanding())
            ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', 'aprobado')], 'amount')
            ->latest('issued_at')
            ->latest('id')
            ->paginate(15)
            ->appends(['estado' => $tab]);

        // Saldo pendiente real: lo facturado menos lo ya validado.
        $pending = $client->invoices()
            ->whereIn('status', ['pendiente', 'parcial', 'vencido'])
            ->withSum(['payments as paid_sum' => fn ($q) => $q->where('status', 'aprobado')], 'amount')
            ->get();

        return view('panel.payments', [
            'client' => $client,
            'invoices' => $invoices,
            'tab' => $tab,
            'pendingCount' => $pending->count(),
            'settledCount' => $client->invoices()->settled()->count(),
            'pendingBalance' => (float) $pending->sum(
                fn ($invoice) => max(0, (float) $invoice->total - (float) $invoice->paid_sum),
            ),
            'awaitingReview' => $client->payments()->where('status', 'pendiente')->count(),
        ]);
    }

    /**
     * Recibo de pago en PDF. Es un documento interno: deja constancia de lo
     * cobrado, no reemplaza al comprobante electrónico de SUNAT.
     */
    public function receipt(Request $request, int $invoice): SymfonyResponse
    {
        $client = $this->client($request);

        $invoice = $client->invoices()
            ->with(['items', 'payments' => fn ($q) => $q->where('status', 'aprobado')->oldest('paid_at')])
            ->findOrFail($invoice);

        abort_unless($invoice->amountPaid() > 0, 404);

        $pdf = Pdf::loadView('pdf.receipt', [
            'invoice' => $invoice,
            'client' => $client,
            'paid' => $invoice->amountPaid(),
            'balance' => $invoice->balance(),
        ])->setPaper('a4');

        return $pdf->download("recibo-{$invoice->code}.pdf");
    }

    /**
     * Comprobante electrónico emitido ante SUNAT. Si APISPERU devolvió el PDF
     * oficial se entrega ese; si no, se arma uno con los mismos datos, marcado
     * con su serie y número.
     */
    public function invoiceDocument(Request $request, int $invoice): SymfonyResponse
    {
        $client = $this->client($request);

        $invoice = $client->invoices()
            ->with(['items', 'payments' => fn ($q) => $q->where('status', 'aprobado')->oldest('paid_at')])
            ->findOrFail($invoice);

        // Si el administrador subió el comprobante, ese manda: es el documento
        // real. Después el que devuelva SUNAT. Y si no hay ninguno, se arma.
        foreach ([$invoice->attachment_path, $invoice->sunat_pdf_path] as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                return response()->download(
                    Storage::disk('public')->path($path),
                    $invoice->attachment_name
                        ? Str::slug($invoice->attachment_name).'.'.pathinfo($path, PATHINFO_EXTENSION)
                        : "{$invoice->documentLabel()}-{$invoice->code}.".pathinfo($path, PATHINFO_EXTENSION),
                );
            }
        }

        abort_unless($invoice->isElectronicallyIssued(), 404);

        return Pdf::loadView('pdf.receipt', [
            'invoice' => $invoice,
            'client' => $client,
            'paid' => $invoice->amountPaid(),
            'balance' => $invoice->balance(),
            'asInvoice' => true,
        ])->setPaper('a4')->download("{$invoice->documentLabel()}-{$invoice->code}.pdf");
    }

    /**
     * Solicitud de comprobante electrónico. Solo se puede pedir una vez y
     * solo si el administrador lo habilitó en el servicio facturado.
     */
    public function requestInvoice(Request $request, int $invoice): RedirectResponse
    {
        $client = $this->client($request);
        $invoice = $client->invoices()->findOrFail($invoice);

        abort_unless($invoice->canRequestElectronicInvoice(), 403);

        $data = $request->validate([
            'billing_document_type' => ['required', 'in:ruc,dni,ce'],
            'billing_document_number' => [
                'required', 'string',
                // El RUC peruano son 11 dígitos; el DNI, 8.
                Rule::when($request->input('billing_document_type') === 'ruc', ['digits:11']),
                Rule::when($request->input('billing_document_type') === 'dni', ['digits:8']),
                Rule::when($request->input('billing_document_type') === 'ce', ['max:20']),
            ],
            'billing_name' => ['required', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_email' => ['nullable', 'email', 'max:255'],
        ], [
            'billing_document_number.digits' => 'El :attribute debe tener :digits dígitos.',
        ], [
            'billing_document_type' => 'tipo de documento',
            'billing_document_number' => 'número de documento',
            'billing_name' => 'nombre o razón social',
        ]);

        $invoice->requestElectronicInvoice($data);

        return redirect()
            ->route('panel.payment', $invoice)
            ->with('status', 'Solicitud enviada. Emitiremos tu comprobante y lo verás aquí para descargar.');
    }

    /**
     * Detalle de una factura: ítems, totales y comprobantes aplicados.
     */
    public function payment(Request $request, int $invoice): View
    {
        $client = $this->client($request);

        $invoice = $client->invoices()
            ->with(['items', 'payments' => fn ($q) => $q->latest('paid_at')->latest('id')])
            ->findOrFail($invoice);

        $paid = $invoice->amountPaid();

        return view('panel.payment', [
            'client' => $client,
            'invoice' => $invoice,
            'paid' => $paid,
            'balance' => max(0, (float) $invoice->total - $paid),
            'canRequestInvoice' => $invoice->canRequestElectronicInvoice(),
            // Solo hace falta saber cómo pagar si queda algo por pagar.
            'methods' => $invoice->balance() > 0
                ? $client->availablePaymentMethods()
                : collect(),
            'paymentLinks' => $invoice->balance() > 0
                ? $invoice->paymentLinks()
                : collect(),
        ]);
    }

    /**
     * Detalle de un servicio: estado, fechas y accesos de alojamiento.
     */
    public function service(Request $request, int $service): View
    {
        $client = $this->client($request);

        $service = $client->services()
            ->with(['domain', 'product', 'hosting', 'visibleAccesses', 'renewals' => fn ($q) => $q->latest('id')->limit(5)])
            ->findOrFail($service);

        return view('panel.service', [
            'client' => $client,
            'service' => $service,
            'hosting' => $service->hosting,
            'accesses' => $service->visibleAccesses,
            'planFeatures' => $service->product?->planFeatures() ?? [],
        ]);
    }

    public function licenses(Request $request): View
    {
        $client = $this->client($request);

        return view('panel.licenses', [
            'client' => $client,
            'licenses' => $client->licenses()
                ->with('product')
                ->orderByRaw('expires_at is null, expires_at asc')
                ->paginate(15),
        ]);
    }

    /**
     * Cliente autenticado. Sin perfil de cliente no hay portal que mostrar.
     */
    protected function client(Request $request): Client
    {
        $client = $request->user()?->client;

        abort_unless($client, 403, 'Tu usuario no tiene un perfil de cliente.');

        return $client;
    }

    /**
     * Ordena los servicios por relevancia para el cliente: primero lo activo,
     * al final lo cancelado.
     */
    protected function serviceStatusOrder(): string
    {
        return "CASE status WHEN 'activo' THEN 0 WHEN 'pendiente' THEN 1 WHEN 'suspendido' THEN 2 WHEN 'vencido' THEN 3 ELSE 4 END";
    }
}
