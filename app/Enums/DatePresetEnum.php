<?php

namespace App\Enums;

use Carbon\Carbon;

enum DatePresetEnum: string
{
    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_30_DAYS = 'last_30_days';
    case THIS_MONTH = 'this_month';
    case LAST_MONTH = 'last_month';
    case THIS_YEAR = 'this_year';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::TODAY => 'Hari Ini',
            self::YESTERDAY => 'Kemarin',
            self::LAST_7_DAYS => '7 Hari Terakhir',
            self::LAST_30_DAYS => '30 Hari Terakhir',
            self::THIS_MONTH => 'Bulan Ini',
            self::LAST_MONTH => 'Bulan Lalu',
            self::THIS_YEAR => 'Tahun Ini',
            self::CUSTOM => 'Kustom',
        };
    }

    /**
     * Hitung rentang tanggal [start_date, end_date] format Y-m-d.
     *
     * @return array{start_date: ?string, end_date: ?string}
     */
    public function dateRange(): array
    {
        return match ($this) {
            self::TODAY => [
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
            ],
            self::YESTERDAY => [
                'start_date' => Carbon::yesterday()->toDateString(),
                'end_date' => Carbon::yesterday()->toDateString(),
            ],
            self::LAST_7_DAYS => [
                'start_date' => Carbon::today()->subDays(6)->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
            ],
            self::LAST_30_DAYS => [
                'start_date' => Carbon::today()->subDays(29)->toDateString(),
                'end_date' => Carbon::today()->toDateString(),
            ],
            self::THIS_MONTH => [
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->endOfMonth()->toDateString(),
            ],
            self::LAST_MONTH => [
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->subMonth()->endOfMonth()->toDateString(),
            ],
            self::THIS_YEAR => [
                'start_date' => Carbon::now()->startOfYear()->toDateString(),
                'end_date' => Carbon::now()->endOfYear()->toDateString(),
            ],
            self::CUSTOM => [
                'start_date' => null,
                'end_date' => null,
            ],
        };
    }

    /**
     * Helper resolusi tanggal dari preset atau custom start/end date.
     * Default fallback ke 'this_month'.
     *
     * @return array{preset: string, start_date: ?string, end_date: ?string}
     */
    public static function resolveRange(?string $preset = null, ?string $startDate = null, ?string $endDate = null, string $defaultPreset = 'this_month'): array
    {
        $activePreset = $preset ?: ($startDate || $endDate ? self::CUSTOM->value : $defaultPreset);
        $enumCase = self::tryFrom($activePreset);

        if ($enumCase && $enumCase !== self::CUSTOM) {
            $range = $enumCase->dateRange();

            return [
                'preset' => $enumCase->value,
                'start_date' => $range['start_date'],
                'end_date' => $range['end_date'],
            ];
        }

        return [
            'preset' => self::CUSTOM->value,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
