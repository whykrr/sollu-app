<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Services\Transaction\MasterDataSyncService as TransactionMasterDataSyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function masterData(Request $request, TransactionMasterDataSyncService $service)
    {
        $device = $request->user();

        // Ensure relationships are loaded
        $device->load('outlet.business');

        $payload = $service->getPayload($device);

        return $this->successResponse($payload, 'Master data retrieved successfully');
    }
}
