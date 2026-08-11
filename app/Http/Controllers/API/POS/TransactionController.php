<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Sales\TransactionService;
use App\Http\Requests\API\POS\StorePosTransactionRequest;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function store(StorePosTransactionRequest $request)
    {
        $device = $request->user();
        
        $transaction = $this->transactionService->syncOfflineTransaction($request->validated(), $device);
        
        return $this->successResponse([
            'transaction_id' => $transaction->id,
            'offline_id' => $transaction->offline_id
        ], 'Transaksi berhasil disinkronisasi');
    }
}
