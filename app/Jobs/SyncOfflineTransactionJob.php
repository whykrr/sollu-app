<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\Transaction\TransactionService;

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
        $transactionService->syncOfflineTransaction($this->data, $this->device);
    }
}
