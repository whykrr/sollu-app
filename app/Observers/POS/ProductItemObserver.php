<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Services\Pos\PosNudgeQueueService;
use Illuminate\Support\Facades\DB;

class ProductItemObserver
{
    public function __construct(
        protected PosNudgeQueueService $nudgeQueue
    ) {}

    /**
     * @var array<string, array<string>>
     */
    protected static array $outletIdsCache = [];

    public function saved(ProductItem $productItem): void
    {
        $this->broadcastNudge($productItem);
    }

    public function deleted(ProductItem $productItem): void
    {
        $this->broadcastNudge($productItem);
    }

    public function restored(ProductItem $productItem): void
    {
        $this->broadcastNudge($productItem);
    }

    protected function broadcastNudge(ProductItem $productItem): void
    {
        $productId = $productItem->product_id;
        $outletIds = [];

        if ($productId) {
            if (! isset(static::$outletIdsCache[$productId])) {
                $ids = DB::table('outlet_product')
                    ->where('product_id', $productId)
                    ->where('is_enabled', true)
                    ->pluck('outlet_id')
                    ->all();

                if (empty($ids) && $productItem->business_id) {
                    $ids = Outlet::where('business_id', $productItem->business_id)->pluck('id')->all();
                }

                static::$outletIdsCache[$productId] = $ids ?? [];
            }
            $outletIds = static::$outletIdsCache[$productId];
        } elseif ($productItem->business_id) {
            $outletIds = Outlet::where('business_id', $productItem->business_id)->pluck('id')->all();
        }

        $this->nudgeQueue->queueSignalsForOutlets($outletIds, 'product_item');
    }
}
