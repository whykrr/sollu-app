<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Services\Sales\MasterDataSyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function masterData(Request $request, MasterDataSyncService $service)
    {
        $device = $request->user();

        // Ensure relationships are loaded
        $device->load('outlet.business');

        $payload = $service->getPayload($device);

        return $this->successResponse($payload, 'Master data retrieved successfully');
    }
}
