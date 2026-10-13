<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Models\OutletSetting;
use App\Services\Pos\PosNudgeQueueService;

class OutletSettingObserver
{
    public function __construct(
        protected PosNudgeQueueService $nudgeQueue,
    ) {}

    public function saved(OutletSetting $setting): void
    {
        if ($setting->outlet_id) {
            $this->nudgeQueue->queueSignal($setting->outlet_id, 'setting');
        }
    }

    public function deleted(OutletSetting $setting): void
    {
        if ($setting->outlet_id) {
            $this->nudgeQueue->queueSignal($setting->outlet_id, 'setting');
        }
    }
}
