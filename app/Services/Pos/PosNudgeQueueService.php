<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Events\Pos\PosCatalogNudgeEvent;
use Illuminate\Support\Facades\DB;

class PosNudgeQueueService
{
    /**
     * When running unit tests, whether to defer flushing until explicitly called.
     */
    public static bool $deferInTests = false;

    /**
     * Map of outletId => array of unique entity types.
     * e.g. ['outlet-uuid-1' => ['product' => true, 'product_price' => true]]
     *
     * @var array<string, array<string, bool>>
     */
    protected array $pendingSignals = [];

    protected bool $isFlushHookRegistered = false;

    /**
     * Antrekan sinyal nudge untuk suatu outlet.
     */
    public function queueSignal(int|string $outletId, string $entityType): void
    {
        $this->pendingSignals[(string) $outletId][$entityType] = true;

        if (app()->runningUnitTests() && ! static::$deferInTests) {
            $this->flush();

            return;
        }

        $this->registerFlushCallback();
    }

    /**
     * Antrekan sinyal untuk banyak outlet sekaligus.
     *
     * @param  array<int|string>  $outletIds
     */
    public function queueSignalsForOutlets(array $outletIds, string $entityType): void
    {
        foreach ($outletIds as $outletId) {
            $this->pendingSignals[(string) $outletId][$entityType] = true;
        }

        if (app()->runningUnitTests() && ! static::$deferInTests) {
            $this->flush();

            return;
        }

        $this->registerFlushCallback();
    }

    /**
     * Daftarkan deferred callback saat transaksi database ter-commit atau request selesai.
     */
    protected function registerFlushCallback(): void
    {
        if ($this->isFlushHookRegistered) {
            return;
        }

        if (app()->runningUnitTests() && static::$deferInTests) {
            return;
        }

        $this->isFlushHookRegistered = true;

        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function (): void {
                $this->flush();
            });
        } else {
            app()->terminating(function (): void {
                $this->flush();
            });
        }
    }

    /**
     * Broadcast semua sinyal yang terkumpul secara teragregasi, tepat 1 kali per outlet.
     */
    public function flush(): void
    {
        $this->isFlushHookRegistered = false;

        if (empty($this->pendingSignals)) {
            return;
        }

        $signals = $this->pendingSignals;
        $this->pendingSignals = [];

        foreach ($signals as $outletId => $entitiesMap) {
            $entities = array_keys($entitiesMap);
            event(new PosCatalogNudgeEvent($outletId, $entities));
        }
    }

    /**
     * Cek apakah ada antrean sinyal tertunda (terutama untuk testing).
     */
    public function hasPendingSignals(): bool
    {
        return ! empty($this->pendingSignals);
    }
}
