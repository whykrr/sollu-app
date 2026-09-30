<?php

namespace App\Http\Controllers\App\Promotion;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Promotion\GetPromotionRequest;
use App\Http\Requests\App\Promotion\StorePromotionRequest;
use App\Http\Requests\App\Promotion\UpdatePromotionRequest;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Services\App\Promotion\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    /**
     * Display a listing of promotions with server-side filters.
     */
    public function index(GetPromotionRequest $request): Response
    {
        $limit = $request->integer('limit', $request->integer('perpage', 20));
        $filters = $request->only([
            'search',
            'status',
            'target_scope',
            'target',
            'target_type',
            'discount_type',
            'promo_type',
            'type',
            'application_mode',
            'mode',
            'outlet',
            'outlet_id',
            'sort',
            'direction',
        ]);

        $filters['outlet'] = SelectedOutlet::resolveEffectiveOutletId($request->user(), $filters['outlet'] ?? $filters['outlet_id'] ?? null);

        $promotions = Promotion::currentBusiness()
            ->filters($filters)
            ->sortable($request->get('sort', 'updated_at'), $request->get('direction', 'desc'))
            ->paginate($limit)
            ->withQueryString();

        $outlets = Outlet::where('business_id', $request->user()->business_id)
            ->where('is_active', true)
            ->get(['id', 'name']);

        return inertia('Promotion/Index', [
            'promotions' => $promotions,
            'promos' => $promotions, // Backwards compatibility for UI
            'filters' => $filters,
            'outlets' => $outlets,
        ]);
    }

    /**
     * Display the specified promotion details on-demand via JSON.
     */
    public function show(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorize(PermissionEnum::PROMO_VIEW->value);

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        return response()->json(
            $promotion->load([
                'outlets:id,name',
                'categories:id,name',
                'products:id,name',
                'productItems:id,name,sku',
                'creator:id,name',
                'publisher:id,name',
            ])
        );
    }

    /**
     * Store a newly created promotion in storage.
     */
    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $this->promotionService->create(
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

    /**
     * Update the specified promotion in storage.
     */
    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        try {
            $this->promotionService->update($promotion, $request->validated(), $request->user());

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

    /**
     * Remove the specified promotion from storage.
     */
    public function destroy(Promotion $promotion, Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PROMO_DELETE->value);

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        try {
            $this->promotionService->delete($promotion, $request->user());

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

    /**
     * Publish a draft or inactive promotion.
     */
    public function publish(Promotion $promotion, Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PROMO_PUBLISH->value);

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        try {
            $this->promotionService->publish($promotion, $request->user());

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

    /**
     * Unpublish an active promotion.
     */
    public function unpublish(Promotion $promotion, Request $request): RedirectResponse
    {
        $this->authorize(PermissionEnum::PROMO_PUBLISH->value);

        if ($promotion->business_id !== $request->user()?->business_id) {
            abort(403);
        }

        try {
            $this->promotionService->unpublish($promotion, $request->user());

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
