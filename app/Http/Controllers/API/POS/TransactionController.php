<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\StorePosTransactionRequest;
use App\Jobs\SyncOfflineTransactionJob;

class TransactionController extends Controller
{
    public function store(StorePosTransactionRequest $request)
    {
        $device = $request->user();

        // Dispatch job untuk memproses sinkronisasi secara asynchronous
        SyncOfflineTransactionJob::dispatch($request->validated(), $device);

        return $this->successResponse([
            'status' => 'queued',
        ], 'Transaksi berhasil dimasukkan ke antrean sinkronisasi', 200);
    }
}
