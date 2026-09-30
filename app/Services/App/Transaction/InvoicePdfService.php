<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\DTOs\Transaction\InvoiceSummaryDTO;
use App\Models\Sales\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfService
{
    public function generatePdf(Transaction $transaction): Response
    {
        $transaction->load([
            'outlet.business',
            'customer',
            'invoice',
            'items',
            'payments.paymentMethod',
        ]);

        $summary = InvoiceSummaryDTO::fromTransaction($transaction);

        // Render PDF menggunakan facade PDF dari barryvdh/laravel-dompdf
        $pdf = Pdf::loadView('pdf.transaction-invoice', [
            'transaction' => $transaction,
            'summary' => $summary,
        ]);

        $filename = str_replace('/', '_', $transaction->invoice->invoice_number).'.pdf';

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
