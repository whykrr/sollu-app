<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueProductItemCode implements ValidationRule
{
    /**
     * @param  string  $field  'sku' or 'barcode'
     * @param  array<string>|string|null  $ignoreItemIds
     */
    public function __construct(
        protected string $businessId,
        protected string $field = 'sku',
        protected array|string|null $ignoreItemIds = null,
        protected ?string $ignoreProductId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $otherField = $this->field === 'sku' ? 'barcode' : 'sku';
        $ignoreIds = array_filter((array) ($this->ignoreItemIds ?? []));

        // 1. Check same field uniqueness in product_items
        $sameFieldQuery = DB::table('product_items')
            ->where('business_id', $this->businessId)
            ->whereNull('deleted_at')
            ->where($this->field, $value);

        if (! empty($ignoreIds)) {
            $sameFieldQuery->whereNotIn('id', $ignoreIds);
        }
        if ($this->ignoreProductId) {
            $sameFieldQuery->where(function ($q) {
                $q->whereNull('product_id')->orWhere('product_id', '!=', $this->ignoreProductId);
            });
        }

        if ($sameFieldQuery->exists()) {
            $label = $this->field === 'sku' ? 'Kode SKU' : 'Barcode';
            $fail("{$label} '{$value}' sudah digunakan oleh produk lain.");

            return;
        }

        // 2. Cross-field uniqueness in product_items (SKU cannot equal any Barcode and vice versa)
        $crossFieldQuery = DB::table('product_items')
            ->where('business_id', $this->businessId)
            ->whereNull('deleted_at')
            ->where($otherField, $value);

        if (! empty($ignoreIds)) {
            $crossFieldQuery->whereNotIn('id', $ignoreIds);
        }
        if ($this->ignoreProductId) {
            $crossFieldQuery->where(function ($q) {
                $q->whereNull('product_id')->orWhere('product_id', '!=', $this->ignoreProductId);
            });
        }

        if ($crossFieldQuery->exists()) {
            $currentLabel = $this->field === 'sku' ? 'Kode SKU' : 'Barcode';
            $oppLabel = $otherField === 'sku' ? 'Kode SKU' : 'Barcode';
            $fail("{$currentLabel} '{$value}' tidak boleh sama dengan {$oppLabel} produk lain.");

            return;
        }

        // 3. Also check products.code
        $prodQuery = DB::table('products')
            ->where('business_id', $this->businessId)
            ->whereNull('deleted_at')
            ->where('code', $value);

        if ($this->ignoreProductId) {
            $prodQuery->where('id', '!=', $this->ignoreProductId);
        }

        if ($prodQuery->exists()) {
            $currentLabel = $this->field === 'sku' ? 'Kode SKU' : 'Barcode';
            $fail("{$currentLabel} '{$value}' sudah digunakan oleh produk lain.");
        }
    }
}
