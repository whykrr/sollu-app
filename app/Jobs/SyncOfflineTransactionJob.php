<?php

namespace App\Jobs;

use App\Services\Transaction\TransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncOfflineTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    protected $device;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data, $device)
    {
        $this->data = $data;
        $this->device = $device;
    }

    /**
     * Execute the job.
     */
    public function handle(TransactionService $transactionService): void
    {
        if (!empty($this->data['shift_id'])) {
            $shift = \App\Models\Sales\Shift::find($this->data['shift_id']);
            if (!$shift) {
                // Delay execution for 60 seconds to wait for shift sync
                $this->release(60);
                return;
            }
        }

        $transactionService->syncOfflineTransaction($this->data, $this->device);
    }
}
