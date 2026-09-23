<?php

namespace App\Http\Controllers\App\Settings;

use App\Enums\AuditModuleEnum;
use App\Enums\DatePresetEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\GetActivityLogRequest;
use App\Models\Audit\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    /**
     * Menampilkan daftar riwayat log aktivitas dan jejak audit.
     */
    public function index(GetActivityLogRequest $request): Response
    {
        $this->authorize(PermissionEnum::SETTING_AUDIT->value);

        $business = Auth::user()->business;
        $dateRange = DatePresetEnum::resolveRange(
            $request->get('preset'),
            $request->get('date_from'),
            $request->get('date_to'),
            DatePresetEnum::THIS_MONTH->value
        );

        $query = ActivityLog::query()
            ->where('business_id', $business->id);

        if (! empty($dateRange['start_date'])) {
            $query->where('created_at', '>=', Carbon::parse($dateRange['start_date'])->startOfDay());
        }

        if (! empty($dateRange['end_date'])) {
            $query->where('created_at', '<=', Carbon::parse($dateRange['end_date'])->endOfDay());
        }

        if ($request->filled('module')) {
            $query->where('module', $request->get('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->get('action').'%');
        }

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->get('outlet_id'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->get('causer_id'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        // Widget KPI ringkasan aktivitas dalam rentang tanggal aktif
        $statsQuery = clone $query;
        $totalActivities = (clone $statsQuery)->count();

        $sensitiveActionsCount = (clone $statsQuery)
            ->where(function ($q) {
                $q->where('action', 'like', '%void%')
                    ->orWhere('action', 'like', '%delete%')
                    ->orWhere('action', 'like', '%refund%')
                    ->orWhere('action', 'like', '%price%')
                    ->orWhere('action', 'like', '%freeze%');
            })
            ->count();

        // Anti over-fetching: select kolom metadata esensial untuk tabel
        $logs = $query
            ->select([
                'id',
                'business_id',
                'outlet_id',
                'causer_type',
                'causer_id',
                'module',
                'action',
                'description',
                'ip_address',
                'user_agent',
                'created_at',
            ])
            ->with([
                'causer' => function ($morphTo) {
                    $morphTo->morphWith([
                        \App\Models\User::class => ['business'],
                    ]);
                },
                'outlet:id,name',
            ])
            ->sortable($request->get('sort', 'created_at'), $request->get('direction', 'desc'))
            ->paginate((int) $request->get('per_page', 20))
            ->withQueryString();

        $outlets = $business->outlets()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Settings/ActivityLog/Index', [
            'logs' => $logs,
            'filters' => array_merge($request->validated(), [
                'preset' => $dateRange['preset'],
                'date_from' => $dateRange['start_date'],
                'date_to' => $dateRange['end_date'],
            ]),
            'outlets' => $outlets,
            'modules' => AuditModuleEnum::options(),
            'stats' => [
                'total_activities' => $totalActivities,
                'sensitive_actions_count' => $sensitiveActionsCount,
            ],
        ]);
    }

    /**
     * Mengambil detail log audit lengkap beserta payload perbandingan (diff) secara on-demand.
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        $this->authorize(PermissionEnum::SETTING_AUDIT->value);

        if ($activityLog->business_id !== Auth::user()->business_id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $activityLog->load([
            'causer',
            'outlet:id,name',
            'subject',
        ]);

        return response()->json([
            'data' => $activityLog,
        ]);
    }
}
