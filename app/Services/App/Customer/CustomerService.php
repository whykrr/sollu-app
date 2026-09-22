<?php

namespace App\Services\App\Customer;

use App\Models\Master\Customer;
use App\Services\App\Master\ActivityLogService;
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
        $query = Customer::currentBusiness()
            ->withCount('transactions');

        // Apply search filter (name, phone, email)
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('phone', "%{$search}%")
                    ->orWhereLike('email', "%{$search}%");
            });
        }

        // Filter by active status
        if (isset($filters['is_active']) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->sortable($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate($perPage)
            ->appends($filters);
    }

    /**
     * Get summary statistics for a given customer.
     */
    public function getSummaryStats(Customer $customer): array
    {
        $transactionsQuery = $customer->transactions();
        $totalTransactions = $transactionsQuery->count();
        $totalBelanja = $transactionsQuery->sum('total');
        $rataRata = $totalTransactions > 0 ? $totalBelanja / $totalTransactions : 0;
        $kunjunganTerakhir = $transactionsQuery->orderBy('created_at', 'desc')->first()?->created_at;

        $recentTransactions = $transactionsQuery->with(['outlet', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($trx) {
                return [
                    'id' => $trx->id,
                    'invoice_number' => $trx->invoice?->invoice_number ?? $trx->transaction_number,
                    'date' => $trx->created_at->format('d M Y'),
                    'outlet_name' => $trx->outlet?->name ?? '-',
                    'grand_total' => (float) $trx->total,
                ];
            });

        return [
            'total_transactions' => $totalTransactions,
            'total_spent' => (float) $totalBelanja,
            'average_spent' => (float) $rataRata,
            'last_transaction_date' => $kunjunganTerakhir ? $kunjunganTerakhir->toDateString() : null,
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
        return Customer::currentBusiness()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($query) {
                $q->whereLike('name', "%{$query}%")
                    ->orWhereLike('phone', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'business_id', 'name', 'phone']);
    }
}
