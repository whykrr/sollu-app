<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'outlet_id' => $this->outlet_id,
            'customer_id' => $this->customer_id,
            'transaction_number' => $this->transaction_number,
            'channel' => $this->channel,
            'transaction_date' => $this->transaction_date,
            'due_date' => $this->invoice ? $this->invoice->due_date : null,
            'payment_term' => $this->invoice ? $this->invoice->payment_term : 'tunai',
            'invoice_number' => $this->invoice ? $this->invoice->invoice_number : null,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'shipping_fee' => (float) $this->shipping_fee,
            'tax_amount' => (float) $this->tax_amount,
            'service_charge_amount' => (float) $this->service_charge_amount,
            'discount_amount' => (float) $this->discount_amount,
            'promo_code' => $this->promo_code,
            'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount,
            'notes' => $this->invoice ? $this->invoice->notes : $this->notes,
            'terms_and_conditions' => $this->invoice ? $this->invoice->terms_and_conditions : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'outlet' => $this->whenLoaded('outlet', function () {
                return [
                    'id' => $this->outlet->id,
                    'name' => $this->outlet->name,
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone,
                    'email' => $this->customer->email,
                ];
            }),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'price' => (float) $item->price,
                        'qty' => (float) $item->qty,
                        'qty_formatted' => $item->qty_formatted ?? $item->qty,
                        'discount_amount' => (float) $item->discount_amount,
                        'subtotal' => (float) $item->subtotal,
                    ];
                });
            }),
            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_method_id' => $payment->payment_method_id,
                        'amount' => (float) $payment->amount,
                        'notes' => $payment->notes,
                        'created_at' => $payment->created_at,
                        'payment_method' => $payment->whenLoaded('paymentMethod', function () use ($payment) {
                            return [
                                'id' => $payment->paymentMethod->id,
                                'name' => $payment->paymentMethod->name,
                            ];
                        }),
                    ];
                });
            }),
        ];
    }
}
