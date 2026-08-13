<?php

namespace App\Services\Dashboard;

use App\Enums\SubscriptionPayment\PaymentMethod;
use App\Enums\SubscriptionPayment\PaymentType;
use App\Enums\SubscriptionPayment\Status;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionPaymentService
{
    /**
     * Undocumented function
     *
     * @param  array  $midtrans_request
     * @param  array  $midtrans_response
     * @return SubscriptionPayment
     */
    public function requestPayment(SubscriptionInvoice $invoice, $midtrans_request, $midtrans_response)
    {
        return $invoice->payments()->create([
            'amount' => $invoice->total,
            'payment_type' => PaymentType::MIDTRANS,
            'order_id' => $midtrans_request['transaction_details']['order_id'],
            'status' => Status::Request,
            'json_request' => $midtrans_request,
            'json_respond' => $midtrans_response,
        ]);
    }

    /**
     * get last payment from invoice
     *
     * @return SubscriptionPayment|null
     */
    public function getLatestPayment(SubscriptionInvoice $invoice)
    {
        return $invoice->payments()->latest()->first();
    }

    /**
     * Undocumented function
     *
     * @param  Request  $request
     * @return SubscriptionPayment
     */
    public function handleNotification(array $data)
    {
        $payment = SubscriptionPayment::whereOrderId($data['order_id'])->first();

        $payment->transaction_id = $data['transaction_id'];
        $payment->payment_method = PaymentMethod::from($data['payment_type']);
        $payment->status = Status::from($data['transaction_status']);
        $payment->json_notification = $data;

        $payment->save();

        $payment->paid_at = Carbon::now()->format('Y-m-d H:i:s');

        $invoice = $payment->invoice;
        $subscription = $payment->invoice->subscription;
        $merchant = $payment->invoice->merchant;

        $invoice->status = 'paid';
        $subscription->is_active = true;
        $merchant->expired_at = $subscription->end_date;

        $invoice->save();
        $subscription->save();
        $merchant->save();
        $payment->save();
    }
}
