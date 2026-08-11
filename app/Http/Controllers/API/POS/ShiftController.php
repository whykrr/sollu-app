<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\OpenShiftRequest;
use App\Http\Requests\API\POS\CloseShiftRequest;
use App\Http\Requests\API\POS\StoreCashLogRequest;
use App\Models\Sales\Shift;
use App\Models\Sales\ShiftCashLog;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function open(OpenShiftRequest $request)
    {
        $device = $request->user();
        
        $shift = Shift::create([
            'outlet_id' => $device->outlet_id,
            'user_id' => $request->validated('user_id'),
            'shift_number' => 'SH-' . date('YmdHis'),
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

        if (!$shift) {
            return $this->errorResponse('Tidak ada shift aktif', 404);
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

        if (!$shift) {
            return $this->errorResponse('Tidak ada shift aktif', 404);
        }

        $log = $shift->cashLogs()->create($request->validated());

        return $this->successResponse($log, 'Cash log berhasil ditambahkan');
    }
}
