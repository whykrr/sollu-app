<?php

namespace App\Settings;

class Settings
{
    public ProductSettings $product;
    public StockSettings $stock;

    public function __construct(array $data = [])
    {
        $this->product = new ProductSettings($data['product'] ?? []);
        $this->stock   = new StockSettings($data['stock'] ?? []);
    }

    public function toArray(): array
    {
        return [
            'product' => $this->product->toArray(),
            'stock'   => $this->stock->toArray(),
        ];
    }
}
