<?php

namespace App\Http\Controllers\App\Promotion;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Promotion\GetPromotionRequest;
use App\Http\Requests\App\StorePromoRequest;
use App\Http\Requests\App\UpdatePromoRequest;
use App\Models\Promo;
use App\Services\App\Promotion\PromoService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        protected PromoService $promoService
    ) {}

    public function index(GetPromotionRequest $request)
    {
        $limit = $request->query('limit', 20);
        $filters = $request->only(['search', 'status', 'target', 'target_type', 'type', 'promo_type', 'outlet', 'sort', 'direction']);

        $promos = Promo::currentBusiness()
            ->filters($filters)
            ->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))
            ->paginate($limit)
            ->withQueryString();

        return inertia('Promotion/Index', [
            'promos' => $promos,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Promo $promotion)
    {
        $this->authorize(PermissionEnum::PROMO_VIEW->value);

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        return response()->json(
            $promotion->load(['outlets:id,name', 'inventoryItems:id,name'])
        );
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
        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

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

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

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

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        try {
            $this->promoService->publish($promotion, $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Promo berhasil dipublikasikan.'
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

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        try {
            $this->promoService->unpublish($promotion, $request->user());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                'Promo berhasil dinonaktifkan.'
            );
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }
}
