<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\Billing\ChangePaymentMethodRequest;
use App\Http\Requests\App\Settings\Billing\UploadPaymentProofRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\App\Invoice\CancelInvoiceService;
use App\Services\App\Invoice\ChangePaymentMethodService;
use App\Services\App\Invoice\CompleteInvoiceService;
use App\Services\App\Invoice\ShowInvoiceService;
use App\Services\App\Invoice\UploadPaymentProofService;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(
        protected ShowInvoiceService $showInvoiceService,
        protected ChangePaymentMethodService $changePaymentMethodService,
        protected UploadPaymentProofService $uploadPaymentProofService,
        protected CancelInvoiceService $cancelInvoiceService,
        protected CompleteInvoiceService $completeInvoiceService
    ) {}

    public function show(Request $request, string $invoice_number): JsonResponse|RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $details = $this->showInvoiceService->getInvoiceDetails($invoice_number, $business);

        if ($request->expectsJson()) {
            return response()->json($details);
        }

        return redirect()->route('settings.billing.index', ['open_invoice' => $details['invoice']->invoice_number]);
    }

    public function changeMethod(ChangePaymentMethodRequest $request, string $invoice_number): RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $invoice = Invoice::query()
            ->where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        try {
            $this->changePaymentMethodService->execute($invoice, $request->validated('payment_method'));

            return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])
                ->with(FlashDataVariable::SUCCESS->value, 'Metode pembayaran berhasil diubah.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with(FlashDataVariable::FAILED->value, $e->getMessage());
        }
    }

    public function uploadProof(UploadPaymentProofRequest $request, string $invoice_number): RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $invoice = Invoice::query()
            ->where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $this->uploadPaymentProofService->execute($invoice, $request->file('payment_proof'));

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])
            ->with(FlashDataVariable::SUCCESS->value, 'Bukti transfer berhasil diunggah. Tim kami akan segera melakukan verifikasi.');
    }

    public function cancel(Request $request, string $invoice_number): RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $invoice = Invoice::query()
            ->where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $result = $this->cancelInvoiceService->execute($invoice, $request->user());

        return redirect()->route('settings.billing.index')->with(
            FlashDataVariable::SUCCESS->value,
            $result['is_outlet_addition']
                ? 'Tagihan berhasil dibatalkan dan outlet terkait telah dihapus.'
                : 'Tagihan berhasil dibatalkan.'
        );
    }

    public function error(Request $request, string $invoice_number): RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $order_id = $request->get('order_id');
        Payment::where('payment_reference', '=', $order_id)->update([
            'status' => 'failed',
        ]);

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])->with(
            FlashDataVariable::WARNING->value,
            'Request pembayaran gagal/kadaluarsa, silahkan ulangi.'
        );
    }

    public function finish(Request $request, string $invoice_number): RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $invoice = Invoice::query()
            ->where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->firstOrFail();

        $this->completeInvoiceService->execute($invoice);

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice_number])->with(
            FlashDataVariable::SUCCESS->value,
            'Tagihan berhasil dibayarkan.'
        );
    }

    public function download(Request $request, string $invoice_number): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $invoice = Invoice::query()
            ->where('invoice_number', $invoice_number)
            ->where('business_id', $business->id)
            ->with(['items', 'business'])
            ->firstOrFail();

        $payment = $invoice->payments()->latest()->first();

        if ($payment && $payment->status === 'failed') {
            $payment = null;
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'payment'));

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
