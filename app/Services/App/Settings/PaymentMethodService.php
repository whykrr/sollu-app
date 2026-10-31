<?php

namespace App\Services\App\Settings;

use App\Models\Master\OutletPaymentMethod;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Services\App\Master\AuditLogService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        return $query->orderedByIndustryStandard()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getForInternalApi(string $businessId, ?string $outletId = null): Collection
    {
        $query = PaymentMethod::where('business_id', $businessId);

        if (! empty($outletId)) {
            $query->activeForOutlet($outletId);
        }

        return $query->orderedByIndustryStandard()->get();
    }

    public function create(array $data, string $businessId): PaymentMethod
    {
        return DB::transaction(function () use ($data, $businessId) {
            $qrImagePath = null;
            if (isset($data['qr_image']) && $data['qr_image'] instanceof UploadedFile) {
                $qrImagePath = $data['qr_image']->store('payment_methods/qr_codes', 'public');
            }

            $paymentMethod = PaymentMethod::create([
                'business_id' => $businessId,
                'name' => $data['name'],
                'type' => $data['type'],
                'provider' => $data['provider'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'reference_policy' => $data['reference_policy'] ?? 'optional',
                'open_cash_drawer' => (bool) ($data['open_cash_drawer'] ?? false),
                'instructions' => $data['instructions'] ?? null,
                'qr_image_path' => $qrImagePath,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_system' => false,
            ]);

            // All outlets in business
            $businessOutlets = Outlet::where('business_id', $businessId)->pluck('id')->toArray();
            $selectedOutletIds = $data['outlet_ids'] ?? $businessOutlets;

            $syncData = [];
            foreach ($businessOutlets as $outletId) {
                $syncData[$outletId] = [
                    'id' => (string) Str::uuid(),
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
            if ($paymentMethod->is_system && isset($data['type']) && $data['type'] !== $paymentMethod->type) {
                throw new Exception('Tipe metode pembayaran sistem tidak dapat diubah.');
            }

            $before = $paymentMethod->load('outlets')->toArray();

            $updateData = [
                'name' => $data['name'] ?? $paymentMethod->name,
                'provider' => array_key_exists('provider', $data) ? $data['provider'] : $paymentMethod->provider,
                'account_number' => array_key_exists('account_number', $data) ? $data['account_number'] : $paymentMethod->account_number,
                'account_name' => array_key_exists('account_name', $data) ? $data['account_name'] : $paymentMethod->account_name,
                'reference_policy' => $data['reference_policy'] ?? $paymentMethod->reference_policy,
                'open_cash_drawer' => array_key_exists('open_cash_drawer', $data)
                    ? (bool) $data['open_cash_drawer']
                    : $paymentMethod->open_cash_drawer,
                'instructions' => array_key_exists('instructions', $data) ? $data['instructions'] : $paymentMethod->instructions,
            ];

            if (! $paymentMethod->is_system && isset($data['type'])) {
                $updateData['type'] = $data['type'];
            }

            if (array_key_exists('is_active', $data)) {
                $updateData['is_active'] = (bool) $data['is_active'];
            }

            if (isset($data['qr_image']) && $data['qr_image'] instanceof UploadedFile) {
                if ($paymentMethod->qr_image_path) {
                    Storage::disk('public')->delete($paymentMethod->qr_image_path);
                }
                $updateData['qr_image_path'] = $data['qr_image']->store('payment_methods/qr_codes', 'public');
            } elseif (! empty($data['remove_qr_image'])) {
                if ($paymentMethod->qr_image_path) {
                    Storage::disk('public')->delete($paymentMethod->qr_image_path);
                }
                $updateData['qr_image_path'] = null;
            }

            $paymentMethod->update($updateData);

            if (array_key_exists('outlet_ids', $data)) {
                $businessOutlets = Outlet::where('business_id', $paymentMethod->business_id)->pluck('id')->toArray();
                $selectedOutletIds = $data['outlet_ids'] ?? [];

                $syncData = [];
                foreach ($businessOutlets as $outletId) {
                    $syncData[$outletId] = [
                        'id' => (string) Str::uuid(),
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

    public function toggleActive(PaymentMethod $paymentMethod): PaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod) {
            $before = $paymentMethod->toArray();
            $paymentMethod->is_active = ! $paymentMethod->is_active;
            $paymentMethod->save();

            $this->auditLogService->log(
                $paymentMethod->business_id,
                'payment_method',
                $paymentMethod->id,
                'toggle_active',
                $before,
                $paymentMethod->fresh()->toArray()
            );

            return $paymentMethod;
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
            if ($paymentMethod->is_system) {
                throw new Exception('Metode pembayaran sistem tidak dapat dihapus.');
            }

            if ($paymentMethod->transactionPayments()->exists()) {
                throw new Exception('Metode pembayaran ini tidak dapat dihapus karena telah digunakan dalam riwayat transaksi. Anda dapat menonaktifkan statusnya.');
            }

            if ($paymentMethod->qr_image_path) {
                Storage::disk('public')->delete($paymentMethod->qr_image_path);
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
