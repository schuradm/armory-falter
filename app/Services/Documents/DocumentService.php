<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Enums\EntitlementType;
use App\Models\CustomerDocument;
use App\Models\CustomerEntitlement;
use App\Models\User;
use App\Notifications\DocumentReviewed;
use App\Services\AuditLogger;
use App\Support\BlindIndex;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Upload, automatische Vorprüfung und Freigabe von Erwerbsdokumenten.
 * Dateien liegen verschlüsselt auf einer privaten Disk außerhalb des Webroots.
 */
class DocumentService
{
    public function __construct(private AuditLogger $audit) {}

    public function store(User $user, UploadedFile $file, array $data): CustomerDocument
    {
        $content = $file->get();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($content);
        $path = $user->id.'/'.Str::uuid().'.bin';
        Storage::disk(config('shop.documents.disk'))->put($path, encrypt($content));

        $doc = new CustomerDocument([
            'type' => $data['type'],
            'document_number' => $data['document_number'] ?? null,
            'issuing_authority' => $data['issuing_authority'] ?? null,
            'issued_at' => $data['issued_at'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'storage_path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
            'mime_type' => $mime,
            'file_size' => $file->getSize(),
            'sha256' => hash('sha256', $content),
            'status' => DocumentStatus::Uploaded,
        ]);
        $doc->user()->associate($user);
        $doc->precheck_result = $this->precheck($doc, $user, $content);
        $doc->status = empty($doc->precheck_result['flags']) ? DocumentStatus::PrecheckPassed : DocumentStatus::PrecheckFlagged;
        $doc->save();

        $this->audit->log('document.uploaded', $doc, new: ['type' => $doc->type->value, 'status' => $doc->status->value]);

        return $doc;
    }

    /**
     * Automatische Vorprüfung. Sie ersetzt keine Prüfung durch einen Menschen, sondern sortiert vor.
     * Eine OCR-Auswertung ist auf Shared Hosting nicht vorgesehen (kein Tesseract); siehe Doku.
     */
    public function precheck(CustomerDocument $doc, User $user, string $content): array
    {
        $flags = [];
        $info = [];

        if (! in_array($doc->mime_type, config('shop.documents.mimes'), true)) {
            $flags[] = 'Dateityp nicht erlaubt ('.$doc->mime_type.')';
        }
        if (str_starts_with($doc->mime_type, 'image/')) {
            $size = @getimagesizefromstring($content);
            if (! $size) {
                $flags[] = 'Bild nicht lesbar';
            } else {
                $info['dimensions'] = "{$size[0]}×{$size[1]}";
                if (min($size[0], $size[1]) < 800) {
                    $flags[] = 'Auflösung gering (unter 800 px) – Lesbarkeit prüfen';
                }
            }
        }
        if ($doc->mime_type === 'application/pdf' && ! str_starts_with($content, '%PDF')) {
            $flags[] = 'PDF-Struktur ungültig';
        }
        if ($doc->valid_until && $doc->valid_until->isPast()) {
            $flags[] = 'Gültigkeitsdatum liegt in der Vergangenheit';
        }
        if (! $user->birth_date) {
            $flags[] = 'Kein Geburtsdatum im Kundenkonto';
        }
        $duplicate = CustomerDocument::where('sha256', hash('sha256', $content))->where('user_id', '!=', $user->id)->exists();
        if ($duplicate) {
            $flags[] = 'Identische Datei bei einem anderen Kundenkonto vorhanden';
        }
        if ($doc->document_number) {
            $bidx = BlindIndex::make($doc->document_number);
            if (CustomerDocument::where('document_number_bidx', $bidx)->where('user_id', '!=', $user->id)->exists()) {
                $flags[] = 'Dokumentnummer bei einem anderen Kundenkonto hinterlegt';
            }
        }

        return ['flags' => $flags, 'info' => $info, 'checked_at' => now()->toIso8601String()];
    }

    public function contents(CustomerDocument $doc): string
    {
        return decrypt(Storage::disk(config('shop.documents.disk'))->get($doc->storage_path));
    }

    /**
     * Freigabe durch Sachbearbeiter. $entitlements: Liste von
     * ['type' => EntitlementType, 'weapon_kind' => ?, 'caliber_id' => ?, 'quantity_total' => ?, 'valid_until' => ?]
     */
    public function approve(CustomerDocument $doc, User $reviewer, array $entitlements, bool $confirmBirthDate, ?string $birthDate = null): void
    {
        DB::transaction(function () use ($doc, $reviewer, $entitlements, $confirmBirthDate, $birthDate) {
            $doc->status = DocumentStatus::Approved;
            $doc->reviewed_by = $reviewer->id;
            $doc->reviewed_at = now();
            $doc->rejection_reason = null;
            $doc->delete_after = $doc->valid_until?->copy()->addMonths(config('shop.documents.retention_months_after_expiry'));
            $doc->save();

            foreach ($entitlements as $e) {
                CustomerEntitlement::create([
                    'user_id' => $doc->user_id,
                    'document_id' => $doc->id,
                    'type' => $e['type'] instanceof EntitlementType ? $e['type']->value : $e['type'],
                    'weapon_kind' => $e['weapon_kind'] ?? null,
                    'caliber_id' => $e['caliber_id'] ?? null,
                    'quantity_total' => $e['quantity_total'] ?? null,
                    'valid_from' => $e['valid_from'] ?? today(),
                    'valid_until' => $e['valid_until'] ?? $doc->valid_until,
                    'status' => 'active',
                    'created_by' => $reviewer->id,
                ]);
            }

            if ($confirmBirthDate && $doc->type->provesAge()) {
                $user = $doc->user;
                if ($birthDate) {
                    $user->birth_date = $birthDate;
                }
                $user->age_verified_at = now();
                $user->age_verification_method = 'document_review';
                $user->save();
            }

            $this->audit->log('document.approved', $doc, new: [
                'entitlements' => array_map(fn ($e) => $e['type'] instanceof EntitlementType ? $e['type']->value : $e['type'], $entitlements),
                'age_confirmed' => $confirmBirthDate,
            ]);
        });

        $doc->user->notify(new DocumentReviewed($doc));
    }

    public function reject(CustomerDocument $doc, User $reviewer, string $reason): void
    {
        $doc->forceFill([
            'status' => DocumentStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
            'delete_after' => today()->addMonths(config('shop.documents.retention_months_rejected')),
        ])->save();

        $this->audit->log('document.rejected', $doc, new: ['reason' => $reason]);
        $doc->user->notify(new DocumentReviewed($doc));
    }

    /** Täglich: abgelaufene Dokumente/Berechtigungen markieren und fällige Dateien löschen. */
    public function housekeeping(): array
    {
        $expired = CustomerDocument::where('status', DocumentStatus::Approved)->whereDate('valid_until', '<', today())->get();
        foreach ($expired as $doc) {
            $doc->forceFill(['status' => DocumentStatus::Expired])->save();
            $doc->entitlements()->where('status', 'active')->update(['status' => 'expired']);
        }
        CustomerEntitlement::where('status', 'active')->whereDate('valid_until', '<', today())->update(['status' => 'expired']);

        $deleted = 0;
        CustomerDocument::whereDate('delete_after', '<', today())->where('storage_path', '!=', 'deleted')->each(function (CustomerDocument $doc) use (&$deleted) {
            Storage::disk(config('shop.documents.disk'))->delete($doc->storage_path);
            $doc->forceFill(['storage_path' => 'deleted', 'document_number' => null])->save();
            $deleted++;
        });

        return ['expired' => $expired->count(), 'files_deleted' => $deleted];
    }
}
