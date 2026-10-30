<?php

namespace App\Services\App\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\StockAdjustment;
use App\Models\Inventory\StockOpname;
use App\Models\Inventory\StockTransfer;
use App\Models\User;

class InventorySodService
{
    /**
     * Default konfigurasi SoD inventori yang aman dan fleksibel.
     *
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return [
            'enabled' => false,
            'allow_owner_bypass' => true,
            'rules' => [
                'stock_adjustment' => true,
                'stock_opname' => true,
                'stock_transfer_approval' => true,
                'stock_transfer_receive' => true,
                'purchase_order_receive' => false,
                'direct_purchase_allowed' => true,
            ],
        ];
    }

    /**
     * Cek apakah alur validasi supervisi aktif (SSOT).
     * Syarat: Bisnis memiliki fitur plan DOUBLE_AUTHORIZATION DAN toggle merchant = true.
     * Default dan fallback jika null adalah false.
     */
    public function isSupervisionEnabled(?Business $business): bool
    {
        if (! $business) {
            return false;
        }

        $hasDoubleAuth = $business->hasFeature(FeatureEnum::DOUBLE_AUTHORIZATION)
            || in_array(FeatureEnum::DOUBLE_AUTHORIZATION, $business->getAvailablePlanFeatures(), true);

        if (! $hasDoubleAuth) {
            return false;
        }

        $raw = $business->settings['inventory_sod'] ?? $business->settings['inventory_supervision'] ?? null;
        if ($raw === null) {
            return false;
        }

        return (bool) ($raw['enabled'] ?? false);
    }

    /**
     * Dapatkan konfigurasi SoD aktif untuk merchant.
     *
     * @return array<string, mixed>
     */
    public function getSettings(?Business $business): array
    {
        if (! $business) {
            return $this->defaultSettings();
        }

        $raw = $business->settings['inventory_sod'] ?? $business->settings['inventory_supervision'] ?? [];
        $defaults = $this->defaultSettings();
        $isSupervision = $this->isSupervisionEnabled($business);

        return [
            'enabled' => $isSupervision,
            'allow_owner_bypass' => (bool) ($raw['allow_owner_bypass'] ?? $defaults['allow_owner_bypass']),
            'rules' => array_merge($defaults['rules'], (array) ($raw['rules'] ?? [])),
        ];
    }

    /**
     * Cek apakah SoD aktif secara global untuk merchant (alias untuk isSupervisionEnabled).
     */
    public function isSodEnabled(?Business $business): bool
    {
        return $this->isSupervisionEnabled($business);
    }

    /**
     * Cek apakah pemilik usaha (Owner / business.*) diizinkan bypass SoD.
     */
    public function allowsOwnerBypass(?Business $business): bool
    {
        return $this->getSettings($business)['allow_owner_bypass'];
    }

    /**
     * Cek apakah aturan SoD spesifik aktif.
     */
    public function isRuleEnabled(?Business $business, string $ruleKey): bool
    {
        if (! $business || ! $business->hasFeature(FeatureEnum::DOUBLE_AUTHORIZATION)) {
            return false;
        }

        $settings = $this->getSettings($business);

        if (! $settings['enabled']) {
            return false;
        }

        return (bool) ($settings['rules'][$ruleKey] ?? false);
    }

    /**
     * Penegakan izin validasi/supervisi pengguna.
     */
    public function assertUserHasValidationPermission(User $user, PermissionEnum $permission): void
    {
        if (! $user->can($permission->value)) {
            abort(403, "Kamu tidak memiliki wewenang supervisi untuk memvalidasi transaksi ini: {$permission->value}");
        }
    }

    /**
     * Evaluasi apakah pengguna memiliki hak bypass pemilik.
     */
    protected function canBypass(?Business $business, User $user): bool
    {
        if (! $this->allowsOwnerBypass($business)) {
            return false;
        }

        return (bool) (
            $user->is_root_user
            || (method_exists($user, 'isOwner') && $user->isOwner())
            || (method_exists($user, 'hasRole') && $user->hasRole('Owner'))
            || $user->can('business.*')
            || ($business && $business->email === $user->email)
            || ($business && $business->owner_name === $user->name)
        );
    }

