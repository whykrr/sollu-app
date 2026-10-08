<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Events\Pos\PosCatalogNudgeEvent;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;

class ProductItemObserver
{
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
        $outletIds = [];
        if ($productItem->product_id) {
            $outletIds = DB::table('outlet_product')
                ->where('product_id', $productItem->product_id)
                ->pluck('outlet_id')
                ->all();
        }

        if (empty($outletIds) && $productItem->business_id) {
            $outletIds = Outlet::where('business_id', $productItem->business_id)->pluck('id')->all();
        }

        foreach ($outletIds as $outletId) {
            event(new PosCatalogNudgeEvent($outletId, 'product_item'));
        }
    }
}
