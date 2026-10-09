<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Models\Master\Product;
use App\Models\Outlet;
use App\Services\Pos\PosNudgeQueueService;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    public function __construct(
        protected PosNudgeQueueService $nudgeQueue
    ) {}

    public function saved(Product $product): void
    {
        $this->broadcastNudge($product);
    }

    public function deleted(Product $product): void
    {
        $this->broadcastNudge($product);
    }

    public function restored(Product $product): void
    {
        $this->broadcastNudge($product);
    }

    protected function broadcastNudge(Product $product): void
    {
        $outletIds = DB::table('outlet_product')
            ->where('product_id', $product->id)
            ->where('is_enabled', true)
            ->pluck('outlet_id')
            ->all();

        if (empty($outletIds) && $product->business_id) {
            $outletIds = Outlet::where('business_id', $product->business_id)->pluck('id')->all();
        }

        $this->nudgeQueue->queueSignalsForOutlets($outletIds, 'product');
    }
}
