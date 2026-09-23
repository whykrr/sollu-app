<?php

namespace App\DTO\Audit;

use Illuminate\Database\Eloquent\Model;

class ActivityLogDTO
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(
        public readonly string $module,
        public readonly string $action,
        public readonly string $description,
        public readonly ?string $subjectType = null,
        public readonly ?string $subjectId = null,
        public readonly ?string $causerType = null,
        public readonly ?string $causerId = null,
        public readonly ?string $businessId = null,
        public readonly ?string $outletId = null,
        public readonly array $properties = [],
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}

    /**
     * Factory dari model Eloquent.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function fromModels(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?Model $causer = null,
        ?string $businessId = null,
        ?string $outletId = null,
        array $properties = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): self {
        return new self(
            module: $module,
            action: $action,
            description: $description,
            subjectType: $subject ? $subject->getMorphClass() : null,
            subjectId: $subject ? (string) $subject->getKey() : null,
            causerType: $causer ? $causer->getMorphClass() : null,
            causerId: $causer ? (string) $causer->getKey() : null,
            businessId: $businessId,
            outletId: $outletId,
            properties: $properties,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    /**
     * Serialisasi DTO ke array payload database.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module' => $this->module,
            'action' => $this->action,
            'description' => $this->description,
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
            'causer_type' => $this->causerType,
            'causer_id' => $this->causerId,
            'business_id' => $this->businessId,
            'outlet_id' => $this->outletId,
            'properties' => empty($this->properties) ? null : $this->properties,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
