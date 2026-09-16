<?php

namespace App\Http\Controllers\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cockpit\BusinessType\StoreBusinessTypeRequest;
use App\Http\Requests\Cockpit\BusinessType\UpdateBusinessTypeFeaturesRequest;
use App\Http\Requests\Cockpit\BusinessType\UpdateBusinessTypeRequest;
use App\Models\BusinessType;
use App\Models\Feature;
use App\Services\Cockpit\BusinessTypeService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessTypeController extends Controller
{
    public function __construct(
        protected BusinessTypeService $businessTypeService
    ) {}

    public function index(): Response
    {
        $businessTypes = BusinessType::query()
            ->withCount('businesses')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $allFeatures = Feature::getAllCached();

        return Inertia::render('Cockpit/BusinessType/Index', [
            'businessTypes' => $businessTypes,
            'allFeatures' => $allFeatures,
        ]);
    }

    public function store(StoreBusinessTypeRequest $request): RedirectResponse
    {
        $this->businessTypeService->create($request->validated());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function show(int $id): JsonResponse
    {
        $businessType = BusinessType::query()
            ->withCount('businesses')
            ->findOrFail($id);

        return response()->json([
            ...$businessType->toArray(),
            'all_features' => Feature::getAllCached(),
        ]);
    }

    public function update(UpdateBusinessTypeRequest $request, int $id): RedirectResponse
    {
        /** @var BusinessType */
        $businessType = BusinessType::findOrFail($id);

        $this->businessTypeService->update($businessType, $request->validated());

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function toggleVisibility(Request $request, int $id): RedirectResponse
    {
        /** @var BusinessType */
        $businessType = BusinessType::findOrFail($id);

        $this->businessTypeService->toggleVisibility($businessType);

        $statusText = $businessType->is_visible ? 'ditampilkan di form pendaftaran' : 'disembunyikan dari form pendaftaran';

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            "Jenis bisnis {$businessType->name} berhasil {$statusText}."
        );
    }

    public function updateFeatures(UpdateBusinessTypeFeaturesRequest $request, int $id): RedirectResponse
    {
        /** @var BusinessType */
        $businessType = BusinessType::findOrFail($id);

        $this->businessTypeService->updateFeatures(
            $businessType,
            $request->validated()['features'] ?? []
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            "Hak akses fitur bawaan untuk jenis bisnis {$businessType->name} berhasil diperbarui!"
        );
    }

    public function destroy(int $id): RedirectResponse
    {
        $businessType = BusinessType::withCount('businesses')->findOrFail($id);

        try {
            $this->businessTypeService->delete($businessType);

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::PURGE_SUCCESS
            );
        } catch (DomainException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }
}
