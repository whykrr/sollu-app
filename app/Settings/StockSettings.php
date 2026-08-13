<?php

namespace App\Settings;

class StockSettings
{
    public string $mode;

    public bool $strict;

    public bool $batchTracking;

    public bool $allowBatchOverride;

    public bool $conversion;

    public bool $conversionValidate;

    public bool $transferValidate;

    public function __construct(array $data = [])
    {
        $this->mode = $data['mode'] ?? 'FIFO';
        $this->strict = $data['strict'] ?? true;
        $this->batchTracking = $data['batch_tracking'] ?? false;
        $this->allowBatchOverride = $data['allow_batch_override'] ?? false;
        $this->conversion = $data['conversion'] ?? false;
        $this->conversionValidate = $data['conversion_validate'] ?? false;
        $this->transferValidate = $data['transfer_validate'] ?? false;
    }

    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'strict' => $this->strict,
            'batch_tracking' => $this->batchTracking,
            'allow_batch_override' => $this->allowBatchOverride,
            'conversion' => $this->conversion,
            'conversion_validate' => $this->conversionValidate,
            'transfer_validate' => $this->transferValidate,
        ];
    }
}
