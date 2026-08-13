<?php

namespace App\Http\Controllers\Transaction;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\Sales\RecordPaymentTransactionRequest;
use App\Http\Requests\Transaction\Sales\StoreSalesTransactionRequest;
use App\Http\Resources\Transaction\TransactionResource;
use App\Jobs\Transaction\ExportTransactionJob;
use App\Models\Sales\Transaction;
use App\Services\Transaction\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('transaction.view');

        $filters = $request->only(['search', 'channel', 'status', 'payment_status', 'start_date', 'end_date', 'sort', 'direction']);

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDirection = $filters['direction'] ?? 'desc';

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
            'invoice',
            'customer',
            'outlet',
            'shift.user',
            'items.modifiers',
            'payments.paymentMethod',
        ]);

        return new TransactionResource($transaction);
    }

    public function store(StoreSalesTransactionRequest $request, TransactionService $service)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            // Simulate creation logic. Since we don't know the exact internals of TransactionService yet,
            // we will pass the data array to it.
            $transaction = $service->createTransaction($validated, Auth::user());

            if ($validated['action'] === 'issue') {
                $this->authorize('transaction.issue_invoice');
                $service->issueInvoice($transaction, Auth::user());
            }

            DB::commit();

            return redirect()->route('transactions.sales.index')
                ->with(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function issue(Transaction $transaction, TransactionService $service)
    {
        $this->authorize('transaction.issue_invoice');

        try {
            DB::beginTransaction();
            $service->issueInvoice($transaction, Auth::user());
            DB::commit();

            return redirect()->back()->with(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function recordPayment(RecordPaymentTransactionRequest $request, Transaction $transaction, TransactionService $service)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();
            $service->recordPayment($transaction, $validated, Auth::user());
            DB::commit();

            return redirect()->back()->with(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function cancel(Transaction $transaction, TransactionService $service)
    {
        $this->authorize('transaction.cancel');

        try {
            DB::beginTransaction();
            $service->cancelTransaction($transaction, Auth::user());
            DB::commit();

            return redirect()->back()->with(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function void(Transaction $transaction, TransactionService $service)
    {
        $this->authorize('transaction.void');

        try {
            DB::beginTransaction();
            $service->voidTransaction($transaction, Auth::user());
            DB::commit();

            return redirect()->back()->with(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function pdf(Transaction $transaction)
    {
        $this->authorize('transaction.view');

        $transaction->load([
            'customer',
            'outlet',
            'items',
            'payments.paymentMethod',
        ]);

        $pdf = Pdf::loadView('pdf.transactions.invoice', [
            'transaction' => $transaction,
        ]);

        return $pdf->stream('Invoice-'.$transaction->transaction_number.'.pdf');
    }

    public function export(Request $request)
    {
        $this->authorize('transaction.view');

        ExportTransactionJob::dispatch(
            Auth::user(),
            Auth::user()->business_id ?? null,
            $request->all()
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            'Ekspor CSV sedang diproses di latar belakang.'
        );
    }
}
