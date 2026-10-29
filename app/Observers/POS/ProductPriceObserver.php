<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Models\Master\Product;
use App\Models\Master\ProductPrice;
use App\Models\Outlet;
use App\Services\Pos\PosNudgeQueueService;
use Illuminate\Support\Facades\DB;

class ProductPriceObserver
{
    public function __construct(
        protected PosNudgeQueueService $nudgeQueue
    ) {}

    /**
     * @var array<string, array<string>>
     */
    protected static array $outletIdsCache = [];

    public function saved(ProductPrice $productPrice): void
    {
        $this->queueNudge($productPrice);
    }

    public function deleted(ProductPrice $productPrice): void
    {
        $this->queueNudge($productPrice);
    }

    protected function queueNudge(ProductPrice $productPrice): void
    {
        if ($productPrice->outlet_id) {
            $this->nudgeQueue->queueSignal($productPrice->outlet_id, 'product_price');

            return;
        }

        $productId = $productPrice->product_id;
        if (! isset(static::$outletIdsCache[$productId])) {
            $outletIds = DB::table('outlet_product')
                ->where('product_id', $productId)
                ->where('is_enabled', true)
                ->pluck('outlet_id')
                ->all();

            if (empty($outletIds)) {
                $businessId = Product::where('id', $productId)->value('business_id');
                if ($businessId) {
                    $outletIds = Outlet::where('business_id', $businessId)->pluck('id')->all();
                }
            }

            static::$outletIdsCache[$productId] = $outletIds ?? [];
        }

        $this->nudgeQueue->queueSignalsForOutlets(static::$outletIdsCache[$productId], 'product_price');
    }
}
