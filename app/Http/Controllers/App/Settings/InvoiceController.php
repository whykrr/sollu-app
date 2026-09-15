<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\SubscriptionInvoice\Status;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentManualValidation;
use App\Models\SystemSetting;
use App\Services\App\Subscription\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function show(Request $req, $invoice_number)
    {
        $business = $req->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)->where('business_id', $business->id)->with(['items', 'business'])->firstOrFail();

        $payment = $invoice->payments()->latest()->first();

        if ($payment && $payment->status === 'failed') {
            $payment = null;
        }

        $isMidtransEnabled = SystemSetting::isMidtransEnabled();

        if (! $payment && ($invoice->status === Status::Open || $invoice->status === 'open') && $isMidtransEnabled) {
            $midtrans_request = [
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
                        'id' => $invoice->id, // Simplified for now
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
                    'finish' => route('settings.billing.invoices.finish', $invoice_number),
                    'error' => route('settings.billing.invoices.error', $invoice_number),
                ],
            ];

            try {
                if (class_exists(MidtransService::class)) {
                    $midtrans = new MidtransService;
                    $transaction = (array) $midtrans->createTransaction($midtrans_request);
                } else {
                    $transaction = ['token' => 'dummy-token']; // Mock if service not found
                }
            } catch (\Exception $e) {
                return redirect()->route('settings.billing.index')
                    ->with(FlashDataVariable::WARNING->value, 'Gagal terhubung ke layanan pembayaran (Midtrans). Silakan coba lagi nanti atau gunakan metode manual. Detail: '.$e->getMessage());
            }

            $payment = $invoice->payments()->create([
                'amount' => $invoice->total_amount,
                'payment_method' => 'midtrans',
                'payment_reference' => $midtrans_request['transaction_details']['order_id'],
                'status' => 'pending',
                'json_request' => $midtrans_request,
                'json_respond' => $transaction,
            ]);
        }

        $manualValidation = PaymentManualValidation::where('invoice_id', $invoice->id)->first();

        $manualPaymentMethods = \App\Models\Master\SubscriptionManualPaymentMethod::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        if ($req->expectsJson()) {
            return response()->json([
                'invoice' => $invoice,
                'payment' => $payment,
                'midtransClientKey' => config('midtrans.client_key'),
                'manualValidation' => $manualValidation,
                'manualPaymentMethods' => $manualPaymentMethods,
                'isMidtransEnabled' => $isMidtransEnabled,
            ]);
        }

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice->invoice_number]);
    }

    public function changeMethod(Request $request, $invoice_number)
    {
        $request->validate([
            'payment_method' => 'required|in:midtrans,manual',
        ]);

        if ($request->payment_method === 'midtrans') {
            $isMidtransEnabled = SystemSetting::isMidtransEnabled();
            if (! $isMidtransEnabled) {
                return redirect()->back()->with(FlashDataVariable::FAILED->value, 'Metode pembayaran otomatis saat ini sedang dinonaktifkan.');
            }
        }

        $business = $request->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)->where('business_id', $business->id)->firstOrFail();

        // Delete any pending payments
        $invoice->payments()->delete();

        if ($request->payment_method === 'manual') {
            $invoice->payments()->create([
                'amount' => $invoice->total_amount,
                'payment_method' => 'manual',
                'status' => 'pending',
                'payment_reference' => "{$invoice->invoice_number}-MANUAL-".Str::upper(Str::random(4)),
            ]);
        }

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])
            ->with(FlashDataVariable::SUCCESS->value, 'Metode pembayaran berhasil diubah.');
    }

    public function uploadProof(Request $request, $invoice_number)
    {
        $request->validate([
            'payment_proof' => 'required|image|max:2048', // Max 2MB
        ]);

        $business = $request->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)->where('business_id', $business->id)->firstOrFail();

        // Save the uploaded proof
        $path = $request->file('payment_proof')->store('invoices/payment_proof');

        // Create or update manual validation record
        $validation = PaymentManualValidation::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'payment_proof_url' => $path,
                'validation_status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]
        );

        // Ensure there is a pending manual payment
        $payment = $invoice->payments()->where('payment_method', 'manual')->latest()->first();
        if (! $payment || $payment->status === 'failed') {
            $invoice->payments()->create([
                'amount' => $invoice->total_amount,
                'payment_method' => 'manual',
                'status' => 'pending',
                'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
            ]);
        }

        \App\Events\Invoice\PaymentProofUploaded::dispatch($invoice, $validation);

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])
            ->with(FlashDataVariable::SUCCESS->value, 'Bukti transfer berhasil diunggah. Tim kami akan segera melakukan verifikasi.');
    }

    public function cancel(Request $req, $invoice_number, \App\Services\App\Outlet\ManageOutletStatusService $manageStatusService)
    {
        $business = $req->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $invoice->update([
            'status' => Status::Void,
        ]);

        $isOutletAddition = false;

        // Find associated outlet to delete
        $outletAdditionItem = $invoice->items()->where('item_type', 'outlet_addition')->first();
        if ($outletAdditionItem && isset($outletAdditionItem->metadata['outlet_id'])) {
            $isOutletAddition = true;
            $outletId = $outletAdditionItem->metadata['outlet_id'];
            $outlet = \App\Models\Outlet::where('id', $outletId)
                ->where('business_id', $business->id)
                ->first();

            if ($outlet) {
                $manageStatusService->delete($outlet, $req->user());
            }
        }

        // Determine what type of subscription invoice this is
        $recurringPlanItem = $invoice->items()->where('item_type', 'recurring_plan')->first();
        $isPlanRenewal = $invoice->items()->where('item_type', 'plan_renewal')->exists();

        // Only cancel the subscription if it's a NEW subscription (recurring_plan) and NOT a renewal
        if ($recurringPlanItem && ! $isOutletAddition && ! $isPlanRenewal) {
            $subscriptionId = $recurringPlanItem->metadata['subscription_id'] ?? null;
            if ($subscriptionId) {
                $subscription = $business->subscriptions()->find($subscriptionId);
                // Ensure we only cancel if it's inactive (meaning it hasn't been paid/activated yet)
                if ($subscription && $subscription->status === 'inactive') {
                    $subscription->update([
                        'status' => 'canceled',
                        'canceled_at' => \Carbon\Carbon::now(),
                    ]);
                }
            }
        }

        return redirect()->route('settings.billing.index')->with(
            FlashDataVariable::SUCCESS->value,
            $isOutletAddition
                ? 'Tagihan berhasil dibatalkan dan outlet terkait telah dihapus.'
                : 'Tagihan berhasil dibatalkan.'
        );
    }

    public function error(Request $req, $invoice_number)
    {
        $order_id = $req->get('order_id');
        Payment::where('payment_reference', '=', $order_id)->update([
            'status' => 'failed',
        ]);

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])->with(
            FlashDataVariable::WARNING->value,
            'Request pembayaran gagal/kadaluarsa, silahkan ulangi.'
        );
    }

    public function finish(Request $req, $invoice_number)
    {
        $business = $req->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)->where('business_id', $business->id)->firstOrFail();

        $completeService = app(\App\Services\App\Invoice\CompleteInvoiceService::class);
        $completeService->execute($invoice);

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])->with(
            FlashDataVariable::SUCCESS->value,
            'Tagihan berhasil dibayarkan.'
        );
    }

    public function download(Request $req, $invoice_number)
    {
        $business = $req->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)->where('business_id', $business->id)->with(['items', 'business'])->firstOrFail();

        $payment = $invoice->payments()->latest()->first();

        if ($payment && $payment->status === 'failed') {
            $payment = null;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice', 'payment'));

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
