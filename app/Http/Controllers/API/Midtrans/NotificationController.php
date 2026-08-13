<?php

namespace App\Http\Controllers\API\Midtrans;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Log::info('midtrans_notification', $request->all());

        $serverKey = config('midtrans.server_key');

        $signature = hash(
            'sha512',
            $request->order_id.
            $request->status_code.
            $request->gross_amount.
            $serverKey
        );

        if ($signature !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payment = Payment::where('payment_reference', $request->order_id)->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment record not found'], 404);
        }

        $payment->payment_method = $request->payment_type ?? 'midtrans';
        $payment->json_respond = $request->all();

        $transactionStatus = $request->transaction_status;

        DB::beginTransaction();
        try {
            $invoice = $payment->invoice;
            $business = $invoice->business;
            $subscription = $business->subscriptions()->latest()->first();

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                $payment->status = 'success';
                $payment->paid_at = Carbon::now();

                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => Carbon::now(),
                ]);

                if ($subscription) {
                    $subscription->update([
                        'status' => 'active',
                    ]);
                }
            } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire', 'failure'])) {
                $payment->status = 'failed';

                if ($subscription) {
                    $subscription->update([
                        'status' => 'inactive',
                    ]);
                }
            } else {
                $payment->status = 'pending';
            }

            $payment->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return response()->json(['message' => 'Successfully processed notification'], 200);
    }
}
