<?php

namespace App\Contracts\Audit;

use App\DTO\Audit\ActivityLogDTO;
use App\Models\Audit\ActivityLog;
use Illuminate\Database\Eloquent\Model;

interface ActivityLoggerInterface
{
    /**
     * Mencatat log audit (otomatis di-dispatch ke background queue).
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        ?string $businessId = null,
        ?string $outletId = null,
        array $properties = []
    ): void;

    /**
     * Mencatat log audit dengan DTO terstruktur (asinkron).
     */
    public function logDTO(ActivityLogDTO $dto): void;

    /**
     * Mencatat log audit secara sinkron (langsung ke database).
     */
    public function logNow(ActivityLogDTO $dto): ActivityLog;
}
