<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $openingCash = (float) ($this->opening_cash ?? 0);
        $closingCash = (float) ($this->closing_cash ?? 0);
        $expectedCash = (float) ($this->expected_cash ?? 0);
        $totalSales = (float) ($this->total_sales ?? 0);

        return [
            'id' => $this->id,
            'outlet_id' => $this->outlet_id,
            'user_id' => $this->user_id,
            'shift_number' => $this->shift_number,
            'opening_cash' => $openingCash,
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'total_sales' => $totalSales,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'created_at' => $this->created_at,
            'closed_at' => $this->closed_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'outlet' => $this->whenLoaded('outlet', function () {
                return [
                    'id' => $this->outlet->id,
                    'name' => $this->outlet->name,
                ];
            }),
            'cash_logs' => $this->whenLoaded('cashLogs', function () {
                return $this->cashLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'shift_id' => $log->shift_id,
                        'type' => $log->type instanceof \BackedEnum ? $log->type->value : $log->type,
                        'amount' => (float) $log->amount,
                        'description' => $log->description,
                        'created_at' => $log->created_at,
                    ];
                });
            }),
        ];
    }
}
