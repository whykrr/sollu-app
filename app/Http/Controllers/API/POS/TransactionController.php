<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\StorePosTransactionRequest;
use App\Services\Transaction\TransactionService;

class TransactionController extends Controller
{
    public function store(StorePosTransactionRequest $request, TransactionService $transactionService)
    {
        $device = $request->user();

        // Data payload
        $data = $request->validated();
        
        // Atur shift_id jika tidak dikirim (atau tidak valid)
        $shiftId = $data['shift_id'] ?? null;
        if (! empty($shiftId)) {
            $shift = \Illuminate\Support\Str::isUuid($shiftId) ? \App\Models\Sales\Shift::find($shiftId) : null;
            if (! $shift) {
                $outletId = $device->outlet_id ?? null;
                if ($outletId) {
                    $openShift = \App\Models\Sales\Shift::where('outlet_id', $outletId)
                        ->where('status', 'open')
                        ->latest()
                        ->first();
                    $data['shift_id'] = $openShift?->id;
                } else {
                    $data['shift_id'] = null;
                }
            }
        }

        // Proses sinkronisasi secara synchronous
        $transaction = $transactionService->syncOfflineTransaction($data, $device);

        return $this->successResponse([
            'transaction' => $transaction,
        ], 'Transaksi berhasil disinkronisasi', 200);
    }
}
