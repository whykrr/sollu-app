<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Services\App\Transaction\MasterDataSyncService as TransactionMasterDataSyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function masterData(Request $request, TransactionMasterDataSyncService $service)
    {
        $device = $request->user();

        // Ensure relationships are loaded without duplicate queries
        $device->loadMissing('outlet.business');

        $force = $request->boolean('force');
        $payload = $service->getPayload($device, $force);

        return $this->successResponse($payload, 'Master data retrieved successfully');
    }
}
