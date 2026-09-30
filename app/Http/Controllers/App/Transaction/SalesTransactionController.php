<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Transaction;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Enums\DatePresetEnum;
use App\Enums\TransactionTypeEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Transaction\Sales\CancelSalesTransactionRequest;
use App\Http\Requests\App\Transaction\Sales\DeleteSalesTransactionRequest;
use App\Http\Requests\App\Transaction\Sales\GetSalesTransactionRequest;
use App\Http\Requests\App\Transaction\Sales\RecordPaymentTransactionRequest;
use App\Http\Requests\App\Transaction\Sales\StoreSalesTransactionRequest;
use App\Http\Requests\App\Transaction\Sales\UpdateDueDateRequest;
use App\Http\Requests\App\Transaction\Sales\UpdateSalesTransactionRequest;
use App\Models\Sales\Transaction;
use App\Services\App\Transaction\Contracts\B2bTransactionServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SalesTransactionController extends Controller
{
    public function __construct(
        private readonly B2bTransactionServiceInterface $b2bTransactionService,
    ) {}

    /**
     * Display a listing of sales transactions.
     */
    public function index(GetSalesTransactionRequest $request): InertiaResponse|JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $effectiveOutletId = SelectedOutlet::resolveEffectiveOutletId(
            $user,
            $validated['outlet_id'] ?? $validated['outlet'] ?? null
        );

        $dateRange = DatePresetEnum::resolveRange(
            $validated['preset'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null
        );

        $filters = [
            ...$validated,
            'outlet' => $effectiveOutletId,
            'outlet_id' => $effectiveOutletId,
            'start_date' => $dateRange['start_date'],
            'end_date' => $dateRange['end_date'],
            'preset' => $dateRange['preset'],
        ];

        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? 'desc';
        $perPage = (int) ($validated['perpage'] ?? $validated['per_page'] ?? 15);

        $accessibleOutlets = SelectedOutlet::make($user)->getAccessibleOutletsList();
        $accessibleOutletIds = array_values(array_filter(array_map(
            fn ($item) => is_array($item) ? ($item['id'] ?? null) : $item->id,
            $accessibleOutlets
        )));

        $query = Transaction::query()
            ->select([
                'id',
                'outlet_id',
                'customer_id',
                'channel',
                'type',
                'transaction_number',
                'transaction_date',
                'total',
                'balance_due',
                'status',
                'payment_status',
                'created_at',
            ])
            ->where('type', TransactionTypeEnum::Invoice->value)
            ->when(
                $effectiveOutletId !== null,
                fn (Builder $q) => $q->where('outlet_id', $effectiveOutletId),
                fn (Builder $q) => $q->whereIn('outlet_id', $accessibleOutletIds)
            )
            ->with([
                'customer:id,name,phone,email',
                'outlet:id,name',
                'invoice:id,transaction_id,invoice_number,invoice_date,due_date,payment_term,status',
            ])
            ->filters($filters)
            ->sortable($sort, $direction);

        $transactions = $query
            ->paginate($perPage)
            ->withQueryString();

        if ($request->wantsJson()) {
            return Response::json($transactions);
        }

        return Inertia::render('Transaction/Sales/Index', [
            'transactions' => $transactions,
            'filters' => $filters,
            'params' => $filters,
            'outlets' => $accessibleOutlets,
        ]);
    }

    /**
     * Display the specified transaction details.
     */
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $transaction->load([
            'items.product',
            'items.productItem',
            'items.inventoryItem',
            'payments.paymentMethod',
            'invoice',
            'customer',
            'outlet',
            'creator',
            'promos',
        ]);

        return Response::json([
            'data' => $transaction,
        ]);
    }

    /**
     * Store a newly created sales transaction.
     */
    public function store(StoreSalesTransactionRequest $request): JsonResponse|RedirectResponse
    {
        $dto = $request->toDTO();
        $user = $request->user();

        $transaction = $this->b2bTransactionService->createTransaction($dto, $user);

        // Jika user memilih untuk langsung menerbitkan faktur
        if ($request->boolean('issue_now')) {
            $paymentData = $request->input('payment', []);
            $transaction = $this->b2bTransactionService->issueInvoice($transaction, $user, $paymentData);
            $message = 'Faktur penjualan berhasil diterbitkan.';
        } else {
            $message = 'Draf penjualan berhasil disimpan.';
        }

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
                'data' => $transaction,
            ], 201);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }

    /**
     * Update an existing draft sales transaction.
     */
    public function update(UpdateSalesTransactionRequest $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $dto = $request->toDTO();
        $transaction = $this->b2bTransactionService->updateDraftTransaction($transaction, $dto, $user);

        // Jika user memilih untuk langsung menerbitkan faktur
        if ($request->boolean('issue_now')) {
            $paymentData = $request->input('payment', []);
            $transaction = $this->b2bTransactionService->issueInvoice($transaction, $user, $paymentData);
            $message = 'Faktur penjualan berhasil diterbitkan.';
        } else {
            $message = 'Draf penjualan berhasil diperbarui.';
        }

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
                'data' => $transaction,
            ]);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }

    /**
     * Delete an existing draft sales transaction.
     */
    public function destroy(DeleteSalesTransactionRequest $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $this->b2bTransactionService->deleteDraftTransaction($transaction, $user);
        $message = 'Draf penjualan berhasil dihapus.';

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
            ]);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }

    /**
     * Issue invoice for an existing transaction.
     */
    public function issue(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $paymentData = $request->input('payment', []);
        $transaction = $this->b2bTransactionService->issueInvoice($transaction, $user, $paymentData);
        $message = 'Faktur berhasil diterbitkan.';

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
                'data' => $transaction,
            ]);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }

    /**
     * Record payment for a sales transaction.
     */
    public function recordPayment(RecordPaymentTransactionRequest $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $dto = $request->toDTO();
        $transaction = $this->b2bTransactionService->recordPayment($transaction, $dto, $user);
        $message = 'Pembayaran berhasil dicatat.';

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
                'data' => $transaction,
            ]);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }

    /**
     * Update due date for a transaction invoice.
     */
    public function updateDueDate(UpdateDueDateRequest $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $transaction = $this->b2bTransactionService->updateDueDate(
            $transaction,
            (string) $request->validated('due_date'),
            $user,
            $request->validated('reason')
        );
        $message = 'Tanggal jatuh tempo berhasil diperbarui.';

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
                'data' => $transaction,
            ]);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }

    /**
     * Cancel sales transaction and restore FIFO inventory cost layers.
     */
    public function cancel(CancelSalesTransactionRequest $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, AuthorizationMessage::CANT_ACCESS_DATA);
        }

        $transaction = $this->b2bTransactionService->cancelTransaction(
            $transaction,
            $user,
            $request->validated('reason')
        );
        $message = 'Faktur penjualan berhasil dibatalkan dan FIFO cost layer telah dipulihkan.';

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $message,
                'data' => $transaction,
            ]);
        }

        return redirect()
            ->back()
            ->with(FlashDataVariable::SUCCESS->value, $message);
    }
}
