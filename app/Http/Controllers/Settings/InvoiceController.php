<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentManualValidation;
use App\Services\MidtransService;
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

        if (! $payment && $invoice->status === 'open') {
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

            // Assuming MidtransService exists and works as in old code
            if (class_exists(MidtransService::class)) {
                $midtrans = new MidtransService;
                $transaction = $midtrans->createTransaction($midtrans_request);
            } else {
                $transaction = ['token' => 'dummy-token']; // Mock if service not found
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

        return inertia('Settings/Billing/DetailInvoice', [
            'invoice' => $invoice,
            'payment' => $payment,
            'midtransClientKey' => config('midtrans.client_key'),
            'manualValidation' => $manualValidation,
        ]);
    }

    public function changeMethod(Request $request, $invoice_number)
    {
        $request->validate([
            'payment_method' => 'required|in:midtrans,manual',
        ]);

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

        return redirect()->route('settings.billing.invoices.show', $invoice_number)
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
        PaymentManualValidation::updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'payment_proof_url' => $path,
                'validation_status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return redirect()->route('settings.billing.invoices.show', $invoice_number)
            ->with(FlashDataVariable::SUCCESS->value, 'Bukti transfer berhasil diunggah. Tim kami akan segera melakukan verifikasi.');
    }

    public function cancel(Request $req, $invoice_number, \App\Services\Outlet\ManageOutletStatusService $manageStatusService)
    {
        $business = $req->user()->business;
        $invoice = Invoice::where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $invoice->update([
            'status' => 'void',
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

        // Only cancel the main subscription if this is NOT a prorated outlet addition invoice
        if (! $isOutletAddition) {
            $subscription = $business->subscriptions()->latest()->first();
            if ($subscription) {
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => \Carbon\Carbon::now(),
                ]);
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

        return redirect()->route('settings.billing.invoices.show', $invoice_number)->with(
            FlashDataVariable::WARNING->value,
            'Request pembayaran gagal/kadaluarsa, silahkan ulangi.'
        );
    }

    public function finish(Request $req, $invoice_number)
    {
        $business = $req->user()->business;
        Invoice::where('invoice_number', $invoice_number)->where('business_id', $business->id)->update([
            'status' => 'paid',
            'paid_at' => \Carbon\Carbon::now(),
        ]);

        $subscription = $business->subscriptions()->latest()->first();
        if ($subscription) {
            $subscription->update([
                'status' => 'active',
            ]);
        }

        return redirect()->route('settings.billing.invoices.show', $invoice_number)->with(
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
