<?php

namespace App\Http\Controllers\Cockpit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\Invoice\RejectInvoiceRequest;
use App\Models\Invoice;
use App\Services\Cockpit\Invoice\ApproveInvoiceValidationService;
use App\Services\Cockpit\Invoice\RejectInvoiceValidationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['business', 'payments', 'paymentManualValidation', 'items']);

        if ($request->filled('open_invoice')) {
            $query->where('invoice_number', $request->open_invoice);
        } elseif ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('business', function ($b) use ($search) {
                        $b->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($invoice) {
                // Map outlet name if present in items metadata
                $outletNames = $invoice->items->map(function ($item) {
                    return $item->metadata['outlet_name'] ?? null;
                })->filter()->unique()->implode(', ');

                if (empty($outletNames)) {
                    $activeOutlets = $invoice->items->map(function ($item) {
                        return $item->metadata['active_outlets'] ?? null;
                    })->filter()->first();
                    $outletNames = $activeOutlets ? $activeOutlets.' Outlets' : '-';
                }

                $status = $invoice->status;
                if ($invoice->paymentManualValidation) {
                    if ($invoice->paymentManualValidation->validation_status === 'pending') {
                        $status = 'pending review';
                    } elseif ($invoice->paymentManualValidation->validation_status === 'rejected') {
                        $status = 'rejected';
                    }
                }

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'date' => $invoice->created_at->format('d M Y H:i'),
                    'merchant' => $invoice->business->name ?? '-',
                    'outlet_name' => $outletNames,
                    'amount' => 'Rp '.number_format($invoice->total_amount, 0, ',', '.'),
                    'status' => $status,
                    'raw_status' => $invoice->status,
                    'items' => $invoice->items,
                    'proof_url' => $invoice->paymentManualValidation?->payment_proof_full_url,
                ];
            });

        return Inertia::render('Cockpit/Invoice/Index', [
            'invoices' => $invoices,
            'filters' => [
                'search' => $request->search,
                'open_invoice' => $request->open_invoice,
            ],
        ]);
    }

    public function approve(Invoice $invoice, ApproveInvoiceValidationService $service)
    {
        $service->execute($invoice);

        return back()->with('success', 'Invoice payment approved successfully.');
    }

    public function reject(Invoice $invoice, RejectInvoiceRequest $request, RejectInvoiceValidationService $service)
    {
        $service->execute($invoice, $request->validated('reason'));

        return back()->with('success', 'Invoice payment rejected and merchant notified.');
    }
}
