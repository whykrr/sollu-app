<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Jobs\Transaction\ExportTransactionJob;
use App\Models\Sales\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('transaction.view');

        $filters = $request->only(['search', 'channel', 'status', 'payment_status', 'start_date', 'end_date', 'sort', 'direction']);

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

        // Wait, is business_id a thing on User? It's typically a trait. We will see in the Job.
        $transactions = Transaction::with(['customer', 'outlet', 'shift.user'])
            ->filters($filters)
            ->orderBy($sortField, $sortDirection)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Transaction/Sales/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
        ]);
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('transaction.view');

        $transaction->load([
            'customer',
            'outlet',
            'shift.user',
            'items.modifiers',
            'payments.paymentMethod',
        ]);

        return Inertia::render('Transaction/Sales/Show', [
            'transaction' => $transaction,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('transaction.view');

        ExportTransactionJob::dispatch(
            Auth::user(),
            Auth::user()->business_id ?? null,
            $request->all()
        );

        return redirect()->back()->with('success', 'Ekspor CSV sedang diproses di latar belakang.');
    }
}
