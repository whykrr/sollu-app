<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Sales\Transaction;
use App\Services\App\Transaction\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoicePdfService $pdfService,
    ) {}

    public function downloadPdf(Request $request, Transaction $transaction): Response
    {
        $user = $request->user();
        if ($transaction->outlet?->business_id !== $user?->business_id) {
            abort(403, 'Akses faktur tidak diizinkan.');
        }

        // Pastikan hanya bisa download jika invoice sudah diterbitkan (bukan draf murni yang belum ada invoice)
        if (! $transaction->invoice) {
            abort(404, 'Faktur belum tersedia untuk transaksi ini.');
        }

        return $this->pdfService->generatePdf($transaction);
    }
}
