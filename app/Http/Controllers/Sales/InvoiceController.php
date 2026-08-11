<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\Transaction;
use App\Services\Sales\TransactionService;
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

        return Inertia::render('Sales/Invoice/Index', [
            'invoices' => $invoices,
            'filters'  => $request->only(['search'])
        ]);
    }

    public function create()
    {
        $this->authorize('transaction.create');
        return Inertia::render('Sales/Invoice/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('transaction.create');
        
        // $transaction = $this->transactionService->createB2bInvoice($request->all(), auth()->user());
        
        return redirect()->route('sales.invoices.index')->with('success', 'Faktur berhasil dibuat.');
    }

    public function show(Transaction $invoice)
    {
        $this->authorize('transaction.view');
        $invoice->load(['items.modifiers', 'customer', 'outlet', 'payments.paymentMethod']);

        return Inertia::render('Sales/Invoice/Show', [
            'invoice' => $invoice
        ]);
    }
}
