<?php

namespace App\Http\Controllers\Dashboard\Merchant;

use App\Enum\SubscriptionInvoice\Status as SubscriptionInvoiceStatus;
use App\Enum\SubscriptionPayment\PaymentType;
use App\Enum\SubscriptionPayment\Status;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Str;

class InvoiceController extends Controller
{
    public function index(Request $req)
    {
        $invoices = SubscriptionInvoice::currentMerchant()
            ->sortable($req->get('sort', 'created_at'), $req->get('direction', 'desc'))
            ->filters($req->only(['search', 'status']))
            ->with(['plan'])
            ->paginate($req->get('perpage', 20))
            ->appends($req->query());

        return inertia(
            'Dashboard/Merchant/Invoice/Index',
            [
                'invoices' => $invoices,
                'params'   => $req->all(),
            ]
        );
    }

    public function show(Request $req, $code)
    {
        $invoice = SubscriptionInvoice::whereCode($code)->with('merchant', 'plan', 'items.outlet')->firstOrFail();
        $payment = $invoice->payments()->latest()->first();

        if ($payment && $payment->status === Status::Expire) {
            $payment = null;
        }

        if (! $payment && $invoice->status === SubscriptionInvoiceStatus::Unpaid) {
            $midtrans_request = [
                'transaction_details' => [
                    'order_id'     => "{$invoice->code}-".Str::upper(Str::random(4)),
                    'gross_amount' => (int) $invoice->total,

                ],
                'customer_details' => [
                    'first_name'      => $invoice->merchant->name,
                    'email'           => $invoice->merchant->email,
                    'phone'           => $invoice->merchant->phone,
                    'billing_address' => [
                        'address' => $invoice->merchant->address,
                    ],

                ],
                'item_details' => [
                    [
                        'id'       => $invoice->plan->id,
                        'price'    => $invoice->total,
                        'quantity' => 1,
                        'name'     => $invoice->plan->name,
                    ],
                ],
                'expiry' => [
                    'unit'     => 'minute',
                    'duration' => 60,
                ],
                'callbacks' => [
                    'finish' => route('dashboard.merchant.invoices.finish', $code),
                    'error'  => route('dashboard.merchant.invoices.error', $code),
                ],
            ];
            $midtrans    = new MidtransService();
            $transaction = $midtrans->createTransaction($midtrans_request);

            $payment = $invoice->payments()->create([
                'amount'       => $invoice->total,
                'payment_type' => PaymentType::MIDTRANS,
                'order_id'     => $midtrans_request['transaction_details']['order_id'],
                'status'       => Status::Request,
                'json_request' => $midtrans_request,
                'json_respond' => $transaction,
            ]);
        }


        return inertia('Dashboard/Merchant/Invoice/Show', [
            'invoice'           => $invoice,
            'payment'           => $payment,
            'midtransClientKey' => config('midtrans.client_key'),
        ]);
    }
    public function cancel(Request $req, $code)
    {
        SubscriptionInvoice::whereCode($code)->update([
            'status' => SubscriptionInvoiceStatus::Canceled,
        ]);

        return redirect()->route('dashboard.merchant.invoices.index')->with('success', 'Tagihan berhasil dibatalkan');
    }

    public function error(Request $req, $code)
    {
        $order_id = $req->get('order_id');
        SubscriptionPayment::where('order_id', '=', $order_id)->update([
            'status' => Status::Expire,
        ]);

        return redirect()->route('dashboard.merchant.invoices.show', $code)->with('success', 'Request pembayaran berhasil diperbaharui, silahkan melakukan pembayaran lagi');
    }

    public function finish(Request $req, $code)
    {
        SubscriptionInvoice::whereCode($code)->update([
            'status' => SubscriptionInvoiceStatus::Payment,
        ]);

        return redirect()->route('dashboard.merchant.invoices.show', $code)->with('success', 'Tagihan berhasil dibayarkan, menunggu status dari penyedia pembayaran');
    }
}