    /**
     * Validasi apakah pengguna diizinkan menyetujui penyesuaian stok.
     *
     * @throws \Exception
     */
    public function assertCanApproveAdjustment(StockAdjustment $adjustment, User $user): void
    {
        $business = $adjustment->business ?? $adjustment->outlet?->business ?? $user->business;

        if (! $this->isRuleEnabled($business, 'stock_adjustment')) {
            return;
        }

        if ($this->canBypass($business, $user)) {
            return;
        }

        if ($adjustment->created_by === $user->id) {
            throw new \Exception('Pemisahan tugas aktif: Kamu tidak dapat menyetujui draf penyesuaian stok yang kamu buat sendiri. Silakan minta rekan kerja atau supervisormu untuk memeriksa dan menyetujuinya.');
        }
    }

    /**
     * Validasi apakah pengguna diizinkan menyetujui/memfinalisasi stock opname.
     *
     * @throws \Exception
     */
    public function assertCanApproveOpname(StockOpname $opname, User $user): void
    {
        $business = $opname->business ?? $opname->outlet?->business ?? $user->business;

        if (! $this->isRuleEnabled($business, 'stock_opname')) {
            return;
        }

        if ($this->canBypass($business, $user)) {
            return;
        }

        if ($opname->created_by === $user->id) {
            throw new \Exception('Pemisahan tugas aktif: Kamu tidak dapat menyetujui rekonsiliasi stock opname yang kamu catat sendiri. Silakan minta supervisor atau staf berwenang untuk memvalidasinya.');
        }
    }

    /**
     * Validasi apakah pengguna diizinkan menyetujui transfer stok.
     *
     * @throws \Exception
     */
    public function assertCanApproveTransfer(StockTransfer $transfer, User $user): void
    {
        $business = $transfer->business ?? $transfer->fromOutlet?->business ?? $user->business;

        if (! $this->isRuleEnabled($business, 'stock_transfer_approval')) {
            return;
        }

        if ($this->canBypass($business, $user)) {
            return;
        }

        if ($transfer->requested_by === $user->id) {
            throw new \Exception('Pemisahan tugas aktif: Kamu tidak dapat menyetujui permintaan transfer stok yang kamu ajukan sendiri.');
        }
    }

    /**
     * Validasi apakah pengguna diizinkan menerima barang transfer di outlet tujuan.
     *
     * @throws \Exception
     */
    public function assertCanReceiveTransfer(StockTransfer $transfer, User $user): void
    {
        $business = $transfer->business ?? $transfer->toOutlet?->business ?? $user->business;

        if (! $this->isRuleEnabled($business, 'stock_transfer_receive')) {
            return;
        }

        if ($this->canBypass($business, $user)) {
            return;
        }

        if ($transfer->requested_by === $user->id) {
            throw new \Exception('Pemisahan tugas aktif: Staf pengirim/pengaju transfer dilarang merangkap sebagai penerima transfer di outlet tujuan.');
        }
    }

    /**
     * Validasi apakah pengguna diizinkan memproses Purchase Order dari Draft menjadi Ordered.
     *
     * @throws \Exception
     */
    public function assertCanOrderPurchaseOrder(PurchaseOrder $po, User $user): void
    {
        $business = $po->business ?? $po->outlet?->business ?? $user->business;

        if (! $this->isRuleEnabled($business, 'purchase_order_approval')) {
            return;
        }

        if ($this->canBypass($business, $user)) {
            return;
        }

        if ($po->created_by === $user->id) {
            throw new \Exception('Pemisahan tugas aktif: Kamu tidak dapat memvalidasi Purchase Order yang kamu buat sendiri.');
        }
    }

    /**
     * Validasi apakah pengguna diizinkan mencatat penerimaan fisik barang dari PO.
     *
     * @throws \Exception
     */
    public function assertCanReceivePurchaseOrder(PurchaseOrder $po, User $user, bool $isDirectPurchase = false): void
    {
        if ($isDirectPurchase) {
            return;
        }

        $business = $po->business ?? $po->outlet?->business ?? $user->business;

        if (! $this->isRuleEnabled($business, 'purchase_order_receive')) {
            return;
        }

        if ($this->canBypass($business, $user)) {
            return;
        }

        if ($po->created_by === $user->id) {
            throw new \Exception('Pemisahan tugas aktif: Pembuat pesanan pembelian (PO) tidak dapat mencatat penerimaan fisik surat jalan untuk pesanan ini.');
        }
    }
}
