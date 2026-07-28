<?php

namespace App\Models\Traits;

trait HasQuantityFormatter
{
    /**
     * Format a quantity field for display.
     * Rounds to max 2 decimal places and formats using local id-ID standard.
     */
    public function formatQuantity(?float $value): string
    {
        if (is_null($value)) {
            return '0';
        }

        // Round to 2 decimals as requested
        $value = round($value, 2);

        // Format to string using number_format
        $formatted = number_format($value, 2, ',', '.');

        // Drop trailing zeroes and comma if it's a whole number
        if (str_contains($formatted, ',')) {
            $formatted = rtrim(rtrim($formatted, '0'), ',');
        }

        return $formatted;
    }
}
