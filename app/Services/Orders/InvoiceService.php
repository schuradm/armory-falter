<?php

namespace App\Services\Orders;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\NumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function __construct(private NumberGenerator $numbers) {}

    public function createFor(Order $order): Invoice
    {
        if ($existing = $order->invoices()->where('type', 'invoice')->first()) {
            return $existing;
        }
        $number = $this->numbers->next('invoice', 'RE');
        $order->load('items', 'user', 'payments');
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $order, 'number' => $number, 'company' => config('shop.company')])->output();
        $path = "invoices/{$order->placed_at->year}/{$number}.pdf";
        Storage::disk('local')->put($path, $pdf);

        return $order->invoices()->create([
            'invoice_number' => $number,
            'type' => 'invoice',
            'pdf_path' => $path,
            'total_gross' => $order->total_gross,
            'issued_at' => now(),
        ]);
    }
}
