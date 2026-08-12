<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Sales\Transaction;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function index(Request $request)
    {
        $this->authorize('transaction.view');

        $invoices = Transaction::where('channel', 'invoice')
            ->with(['customer', 'outlet'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Transaction/Invoice/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['search']),
        ]);
    }

    public function create()
    {
        $this->authorize('transaction.create');

        $customers = \App\Models\Master\Customer::currentBusiness()
            ->select('id', 'name', 'email', 'phone')
            ->orderBy('name')
            ->get();

        $outlets = \App\Models\Outlet::currentBusiness()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Transaction/Invoice/Create', [
            'customers' => $customers,
            'outlets'   => $outlets,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('transaction.create');

        $validated = $request->validate([
            'outlet_id'               => 'required|uuid',
            'customer_id'             => 'required|uuid',
            'due_date'                => 'required|date',
            'items'                   => 'required|array|min:1',
            'items.*.product_id'      => 'required|uuid',
            'items.*.product_name'    => 'required|string',
            'items.*.price'           => 'required|numeric|min:0',
            'items.*.qty'             => 'required|numeric|min:0.01',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.subtotal'        => 'required|numeric|min:0',
            'subtotal'                => 'required|numeric|min:0',
            'tax_amount'              => 'nullable|numeric|min:0',
            'discount_amount'         => 'nullable|numeric|min:0',
            'service_charge_amount'   => 'nullable|numeric|min:0',
            'total'                   => 'required|numeric|min:0',
        ]);

        $this->transactionService->createB2bInvoice($validated, auth()->user());

        return redirect()->route('transactions.sales.index')->with('success', 'Faktur berhasil dibuat.');
    }

    public function show(Transaction $invoice)
    {
        $this->authorize('transaction.view');
        $invoice->load(['items.modifiers', 'customer', 'outlet', 'payments.paymentMethod']);

        return Inertia::render('Transaction/Invoice/Show', [
            'invoice' => $invoice,
        ]);
    }
}
