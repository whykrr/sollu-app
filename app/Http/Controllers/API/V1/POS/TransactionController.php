<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\POS;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\POS\BatchPosTransactionRequest;
use App\Http\Requests\API\V1\POS\StorePosTransactionRequest;
use App\Http\Requests\API\V1\POS\VoidPosTransactionRequest;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function store(StorePosTransactionRequest $request, TransactionService $transactionService): JsonResponse
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

    public function batchSync(BatchPosTransactionRequest $request, TransactionService $transactionService): JsonResponse
    {
        $device = $request->user();
        $data = $request->validated();

        // Resolusi default open shift untuk transaksi yang shift_id-nya kosong/tidak valid
        $openShiftId = null;
        if (! empty($device->outlet_id)) {
            $openShift = Shift::where('outlet_id', $device->outlet_id)
                ->where('status', 'open')
                ->latest()
                ->first();
            $openShiftId = $openShift?->id;
        }

        $transactionsData = $data['transactions'];
        foreach ($transactionsData as &$tx) {
            $sId = $tx['shift_id'] ?? null;
            if (empty($sId) || ! Str::isUuid((string) $sId)) {
                $tx['shift_id'] = $openShiftId;
            }
        }
        unset($tx);

        $syncedTransactions = $transactionService->syncOfflineTransactionsBatch(
            $transactionsData,
            $device
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Batch transaksi berhasil disinkronisasi',
            'data' => [
                'count' => count($syncedTransactions),
                'transactions' => $syncedTransactions,
            ],
        ], 200);
    }

    public function voidTransaction(
        VoidPosTransactionRequest $request,
        string $id,
        TransactionService $transactionService
    ): JsonResponse {
        $device = $request->user();
        $data = $request->validated();

        // 1. Validasi keberadaan transaksi dan isolasi outlet
        $transaction = Transaction::where('id', $id)->first();
        if (! $transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        if ($transaction->outlet_id !== $device->outlet_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Transaksi bukan milik outlet perangkat ini.',
            ], 403);
        }

        // 2. Cegah duplikasi void
        if ($transaction->status === TransactionStatus::Void || $transaction->status?->value === 'void' || ! empty($transaction->voided_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Transaksi sudah dibatalkan sebelumnya.',
            ], 422);
        }

        if (! in_array($transaction->status, [TransactionStatus::Completed, TransactionStatus::Paid], true)
            && ! in_array($transaction->status?->value, ['completed', 'paid'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya transaksi selesai/lunas yang dapat dibatalkan.',
            ], 422);
        }

        // 3. Validasi Otorisasi Supervisor
        $outlet = $device->outlet ?? Outlet::find($device->outlet_id);
        $business = $outlet->business;

        $isSupervisorPinRequired = false;
        if ($business->hasFeature(FeatureEnum::DOUBLE_AUTHORIZATION)) {
            $rawPinSetting = OutletSetting::where('outlet_id', $outlet->id)
                ->where('category', 'pos')
                ->where('key', 'enable_supervisor_pin')
                ->value('value');
            $isSupervisorPinRequired = filter_var($rawPinSetting, FILTER_VALIDATE_BOOLEAN);
        }

        $supervisorId = $data['supervisor_id'] ?? null;
        $supervisorPin = $data['supervisor_pin'] ?? null;

        if ($isSupervisorPinRequired && empty($supervisorId)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Otorisasi PIN supervisor wajib disertakan.',
            ], 422);
        }

        $causer = null;
        if (! empty($supervisorId)) {
            $supervisor = User::where('id', $supervisorId)
                ->where('business_id', $business->id)
                ->first();

            if (! $supervisor) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Supervisor tidak ditemukan.',
                ], 422);
            }

            // Validasi hak akses supervisi
            setPermissionsTeamId($business->id);
            $hasSupervisionPermission = $supervisor->hasPermissionTo(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value, 'web')
                || $supervisor->getAllPermissions()->contains('name', PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value);

            if (! $hasSupervisionPermission) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pengguna tidak memiliki izin supervisi transaksi.',
                ], 403);
            }

            // Validasi PIN
            if ($isSupervisorPinRequired || ! empty($supervisorPin)) {
                if (empty($supervisorPin) || empty($supervisor->pin) || ! Hash::check((string) $supervisorPin, $supervisor->pin)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'PIN supervisor tidak valid.',
                    ], 422);
                }
            }

            $causer = $supervisor;
        }

        if (! $causer) {
            $causer = $outlet->users()->first()
                ?? $business->users()->where('is_root_user', true)->first()
                ?? User::first();
        }

        // 4. Eksekusi void dan pemulihan stok FIFO melalui TransactionService
        $voidedTransaction = $transactionService->voidTransaction(
            $transaction,
            $causer,
            $data['reason'],
            $data['notes'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Transaksi berhasil dibatalkan (void)',
            'data' => [
                'id' => $voidedTransaction->id,
                'status' => 'void',
                'voided_at' => $voidedTransaction->voided_at?->toISOString() ?? now()->toISOString(),
                'transaction' => $voidedTransaction,
            ],
        ], 200);
    }
}
