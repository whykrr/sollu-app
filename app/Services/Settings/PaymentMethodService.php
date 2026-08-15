<?php

namespace App\Services\Settings;

use App\Models\Master\OutletPaymentMethod;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Services\Master\AuditLogService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentMethodService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function getPaginated(string $businessId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PaymentMethod::where('business_id', $businessId)
            ->with(['outlets' => function ($q) {
                $q->select('outlets.id', 'outlets.name');
            }])
            ->withCount('transactionPayments');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'ilike', "%{$search}%");
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function getForInternalApi(string $businessId, ?string $outletId = null): Collection
    {
        $query = PaymentMethod::where('business_id', $businessId);

        if (! empty($outletId)) {
            $query->activeForOutlet($outletId);
        }

        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc')->get();
    }

    public function create(array $data, string $businessId): PaymentMethod
    {
        return DB::transaction(function () use ($data, $businessId) {
            $maxSort = PaymentMethod::where('business_id', $businessId)->max('sort_order') ?? 0;
            $paymentMethod = PaymentMethod::create([
                'business_id' => $businessId,
                'name' => $data['name'],
                'type' => $data['type'],
                'sort_order' => $maxSort + 1,
            ]);

            // All outlets in business
            $businessOutlets = Outlet::where('business_id', $businessId)->pluck('id')->toArray();
            $selectedOutletIds = $data['outlet_ids'] ?? $businessOutlets;

            $syncData = [];
            foreach ($businessOutlets as $outletId) {
                $syncData[$outletId] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'is_enabled' => in_array($outletId, $selectedOutletIds),
                ];
            }

            if (! empty($syncData)) {
                $paymentMethod->outlets()->sync($syncData);
            }

            $this->auditLogService->log(
                $businessId,
                'payment_method',
                $paymentMethod->id,
                'created',
                null,
                $paymentMethod->fresh(['outlets'])->toArray()
            );

            return $paymentMethod;
        });
    }

    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod, $data) {
            $before = $paymentMethod->load('outlets')->toArray();

            $paymentMethod->update([
                'name' => $data['name'] ?? $paymentMethod->name,
                'type' => $data['type'] ?? $paymentMethod->type,
            ]);

            if (array_key_exists('outlet_ids', $data)) {
                $businessOutlets = Outlet::where('business_id', $paymentMethod->business_id)->pluck('id')->toArray();
                $selectedOutletIds = $data['outlet_ids'] ?? [];

                $syncData = [];
                foreach ($businessOutlets as $outletId) {
                    $syncData[$outletId] = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'is_enabled' => in_array($outletId, $selectedOutletIds),
                    ];
                }

                $paymentMethod->outlets()->sync($syncData);
            }

            $this->auditLogService->log(
                $paymentMethod->business_id,
                'payment_method',
                $paymentMethod->id,
                'updated',
                $before,
                $paymentMethod->fresh(['outlets'])->toArray()
            );

            return $paymentMethod;
        });
    }

    public function reorder(array $orderedIds, string $businessId): void
    {
        DB::transaction(function () use ($orderedIds, $businessId) {
            foreach ($orderedIds as $index => $id) {
                PaymentMethod::where('id', $id)
                    ->where('business_id', $businessId)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    public function toggleOutletStatus(PaymentMethod $paymentMethod, string $outletId, ?bool $isEnabled = null): OutletPaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod, $outletId, $isEnabled) {
            $pivot = OutletPaymentMethod::firstOrNew([
                'payment_method_id' => $paymentMethod->id,
                'outlet_id' => $outletId,
            ]);

            $newStatus = $isEnabled !== null ? $isEnabled : (! ($pivot->is_enabled ?? true));
            $pivot->is_enabled = $newStatus;
            $pivot->save();

            $this->auditLogService->log(
                $paymentMethod->business_id,
                'outlet_payment_method',
                $pivot->id,
                'toggle_outlet_status',
                null,
                $pivot->toArray()
            );

            return $pivot;
        });
    }

    public function delete(PaymentMethod $paymentMethod): bool
    {
        return DB::transaction(function () use ($paymentMethod) {
            if ($paymentMethod->transactionPayments()->exists()) {
                throw new Exception('Metode pembayaran ini tidak dapat dihapus karena telah digunakan dalam riwayat transaksi. Anda dapat menonaktifkan statusnya.');
            }

            $before = $paymentMethod->load('outlets')->toArray();
            $paymentMethod->outlets()->detach();
            $paymentMethod->delete();

            $this->auditLogService->log(
                $paymentMethod->business_id,
                'payment_method',
                $paymentMethod->id,
                'deleted',
                $before,
                null
            );

            return true;
        });
    }
}
