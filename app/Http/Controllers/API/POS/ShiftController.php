<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\CloseShiftRequest;
use App\Http\Requests\API\POS\OpenShiftRequest;
use App\Http\Requests\API\POS\StoreCashLogRequest;
use App\Models\Sales\Shift;

class ShiftController extends Controller
{
    public function open(OpenShiftRequest $request)
    {
        $device = $request->user();

        $shift = Shift::create([
            'outlet_id' => $device->outlet_id,
            'user_id' => $request->validated('user_id'),
            'shift_number' => 'SH-'.date('YmdHis'),
            'opening_cash' => $request->validated('opening_cash'),
            'status' => 'open',
        ]);

        return $this->successResponse($shift, 'Shift berhasil dibuka');
    }

    public function close(CloseShiftRequest $request)
    {
        $device = $request->user();

        // Find active shift
        $shift = Shift::where('outlet_id', $device->outlet_id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $shift) {
            return $this->errorResponse('Tidak ada shift aktif', [], 404);
        }

        $shift->update([
            'closing_cash' => $request->validated('closing_cash'),
            'status' => 'closed',
        ]);

        return $this->successResponse($shift, 'Shift berhasil ditutup');
    }

    public function cashLog(StoreCashLogRequest $request)
    {
        $device = $request->user();

        $shift = Shift::where('outlet_id', $device->outlet_id)
            ->where('status', 'open')
            ->latest()
            ->first();

        if (! $shift) {
            return $this->errorResponse('Tidak ada shift aktif', [], 404);
        }

        $log = $shift->cashLogs()->create($request->validated());

        return $this->successResponse($log, 'Cash log berhasil ditambahkan');
    }

    public function sync(\Illuminate\Http\Request $request)
    {
        $device = $request->user();
        $payload = $request->validate([
            'shifts' => 'present|array',
            'shifts.*.id' => 'required|uuid',
            'shifts.*.user_id' => 'required|uuid',
            'shifts.*.shift_number' => 'nullable|string',
            'shifts.*.opening_cash' => 'numeric',
            'shifts.*.closing_cash' => 'nullable|numeric',
            'shifts.*.expected_cash' => 'nullable|numeric',
            'shifts.*.total_sales' => 'nullable|numeric',
            'shifts.*.status' => 'required|string',
            'shifts.*.opened_at' => 'required|date',
            'shifts.*.closed_at' => 'nullable|date',
            'shifts.*.cash_logs' => 'nullable|array',
            'shifts.*.cash_logs.*.id' => 'required|uuid',
            'shifts.*.cash_logs.*.type' => 'required|string',
            'shifts.*.cash_logs.*.amount' => 'required|numeric',
            'shifts.*.cash_logs.*.note' => 'nullable|string',
            'shifts.*.cash_logs.*.created_at' => 'required|date',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($device, $payload) {
            foreach ($payload['shifts'] ?? [] as $shiftData) {
                $shift = Shift::updateOrCreate(
                    ['id' => $shiftData['id']],
                    [
                        'outlet_id' => $device->outlet_id,
                        'user_id' => $shiftData['user_id'],
                        'shift_number' => $shiftData['shift_number'] ?? ('SH-'.date('YmdHis')),
                        'opening_cash' => $shiftData['opening_cash'] ?? 0,
                        'closing_cash' => $shiftData['closing_cash'] ?? 0,
                        'expected_cash' => $shiftData['expected_cash'] ?? 0,
                        'total_sales' => $shiftData['total_sales'] ?? 0,
                        'status' => $shiftData['status'],
                        'created_at' => $shiftData['opened_at'],
                        'closed_at' => $shiftData['closed_at'] ?? null,
                    ]
                );

                if (! empty($shiftData['cash_logs'])) {
                    foreach ($shiftData['cash_logs'] as $logData) {
                        $shift->cashLogs()->updateOrCreate(
                            ['id' => $logData['id']],
                            [
                                'type' => $logData['type'],
                                'amount' => $logData['amount'],
                                'note' => $logData['note'] ?? null,
                                'created_at' => $logData['created_at'],
                            ]
                        );
                    }
                }
            }
        });

        return $this->successResponse([], 'Sinkronisasi shift berhasil');
    }
}
