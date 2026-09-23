<?php

namespace App\Jobs\Audit;

use App\Models\Audit\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class RecordActivityLogJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $data = $this->payload;
            if (! isset($data['id'])) {
                $data['id'] = Str::uuid()->toString();
            }

            ActivityLog::create($data);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal menyimpan record log audit di background worker: '.$e->getMessage(), [
                'payload' => $this->payload,
            ]);
        }
    }
}
