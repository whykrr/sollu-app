<?php

namespace App\Http\Controllers\API\Midtrans;

use App\Enum\SubscriptionPayment\PaymentMethod;
use App\Enum\SubscriptionPayment\Status;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
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
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        if ($signature !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payment = SubscriptionPayment::whereOrderId($request->order_id)->first();

        $payment->transaction_id    = $request->transaction_id;
        $payment->payment_method    = PaymentMethod::from($request->payment_type);
        $payment->status            = Status::from($request->transaction_status);
        $payment->json_notification = $request->all();

        DB::beginTransaction();
        try {
            if ($payment->status === Status::Capture || $payment->status === Status::Settlement) {
                $payment->paid_at = Carbon::now()->format('Y-m-d H:i:s');

                $invoice      = $payment->invoice;
                $subscription = $payment->invoice->subscription;
                $merchant     = $payment->invoice->merchant;

                $invoice->status         = 'paid';
                $subscription->is_active = true;
                $merchant->expired_at    = $subscription->end_date;

                $invoice->save();
                $subscription->save();
                $merchant->save();
            }
            $payment->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;

            return response()->json(['message' => 'Server Error'], 500);
        }

        return response()->json(['message' => 'Successfully retrieve'], 200);
    }
}
