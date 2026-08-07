<?php

namespace App\Http\Requests\Inventory\Transfer;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class ProcessStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::INVENTORY_TRANSFER_RECEIVE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.id'           => ['required', 'uuid', 'exists:stock_transfer_items,id'],
            'items.*.qty_received' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $transfer = $this->route('transfer');
            $itemsData = $this->input('items', []);
            
            if (!$transfer || empty($itemsData)) {
                return;
            }

            $transferItems = $transfer->items()->pluck('qty', 'id');
            
            foreach ($itemsData as $index => $item) {
                $transferItemId = $item['id'] ?? null;
                $qtyReceived = $item['qty_received'] ?? 0;
                
                if ($transferItemId && isset($transferItems[$transferItemId])) {
                    $maxQty = $transferItems[$transferItemId];
                    if ($qtyReceived > $maxQty) {
                        $validator->errors()->add("items.{$index}.qty_received", "Jumlah diterima tidak boleh melebihi jumlah yang dikirim ({$maxQty}).");
                    }
                }
            }
        });
    }
}
