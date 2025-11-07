<?php

namespace App\Settings;

class ProductSettings
{
    public bool $variant;

    public function __construct(array $data = [])
    {
        $this->variant = $data['variant'] ?? false;
    }

    public function toArray(): array
    {
        return [
            'variant' => $this->variant,
        ];
    }
}
