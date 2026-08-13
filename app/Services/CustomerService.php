<?php

namespace App\Services;

use App\Models\Master\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function __construct(protected ActivityLogService $activityLogService) {}

    /**
     * Get paginated list of customers with optional filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Customer::withCount('transactions');

        // Apply search filter (name, phone, email)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Add ordering, can be extended later
        if (isset($filters['sort'])) {
            $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc');
        } else {
            $query->orderBy('name');
        }

        return $query->paginate($perPage)->appends($filters);
    }

    /**
     * Get summary statistics for a given customer.
     */
    public function getSummaryStats(Customer $customer): array
    {
        $transactions = $customer->transactions();
        $totalTransactions = $transactions->count();
        $totalBelanja = $transactions->sum('total_amount');
        $rataRata = $totalTransactions > 0 ? $totalBelanja / $totalTransactions : 0;
        $kunjunganTerakhir = $transactions->orderBy('created_at', 'desc')->first()?->created_at;

        $recentTransactions = $transactions->orderBy('created_at', 'desc')->limit(10)->get([
            'id', 'invoice_number', 'created_at as date', 'outlet_id', 'total as grand_total',
        ])->map(function ($trx) {
            return [
                'id' => $trx->id,
                'invoice_number' => $trx->invoice_number,
                'date' => $trx->date->format('d M Y'),
                'outlet_name' => $trx->outlet?->name ?? '-',
                'grand_total' => $trx->grand_total,
            ];
        });

        return [
            'total_transactions' => $totalTransactions,
            'total_spent' => (float) $totalBelanja,
            'average_spent' => (float) $rataRata,
            'last_visit' => $kunjunganTerakhir ? $kunjunganTerakhir->toDateString() : null,
            'recent_transactions' => $recentTransactions,
        ];
    }

    /**
     * Store a new customer.
     */
    public function create(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            $customer = Customer::create($data);
            $this->activityLogService->log($customer, 'created', auth()->user());

            return $customer;
        });
    }

    /**
     * Update an existing customer.
     */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update($data);
            $this->activityLogService->log($customer, 'updated', auth()->user());

            return $customer;
        });
    }

    /**
     * Delete (soft) a customer.
     * If the customer has no transactions, we can perform a hard delete.
     * Otherwise, we deactivate the record.
     */
    public function delete(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            if ($customer->transactions()->exists()) {
                $customer->update(['is_active' => false]);
                $this->activityLogService->log($customer, 'deactivated (soft delete via update)', auth()->user());
            } else {
                $customer->delete();
                $this->activityLogService->log($customer, 'deleted', auth()->user());
            }
        });
    }

    /**
     * Search active customers for POS usage.
     */
    public function searchActive(string $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::where('is_active', true)
            ->where(function (Builder $q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'phone']);
    }
}
