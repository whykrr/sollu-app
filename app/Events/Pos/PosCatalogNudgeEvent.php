<?php

declare(strict_types=1);

namespace App\Events\Pos;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PosCatalogNudgeEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string|array<string>  $entityType
     */
    public function __construct(
        public int|string $outletId,
        public string|array $entityType = 'product',
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("outlet.{$this->outletId}.pos"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pos.catalog.nudge';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $entities = is_array($this->entityType)
            ? array_values(array_unique($this->entityType))
            : [$this->entityType];

        return [
            'event' => 'pos.catalog.nudge',
            'outlet_id' => (string) $this->outletId,
            'entity_type' => implode(',', $entities),
            'entities' => $entities,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
