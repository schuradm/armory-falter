<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Support\BlindIndex;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDocument extends Model
{
    protected $fillable = ['type', 'document_number', 'issuing_authority', 'issued_at', 'valid_until', 'storage_path',
        'original_name', 'mime_type', 'file_size', 'sha256', 'status', 'precheck_result', 'delete_after'];

    protected $hidden = ['storage_path'];

    protected $casts = [
        'type' => DocumentType::class,
        'status' => DocumentStatus::class,
        'document_number' => 'encrypted',
        'issued_at' => 'date',
        'valid_until' => 'date',
        'delete_after' => 'date',
        'reviewed_at' => 'datetime',
        'precheck_result' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (CustomerDocument $doc) {
            if ($doc->isDirty('document_number')) {
                $doc->document_number_bidx = $doc->document_number ? BlindIndex::make($doc->document_number) : null;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(CustomerEntitlement::class, 'document_id');
    }

    public function scopeAwaitingReview($query)
    {
        return $query->whereIn('status', [DocumentStatus::Uploaded, DocumentStatus::PrecheckPassed, DocumentStatus::PrecheckFlagged]);
    }
}
