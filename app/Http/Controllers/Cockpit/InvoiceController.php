<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\Invoice\GetInvoiceRequest;
use App\Http\Requests\Cockpit\Invoice\RejectInvoiceRequest;
use App\Models\Invoice;
use App\Services\Cockpit\Invoice\ApproveInvoiceValidationService;
use App\Services\Cockpit\Invoice\RejectInvoiceValidationService;
use App\Services\Cockpit\SubscriptionInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function __construct(
        protected SubscriptionInvoiceService $invoiceService
    ) {}

    /**
     * Display paginated list of subscription invoices with filters and KPI metrics.
     */
    public function index(GetInvoiceRequest $request): Response
    {
        $filters = [
            'search' => (string) $request->input('search', ''),
            'status' => (string) $request->input('status', ''),
            'sort' => (string) $request->input('sort', 'created_at'),
            'direction' => (string) $request->input('direction', 'desc'),
            'open_invoice' => (string) $request->input('open_invoice', ''),
        ];

        $perPage = (int) $request->input('perpage', 20);
        $invoices = $this->invoiceService->getPaginatedInvoices($filters, $perPage);
        $metrics = $this->invoiceService->getMetrics();

        return Inertia::render('Cockpit/Invoice/Index', [
            'invoices' => $invoices,
            'metrics' => $metrics,
            'filters' => $filters,
        ]);
    }

    /**
     * Get detailed subscription invoice information on-demand.
     */
    public function show(Invoice $invoice): JsonResponse
    {
        $detail = $this->invoiceService->getInvoiceDetail($invoice);

        return response()->json($detail);
    }

    /**
     * Approve manual payment validation for the given invoice.
     */
    public function approve(Invoice $invoice, ApproveInvoiceValidationService $service): RedirectResponse
    {
        $service->execute($invoice);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Pembayaran invoice berhasil disetujui dan diverifikasi.'
        );
    }

    /**
     * Reject manual payment validation with a specific reason.
     */
    public function reject(
        Invoice $invoice,
        RejectInvoiceRequest $request,
        RejectInvoiceValidationService $service
    ): RedirectResponse {
        $service->execute($invoice, $request->validated('reason'));

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Pembayaran invoice berhasil ditolak dan merchant telah dinotifikasi.'
        );
    }
}
