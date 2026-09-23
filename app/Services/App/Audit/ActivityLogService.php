<?php

namespace App\Services\App\Audit;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\DTO\Audit\ActivityLogDTO;
use App\Jobs\Audit\RecordActivityLogJob;
use App\Models\Audit\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogService implements ActivityLoggerInterface
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
    ): void {
        $dto = $this->buildDTO(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            causer: $causer,
            businessId: $businessId,
            outletId: $outletId,
            properties: $properties
        );

        $this->logDTO($dto);
    }

    /**
     * Mencatat log audit dengan DTO terstruktur (asinkron).
     */
    public function logDTO(ActivityLogDTO $dto): void
    {
        try {
            RecordActivityLogJob::dispatch($dto->toArray());
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal mendispatch background audit log: '.$e->getMessage(), [
                'dto' => $dto->toArray(),
            ]);
        }
    }

    /**
     * Mencatat log audit secara sinkron (langsung ke database).
     */
    public function logNow(ActivityLogDTO $dto): ActivityLog
    {
        $payload = $dto->toArray();
        if (! isset($payload['id'])) {
            $payload['id'] = Str::uuid()->toString();
        }

        return ActivityLog::create($payload);
    }

    /**
     * Membangun DTO dengan auto-resolution tenant, outlet, dan request context.
     *
     * @param  array<string, mixed>  $properties
     */
    protected function buildDTO(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        ?string $businessId = null,
        ?string $outletId = null,
        array $properties = []
    ): ActivityLogDTO {
        $resolvedCauser = $causer ?? Auth::user();

        $resolvedBusinessId = $businessId
            ?? ($resolvedCauser && isset($resolvedCauser->business_id) ? $resolvedCauser->business_id : null)
            ?? ($subject && isset($subject->business_id) ? $subject->business_id : null);

        $resolvedOutletId = $outletId
            ?? ($subject && isset($subject->outlet_id) ? $subject->outlet_id : null)
            ?? ($resolvedCauser && isset($resolvedCauser->active_outlet_id) ? $resolvedCauser->active_outlet_id : null);

        $ipAddress = null;
        $userAgent = null;

        if (app()->bound('request') && request()) {
            $ipAddress = request()->ip();
            $userAgent = request()->userAgent();
        }

        return ActivityLogDTO::fromModels(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            causer: $resolvedCauser,
            businessId: $resolvedBusinessId,
            outletId: $resolvedOutletId,
            properties: $properties,
            ipAddress: $ipAddress,
            userAgent: $userAgent
        );
    }
}
