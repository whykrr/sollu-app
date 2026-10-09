<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\POS\PosDeltaSyncRequest;
use App\Services\App\Transaction\DeltaSyncService;
use App\Services\App\Transaction\MasterDataSyncService as TransactionMasterDataSyncService;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Unduh keseluruhan master data (Full Snapshot) untuk bootstrapping awal.
     */
    public function initial(Request $request, TransactionMasterDataSyncService $service)
    {
        $device = $request->user();
        $device->loadMissing('outlet.business');

        $payload = $service->getPayload($device);

        return $this->successResponse($payload, 'Initial master data snapshot retrieved successfully');
    }

    /**
     * Unduh perubahan inkremental data (Delta Sync) berbasis parameter updated_since.
     */
    public function delta(PosDeltaSyncRequest $request, DeltaSyncService $service)
    {
        $device = $request->user();
        $device->loadMissing('outlet.business');

        $updatedSince = $request->validated('updated_since');
        $entitiesParam = $request->validated('entities');
        $entities = $entitiesParam !== null && $entitiesParam !== ''
            ? array_filter(array_map('trim', explode(',', $entitiesParam)))
            : null;

        $payload = $service->getDelta($device, $updatedSince, $entities);

        return $this->successResponse($payload, 'Delta master catalog synchronized successfully');
    }

    /**
     * Fallback alias untuk klien versi lama (deprecated).
     */
    public function masterData(Request $request, TransactionMasterDataSyncService $service)
    {
        return $this->initial($request, $service);
    }
}
