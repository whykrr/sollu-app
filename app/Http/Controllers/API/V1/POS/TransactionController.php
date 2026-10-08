<?php

namespace App\Http\Controllers\API\V1\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\POS\StorePosTransactionRequest;
use App\Models\Sales\Shift;
use App\Services\App\Transaction\TransactionService;
use Illuminate\Support\Str;

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
            $shift = Str::isUuid($shiftId) ? Shift::find($shiftId) : null;
            if (! $shift) {
                $outletId = $device->outlet_id ?? null;
                if ($outletId) {
                    $openShift = Shift::where('outlet_id', $outletId)
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
