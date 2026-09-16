<?php

namespace App\Services\Cockpit;

use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionInvoice\Status as InvoiceStatusEnum;
use App\Models\Invoice;
use App\Models\PaymentManualValidation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SubscriptionInvoiceService
{
    /**
     * Get paginated invoices with search, filters, and sorting.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedInvoices(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with([
                'business:id,name,email',
                'paymentManualValidation:id,invoice_id,validation_status',
            ]);

        if (! empty($filters['open_invoice'])) {
            $query->where('invoice_number', $filters['open_invoice']);
        } elseif (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('business', function ($b) use ($search) {
                        $b->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            $status = (string) $filters['status'];
            if (in_array($status, ['pending', 'pending_review', 'pending review'], true)) {
                $query->whereHas('paymentManualValidation', function ($q) {
                    $q->where('validation_status', PaymentManualValidationStatus::Pending->value);
                });
            } elseif ($status === 'rejected') {
                $query->whereHas('paymentManualValidation', function ($q) {
                    $q->where('validation_status', PaymentManualValidationStatus::Rejected->value);
                });
            } elseif ($status === 'paid') {
                $query->where('status', InvoiceStatusEnum::Paid->value);
            } elseif ($status === 'unpaid' || $status === 'open') {
                $query->whereIn('status', [InvoiceStatusEnum::Unpaid->value, InvoiceStatusEnum::Open->value])
                    ->where(function ($q) {
                        $q->whereDoesntHave('paymentManualValidation')
                            ->orWhereHas('paymentManualValidation', function ($sub) {
                                $sub->whereNotIn('validation_status', [
                                    PaymentManualValidationStatus::Pending->value,
                                    PaymentManualValidationStatus::Rejected->value,
                                ]);
                            });
                    });
            } else {
                $query->where('status', $status);
            }
        }

        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = (string) ($filters['direction'] ?? 'desc');

        return $query
            ->sortable($sort, $direction)
            ->paginate($perPage)
            ->withQueryString()
            ->through(function (Invoice $invoice) {
                $statusKey = $invoice->status?->value ?? (string) $invoice->status;
                $statusLabel = $invoice->status?->label() ?? ucfirst((string) $invoice->status);
                $statusColor = $invoice->status?->color() ?? 'badge-neutral';

                if ($invoice->paymentManualValidation) {
                    $valStatus = $invoice->paymentManualValidation->validation_status;
                    if ($valStatus === PaymentManualValidationStatus::Pending) {
                        $statusKey = 'pending_review';
                        $statusLabel = 'Menunggu Verifikasi';
                        $statusColor = 'badge-warning';
                    } elseif ($valStatus === PaymentManualValidationStatus::Rejected) {
                        $statusKey = 'rejected';
                        $statusLabel = 'Ditolak';
                        $statusColor = 'badge-danger';
                    } elseif ($valStatus === PaymentManualValidationStatus::Approved) {
                        $statusKey = 'paid';
                        $statusLabel = 'Lunas';
                        $statusColor = 'badge-success';
                    }
                }

                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'date' => $invoice->created_at?->format('d M Y H:i') ?? '-',
                    'created_at' => $invoice->created_at?->toISOString(),
                    'merchant' => $invoice->business->name ?? '-',
                    'amount' => 'Rp '.number_format((float) $invoice->total_amount, 0, ',', '.'),
                    'total_amount' => (float) $invoice->total_amount,
                    'status' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_color' => $statusColor,
                    'raw_status' => $invoice->status?->value ?? (string) $invoice->status,
                ];
            });
    }

    /**
     * Get aggregate KPI metrics for Cockpit Invoice page.
     *
     * @return array<string, int|float>
     */
    public function getMetrics(): array
    {
        $totalInvoices = Invoice::query()->count();

        $pendingValidation = PaymentManualValidation::query()
            ->where('validation_status', PaymentManualValidationStatus::Pending->value)
            ->count();

        $paidInvoices = Invoice::query()
            ->where('status', InvoiceStatusEnum::Paid->value)
            ->count();

        $rejectedInvoices = PaymentManualValidation::query()
            ->where('validation_status', PaymentManualValidationStatus::Rejected->value)
            ->count();

        return [
            'total_invoices' => $totalInvoices,
            'pending_validation' => $pendingValidation,
            'paid_invoices' => $paidInvoices,
            'rejected_invoices' => $rejectedInvoices,
        ];
    }

    /**
     * Get complete invoice details on-demand for Cockpit drawer.
     *
     * @return array<string, mixed>
     */
    public function getInvoiceDetail(Invoice|string $invoice): array
    {
        if (is_string($invoice)) {
            $invoice = Invoice::query()->findOrFail($invoice);
        }

        $invoice->loadMissing([
            'business:id,name,owner_name,email,phone,address',
            'items',
            'payments',
            'paymentManualValidation',
        ]);

        $outletNames = $invoice->items->map(function ($item) {
            return $item->metadata['outlet_name'] ?? null;
        })->filter()->unique()->implode(', ');

        if (empty($outletNames)) {
            $activeOutlets = $invoice->items->map(function ($item) {
                return $item->metadata['active_outlets'] ?? null;
            })->filter()->first();
            $outletNames = $activeOutlets ? $activeOutlets.' Outlets' : '-';
        }

        $statusKey = $invoice->status?->value ?? (string) $invoice->status;
        $statusLabel = $invoice->status?->label() ?? ucfirst((string) $invoice->status);
        $statusColor = $invoice->status?->color() ?? 'badge-neutral';

        if ($invoice->paymentManualValidation) {
            $valStatus = $invoice->paymentManualValidation->validation_status;
            if ($valStatus === PaymentManualValidationStatus::Pending) {
                $statusKey = 'pending_review';
                $statusLabel = 'Menunggu Verifikasi';
                $statusColor = 'badge-warning';
            } elseif ($valStatus === PaymentManualValidationStatus::Rejected) {
                $statusKey = 'rejected';
                $statusLabel = 'Ditolak';
                $statusColor = 'badge-danger';
            } elseif ($valStatus === PaymentManualValidationStatus::Approved) {
                $statusKey = 'paid';
                $statusLabel = 'Lunas';
                $statusColor = 'badge-success';
            }
        }

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'date' => $invoice->created_at?->format('d M Y H:i') ?? '-',
            'due_date' => $invoice->due_date?->format('d M Y H:i') ?? '-',
            'paid_at' => $invoice->paid_at?->format('d M Y H:i'),
            'merchant' => $invoice->business->name ?? '-',
            'merchant_detail' => [
                'owner_name' => $invoice->business->owner_name ?? '-',
                'email' => $invoice->business->email ?? '-',
                'phone' => $invoice->business->phone ?? '-',
                'address' => $invoice->business->address ?? '-',
            ],
            'outlet_name' => $outletNames,
            'subtotal' => (float) $invoice->subtotal,
            'tax_amount' => (float) $invoice->tax_amount,
            'total_amount' => (float) $invoice->total_amount,
            'amount_formatted' => 'Rp '.number_format((float) $invoice->total_amount, 0, ',', '.'),
            'status' => $statusKey,
            'status_label' => $statusLabel,
            'status_color' => $statusColor,
            'raw_status' => $invoice->status?->value ?? (string) $invoice->status,
            'items' => $invoice->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'metadata' => $item->metadata,
                ];
            }),
            'payments' => $invoice->payments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'payment_method' => $p->payment_method,
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                    'paid_at' => $p->paid_at?->format('d M Y H:i'),
                ];
            }),
            'payment_manual_validation' => $invoice->paymentManualValidation ? [
                'id' => $invoice->paymentManualValidation->id,
                'validation_status' => $invoice->paymentManualValidation->validation_status?->value ?? (string) $invoice->paymentManualValidation->validation_status,
                'rejection_reason' => $invoice->paymentManualValidation->rejection_reason,
                'proof_url' => $invoice->paymentManualValidation->payment_proof_full_url,
                'reviewed_at' => $invoice->paymentManualValidation->reviewed_at?->format('d M Y H:i'),
            ] : null,
            'proof_url' => $invoice->paymentManualValidation?->payment_proof_full_url,
        ];
    }
}
