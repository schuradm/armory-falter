<?php

namespace App\Services\Trade;

use App\Enums\TradeRequestStatus;
use App\Enums\TradeRequestType;
use App\Mail\TradeRequestReceived;
use App\Models\TradeRequest;
use App\Services\AuditLogger;
use App\Services\NumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TradeRequestService
{
    public function __construct(private NumberGenerator $numbers, private AuditLogger $audit) {}

    /** @param list<UploadedFile> $photos */
    public function create(TradeRequestType $type, array $data, array $photos, ?int $userId): TradeRequest
    {
        $request = DB::transaction(function () use ($type, $data, $photos, $userId) {
            $request = TradeRequest::create([
                'reference' => $this->numbers->next('trade-'.$type->value, $type->prefix(), 4),
                'type' => $type,
                'user_id' => $userId,
                'contact' => [
                    'name' => $data['name'],
                    'street' => $data['street'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'city' => $data['city'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'],
                ],
                'weapon_type' => $data['weapon_type'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'manufacturer_name' => $data['manufacturer_name'] ?? null,
                'model' => $data['model'],
                'caliber' => $data['caliber'] ?? null,
                'condition_code' => $data['condition_code'],
                'desired_price' => $data['desired_price'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => TradeRequestStatus::New,
                'privacy_accepted_at' => now(),
            ]);

            foreach (array_slice($photos, 0, config('shop.trade_requests.max_photos')) as $i => $photo) {
                $path = $photo->storeAs("trade-requests/{$request->id}", Str::uuid().'.'.$photo->extension(), 'local');
                $request->media()->create(['path' => $path, 'sort_order' => $i]);
            }

            return $request;
        });

        $this->generatePdf($request);
        $this->audit->log('trade_request.created', $request, new: ['type' => $type->value]);

        Mail::to(config('shop.company.email'))->queue(new TradeRequestReceived($request, internal: true));
        Mail::to($request->contact['email'])->queue(new TradeRequestReceived($request, internal: false));

        return $request;
    }

    /** PDF-Dossier für das Beratungsgespräch (Daten + Fotos). */
    public function generatePdf(TradeRequest $request): string
    {
        $request->load('media', 'manufacturer');
        $photos = $request->media->map(function ($m) {
            $content = Storage::disk('local')->get($m->path);
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);

            return 'data:'.$mime.';base64,'.base64_encode($content);
        })->all();

        $pdf = Pdf::loadView('pdf.trade-request', ['request' => $request, 'photos' => $photos, 'company' => config('shop.company')])
            ->setPaper('a4')->output();
        $path = "trade-requests/{$request->id}/{$request->reference}.pdf";
        Storage::disk('local')->put($path, $pdf);
        $request->update(['advisory_pdf_path' => $path]);

        return $path;
    }

    /**
     * Export im CSV-Format für das Einstellen bei egun (manuell oder über den egun-Händlerimport).
     * Feldbelegung vor Go-live mit egun abstimmen.
     */
    public function egunCsvRow(TradeRequest $r): array
    {
        return [
            'Titel' => trim($r->manufacturerLabel().' '.$r->model.' '.$r->caliber),
            'Hersteller' => $r->manufacturerLabel(),
            'Modell' => $r->model,
            'Kaliber' => $r->caliber,
            'Zustand' => $r->condition_code->label(),
            'Startpreis' => $r->offer_price ? number_format($r->offer_price / 100, 2, ',', '') : '',
            'Beschreibung' => $r->description,
            'Referenz' => $r->reference,
        ];
    }
}
