<?php

namespace Tests\Unit;

use App\Enums\DatePresetEnum;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DatePresetEnumTest extends TestCase
{
    public function test_date_preset_labels_and_cases(): void
    {
        $this->assertEquals('Hari Ini', DatePresetEnum::TODAY->label());
        $this->assertEquals('Kemarin', DatePresetEnum::YESTERDAY->label());
        $this->assertEquals('7 Hari Terakhir', DatePresetEnum::LAST_7_DAYS->label());
        $this->assertEquals('30 Hari Terakhir', DatePresetEnum::LAST_30_DAYS->label());
        $this->assertEquals('Bulan Ini', DatePresetEnum::THIS_MONTH->label());
        $this->assertEquals('Bulan Lalu', DatePresetEnum::LAST_MONTH->label());
        $this->assertEquals('Tahun Ini', DatePresetEnum::THIS_YEAR->label());
        $this->assertEquals('Kustom', DatePresetEnum::CUSTOM->label());
    }

    public function test_resolve_range_defaults_to_this_month(): void
    {
        $resolved = DatePresetEnum::resolveRange();

        $this->assertEquals('this_month', $resolved['preset']);
        $this->assertEquals(Carbon::now()->startOfMonth()->toDateString(), $resolved['start_date']);
        $this->assertEquals(Carbon::now()->endOfMonth()->toDateString(), $resolved['end_date']);
    }

    public function test_resolve_range_with_specific_preset(): void
    {
        $resolved = DatePresetEnum::resolveRange('last_7_days');

        $this->assertEquals('last_7_days', $resolved['preset']);
        $this->assertEquals(Carbon::today()->subDays(6)->toDateString(), $resolved['start_date']);
        $this->assertEquals(Carbon::today()->toDateString(), $resolved['end_date']);
    }

    public function test_resolve_range_with_custom_dates(): void
    {
        $resolved = DatePresetEnum::resolveRange('custom', '2026-01-01', '2026-01-15');

        $this->assertEquals('custom', $resolved['preset']);
        $this->assertEquals('2026-01-01', $resolved['start_date']);
        $this->assertEquals('2026-01-15', $resolved['end_date']);
    }
}
