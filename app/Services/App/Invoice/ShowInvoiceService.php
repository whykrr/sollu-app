<?php

namespace App\Services\App\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Master\SubscriptionManualPaymentMethod;
use App\Models\PaymentManualValidation;
use App\Models\SystemSetting;
use App\Services\App\Subscription\MidtransService;
use Exception;
use Illuminate\Support\Str;

class ShowInvoiceService
{
    /**
     * Prepare full details of an invoice for viewing/modal display.
     *
     * @return array{
     *     invoice: Invoice,
     *     payment: ?\App\Models\Payment,
     *     midtransClientKey: ?string,
     *     manualValidation: ?PaymentManualValidation,
     *     manualPaymentMethods: \Illuminate\Database\Eloquent\Collection,
     *     isMidtransEnabled: bool
     * }
     */
    public function getInvoiceDetails(string $invoiceNumber, Business $business): array
    {
        $invoice = Invoice::query()
            ->where('invoice_number', $invoiceNumber)
            ->where('business_id', $business->id)
            ->with(['items', 'business'])
            ->firstOrFail();

        $payment = $invoice->payments()->latest()->first();

        if ($payment && $payment->status === 'failed') {
            $payment = null;
        }

        $isMidtransEnabled = SystemSetting::isMidtransEnabled();

        if (! $payment && ($invoice->status === Status::Open || (is_string($invoice->status) && $invoice->status === 'open')) && $isMidtransEnabled) {
            $midtransRequest = [
                'transaction_details' => [
                    'order_id' => "{$invoice->invoice_number}-".Str::upper(Str::random(4)),
                    'gross_amount' => (int) $invoice->total_amount,
                ],
                'customer_details' => [
                    'first_name' => $business->name,
                    'email' => $business->email,
                    'phone' => $business->phone,
                    'billing_address' => [
                        'address' => $business->address,
                    ],
                ],
                'item_details' => [
                    [
                        'id' => $invoice->id,
                        'price' => (int) $invoice->total_amount,
                        'quantity' => 1,
                        'name' => 'Subscription Billing',
                    ],
                ],
                'expiry' => [
                    'unit' => 'minute',
                    'duration' => 60,
                ],
                'callbacks' => [
                    'finish' => route('settings.billing.invoices.finish', $invoiceNumber),
                    'error' => route('settings.billing.invoices.error', $invoiceNumber),
                ],
            ];

            try {
                if (class_exists(MidtransService::class)) {
                    $midtrans = new MidtransService;
                    $transaction = (array) $midtrans->createTransaction($midtransRequest);
                } else {
                    $transaction = ['token' => 'dummy-token'];
                }

                $payment = $invoice->payments()->create([
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'midtrans',
                    'payment_reference' => $midtransRequest['transaction_details']['order_id'],
                    'status' => 'pending',
                    'json_request' => $midtransRequest,
                    'json_respond' => $transaction,
                ]);
            } catch (Exception $e) {
                // Ignore failure gracefully so page can still load manual transfer option
            }
        }

        $manualValidation = PaymentManualValidation::where('invoice_id', $invoice->id)->first();

        $manualPaymentMethods = SubscriptionManualPaymentMethod::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return [
            'invoice' => $invoice,
            'payment' => $payment,
            'midtransClientKey' => config('midtrans.client_key'),
            'manualValidation' => $manualValidation,
            'manualPaymentMethods' => $manualPaymentMethods,
            'isMidtransEnabled' => $isMidtransEnabled,
        ];
    }
}
