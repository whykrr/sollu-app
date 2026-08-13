<?php

namespace App\Http\Controllers;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequest;
use App\Models\Promo;
use App\Services\PromoService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        protected PromoService $promoService
    ) {}

    public function index(Request $request)
    {
        $this->authorize(PermissionEnum::PROMO_VIEW->value);

        $limit = $request->query('limit', 20);

        $promos = Promo::currentBusiness()
            ->with(['outlets:id,name', 'inventoryItems:id,name'])
            ->filters($request->only(['search', 'status', 'target', 'type', 'outlet']))
            ->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))
            ->paginate($limit)
            ->withQueryString();

        return inertia('Promotion/Index', [
            'promos' => $promos,
            'filters' => $request->only(['search', 'status', 'target', 'type', 'outlet']),
        ]);
    }

    public function store(StorePromoRequest $request)
    {
        $this->promoService->create(
            array_merge($request->validated(), [
                'business_id' => $request->user()->business_id,
            ]),
            $request->user()
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function update(UpdatePromoRequest $request, Promo $promotion)
    {
        try {
            $this->promoService->update($promotion, $request->validated(), $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::UPDATE_SUCCESS
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }

    public function destroy(Promo $promotion, Request $request)
    {
        $this->authorize(PermissionEnum::PROMO_DELETE->value);

        try {
            $this->promoService->delete($promotion, $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::DELETE_SUCCESS
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }

    public function publish(Promo $promotion, Request $request)
    {
        $this->authorize(PermissionEnum::PROMO_PUBLISH->value);

        try {
            $this->promoService->publish($promotion, $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Promo berhasil dipublish!'
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }

    public function unpublish(Promo $promotion, Request $request)
    {
        $this->authorize(PermissionEnum::PROMO_PUBLISH->value);

        try {
            $this->promoService->unpublish($promotion, $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Promo berhasil dinonaktifkan!'
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }
}
