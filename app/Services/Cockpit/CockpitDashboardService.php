<?php

namespace App\Services\Cockpit;

use App\Enums\BusinessStatus;
use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionInvoice\Status as InvoiceStatusEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\PaymentManualValidation;
use App\Models\Subscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;

class CockpitDashboardService
{
    /**
     * Get complete dashboard overview dataset for Cockpit with caching.
     *
     * @return array<string, mixed>
     */
    public function getDashboardData(string $period = 'this_month'): array
    {
        $cacheKey = "cockpit:dashboard:overview:{$period}";

        return Cache::remember($cacheKey, 60, function () use ($period) {
            [$startDate, $endDate, $prevStartDate, $prevEndDate, $periodLabel] = $this->resolveDateRanges($period);

            return [
                'metrics' => $this->getMetrics($startDate, $endDate, $prevStartDate, $prevEndDate),
                'revenue_trend' => $this->getRevenueTrend($period, $startDate, $endDate),
                'acquisition_trend' => $this->getAcquisitionTrend(),
                'plan_distribution' => $this->getPlanDistribution(),
                'business_type_distribution' => $this->getBusinessTypeDistribution(),
                'pending_invoices' => $this->getPendingInvoices(),
                'recent_merchants' => $this->getRecentMerchants(),
                'period_label' => $periodLabel,
            ];
        });
    }

    /**
     * Calculate core SaaS KPI metrics with percentage growth.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(
        ?Carbon $startDate,
        ?Carbon $endDate,
        ?Carbon $prevStartDate,
        ?Carbon $prevEndDate
    ): array {
        // Current Period Revenue
        $revenueQuery = Invoice::query()->where('status', InvoiceStatusEnum::Paid->value);
        if ($startDate && $endDate) {
            $revenueQuery->whereBetween('paid_at', [$startDate, $endDate]);
        }
        $currentRevenue = (float) $revenueQuery->sum('total_amount');

        // Previous Period Revenue (for % growth)
        $previousRevenue = 0.0;
        if ($prevStartDate && $prevEndDate) {
            $previousRevenue = (float) Invoice::query()
                ->where('status', InvoiceStatusEnum::Paid->value)
                ->whereBetween('paid_at', [$prevStartDate, $prevEndDate])
                ->sum('total_amount');
        }

        $revenueGrowth = 0.0;
        if ($previousRevenue > 0) {
            $revenueGrowth = (float) round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
        }

        // Active Merchants
        $totalMerchants = Business::query()->count();
        $activeMerchants = Business::query()->where('status', BusinessStatus::Active->value)->count();
        $suspendedMerchants = Business::query()->where('status', BusinessStatus::Suspended->value)->count();

        // Active Paying Subscribers
        $activeSubscribers = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            })
            ->distinct('business_id')
            ->count('business_id');

        // Trial Merchants
        $trialMerchants = Business::query()
            ->where('trial_end_at', '>=', now())
            ->count();

        // Total Outlets
        $totalOutlets = Outlet::query()->whereNull('deleted_at')->count();

        // Pending Manual Payment Validations
        $pendingInvoicesCount = PaymentManualValidation::query()
            ->where('validation_status', PaymentManualValidationStatus::Pending->value)
            ->count();

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'revenue_growth_percent' => $revenueGrowth,
            'total_merchants' => $totalMerchants,
            'active_merchants' => $activeMerchants,
            'suspended_merchants' => $suspendedMerchants,
            'active_subscribers' => $activeSubscribers,
            'trial_merchants' => $trialMerchants,
            'total_outlets' => $totalOutlets,
            'pending_invoices_count' => $pendingInvoicesCount,
        ];
    }

    /**
     * Get revenue trend dataset for Line chart.
     *
     * @return array{labels: array<string>, values: array<float>}
     */
    public function getRevenueTrend(string $period, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $isDaily = in_array($period, ['today', 'yesterday', '7_days', 'last_30_days', 'this_month', 'last_month'], true);

        if ($isDaily && $startDate && $endDate) {
            $periodDays = CarbonPeriod::create($startDate, $endDate);
            $labels = [];
            $revenueMap = [];

            foreach ($periodDays as $date) {
                $key = $date->format('Y-m-d');
                $label = $period === 'today' || $period === 'yesterday'
                    ? $date->format('d M')
                    : $date->format('d M');
                $labels[$key] = $label;
                $revenueMap[$key] = 0.0;
            }

            $invoices = Invoice::query()
                ->where('status', InvoiceStatusEnum::Paid->value)
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->select(['paid_at', 'total_amount'])
                ->get();

            foreach ($invoices as $inv) {
                if ($inv->paid_at) {
                    $key = Carbon::parse($inv->paid_at)->format('Y-m-d');
                    if (isset($revenueMap[$key])) {
                        $revenueMap[$key] += (float) $inv->total_amount;
                    }
                }
            }

            return [
                'labels' => array_values($labels),
                'values' => array_values($revenueMap),
            ];
        }

        // Monthly breakdown for the last 6 months
        $months = [];
        $revenueMap = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $key = $monthDate->format('Y-m');
            $months[$key] = $monthDate->translatedFormat('M Y');
            $revenueMap[$key] = 0.0;
        }

        $sinceDate = now()->subMonths(5)->startOfMonth();
        $invoices = Invoice::query()
            ->where('status', InvoiceStatusEnum::Paid->value)
            ->where('paid_at', '>=', $sinceDate)
            ->select(['paid_at', 'total_amount'])
            ->get();

        foreach ($invoices as $inv) {
            if ($inv->paid_at) {
                $key = Carbon::parse($inv->paid_at)->format('Y-m');
                if (isset($revenueMap[$key])) {
                    $revenueMap[$key] += (float) $inv->total_amount;
                }
            }
        }

        return [
            'labels' => array_values($months),
            'values' => array_values($revenueMap),
        ];
    }

    /**
     * Get merchant and outlet acquisition velocity for the last 6 months.
     *
     * @return array{labels: array<string>, merchants: array<int>, outlets: array<int>}
     */
    public function getAcquisitionTrend(): array
    {
        $months = [];
        $merchantsMap = [];
        $outletsMap = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $key = $monthDate->format('Y-m');
            $months[$key] = $monthDate->translatedFormat('M Y');
            $merchantsMap[$key] = 0;
            $outletsMap[$key] = 0;
        }

        $sinceDate = now()->subMonths(5)->startOfMonth();

        $businesses = Business::query()
            ->where('created_at', '>=', $sinceDate)
            ->select(['created_at'])
            ->get();

        foreach ($businesses as $b) {
            if ($b->created_at) {
                $key = Carbon::parse($b->created_at)->format('Y-m');
                if (isset($merchantsMap[$key])) {
                    $merchantsMap[$key]++;
                }
            }
        }

        $outlets = Outlet::query()
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $sinceDate)
            ->select(['created_at'])
            ->get();

        foreach ($outlets as $o) {
            if ($o->created_at) {
                $key = Carbon::parse($o->created_at)->format('Y-m');
                if (isset($outletsMap[$key])) {
                    $outletsMap[$key]++;
                }
            }
        }

        return [
            'labels' => array_values($months),
            'merchants' => array_values($merchantsMap),
            'outlets' => array_values($outletsMap),
        ];
    }

    /**
     * Get distribution of active subscription plans.
     *
     * @return array{labels: array<string>, values: array<int>}
     */
    public function getPlanDistribution(): array
    {
        $subscriptions = Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            })
            ->with('plan:id,name,code')
            ->select(['id', 'plan_id'])
            ->get();

        $planCounts = [];
        foreach ($subscriptions as $sub) {
            $planName = $sub->plan->name ?? 'Paket Kustom';
            $planCounts[$planName] = ($planCounts[$planName] ?? 0) + 1;
        }

        // Add Trial count
        $trialCount = Business::query()
            ->where('trial_end_at', '>=', now())
            ->whereDoesntHave('subscriptions', function ($q) {
                $q->where('status', SubscriptionStatus::Active->value);
            })
            ->count();

        if ($trialCount > 0) {
            $planCounts['Masa Trial'] = $trialCount;
        }

        if (empty($planCounts)) {
            return [
                'labels' => ['Belum Ada Data'],
                'values' => [0],
            ];
        }

        return [
            'labels' => array_keys($planCounts),
            'values' => array_values($planCounts),
        ];
    }

    /**
     * Get distribution of business types / industries.
     *
     * @return array{labels: array<string>, values: array<int>}
     */
    public function getBusinessTypeDistribution(): array
    {
        $businessTypes = BusinessType::getAllCached();
        $typeNames = $businessTypes->pluck('name', 'id')->all();

        $businesses = Business::query()
            ->select(['id', 'business_type_id'])
            ->get();

        $typeCounts = [];
        foreach ($businesses as $b) {
            $typeName = $typeNames[$b->business_type_id] ?? 'Lainnya';
            $typeCounts[$typeName] = ($typeCounts[$typeName] ?? 0) + 1;
        }

        // Sort by count descending
        arsort($typeCounts);

        // Keep top 5 and group rest as 'Lainnya' if more than 6
        if (count($typeCounts) > 6) {
            $topTypes = array_slice($typeCounts, 0, 5, true);
            $othersCount = array_sum(array_slice($typeCounts, 5, null, true));
            if ($othersCount > 0) {
                $topTypes['Lainnya'] = ($topTypes['Lainnya'] ?? 0) + $othersCount;
            }
            $typeCounts = $topTypes;
        }

        if (empty($typeCounts)) {
            return [
                'labels' => ['Belum Ada Data'],
                'values' => [0],
            ];
        }

        return [
            'labels' => array_keys($typeCounts),
            'values' => array_values($typeCounts),
        ];
    }

    /**
     * Get top 5 pending payment manual validations.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendingInvoices(): array
    {
        return PaymentManualValidation::query()
            ->where('validation_status', PaymentManualValidationStatus::Pending->value)
            ->with([
                'invoice:id,invoice_number,total_amount,business_id,created_at',
                'invoice.business:id,name,owner_name,email',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (PaymentManualValidation $val) {
                return [
                    'id' => $val->id,
                    'invoice_id' => $val->invoice_id,
                    'invoice_number' => $val->invoice?->invoice_number ?? '-',
                    'merchant_name' => $val->invoice?->business?->name ?? '-',
                    'owner_name' => $val->invoice?->business?->owner_name ?? '-',
                    'amount' => (float) ($val->invoice?->total_amount ?? 0),
                    'amount_formatted' => 'Rp '.number_format((float) ($val->invoice?->total_amount ?? 0), 0, ',', '.'),
                    'proof_url' => $val->payment_proof_full_url,
                    'created_at' => $val->created_at?->toISOString(),
                    'created_at_formatted' => $val->created_at?->translatedFormat('d M Y H:i') ?? '-',
                ];
            })
            ->all();
    }

    /**
     * Get top 5 recently registered merchants.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentMerchants(): array
    {
        return Business::query()
            ->select([
                'id',
                'name',
                'owner_name',
                'email',
                'phone',
                'business_type_id',
                'status',
                'trial_end_at',
                'created_at',
            ])
            ->with([
                'type:id,code,name',
                'subscriptions' => function ($q) {
                    $q->where('status', SubscriptionStatus::Active->value)
                        ->latest('created_at')
                        ->with('plan:id,name');
                },
            ])
            ->withCount('outlets')
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function (Business $b) {
                $activeSub = $b->subscriptions->first();
                $planName = $activeSub?->plan?->name;

                $isTrial = $b->trial_end_at && Carbon::parse($b->trial_end_at)->isFuture();

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'owner_name' => $b->owner_name ?? '-',
                    'email' => $b->email,
                    'business_type' => $b->type?->name ?? '-',
                    'outlets_count' => (int) $b->outlets_count,
                    'status' => $b->status instanceof BusinessStatus ? $b->status->value : (string) $b->status,
                    'plan_name' => $planName ?: ($isTrial ? 'Masa Trial' : 'Tanpa Paket'),
                    'is_trial' => $isTrial,
                    'created_at' => $b->created_at?->toISOString(),
                    'created_at_formatted' => $b->created_at?->translatedFormat('d M Y') ?? '-',
                ];
            })
            ->all();
    }

    /**
     * Resolve start and end dates based on period string.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?Carbon, 3: ?Carbon, 4: string}
     */
    protected function resolveDateRanges(string $period): array
    {
        return match ($period) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                'Hari Ini',
            ],
            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                now()->subDays(2)->startOfDay(),
                now()->subDays(2)->endOfDay(),
                'Kemarin',
            ],
            '7_days' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                now()->subDays(13)->startOfDay(),
                now()->subDays(7)->endOfDay(),
                '7 Hari Terakhir',
            ],
            'last_30_days' => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                now()->subDays(59)->startOfDay(),
                now()->subDays(30)->endOfDay(),
                '30 Hari Terakhir',
            ],
            'last_month' => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                now()->subMonths(2)->startOfMonth(),
                now()->subMonths(2)->endOfMonth(),
                'Bulan Lalu',
            ],
            'this_year' => [
                now()->startOfYear(),
                now()->endOfYear(),
                now()->subYear()->startOfYear(),
                now()->subYear()->endOfYear(),
                'Tahun Ini',
            ],
            'all_time' => [
                null,
                null,
                null,
                null,
                'Sepanjang Waktu',
            ],
            default => [
                now()->startOfMonth(),
                now()->endOfMonth(),
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                'Bulan Ini',
            ],
        };
    }
}
