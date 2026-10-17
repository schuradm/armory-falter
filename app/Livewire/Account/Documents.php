<?php

namespace App\Livewire\Account;

use App\Enums\DocumentType;
use App\Services\Documents\DocumentService;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Upload von WBK, Jagdschein, Ausweis usw. mit Statusanzeige. */
class Documents extends Component
{
    use WithFileUploads;

    public string $type = 'id_card';

    public $file;

    public string $documentNumber = '';

    public string $issuingAuthority = '';

    public ?string $validUntil = null;

    public bool $confirm = false;

    public function mount(Seo $seo): void
    {
        $seo->title('Meine Dokumente')->noindex();
    }

    public function save(DocumentService $service): void
    {
        $this->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.config('shop.documents.max_kb')],
            'documentNumber' => ['nullable', 'string', 'max:60'],
            'issuingAuthority' => ['nullable', 'string', 'max:200'],
            'validUntil' => ['nullable', 'date', 'after:today'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Bitte bestätigen Sie, dass das Dokument Ihnen gehört und aktuell ist.',
            'file.max' => 'Die Datei darf höchstens 10 MB groß sein.',
            'validUntil.after' => 'Das Dokument ist bereits abgelaufen.',
        ]);

        if (! RateLimiter::attempt('doc-upload:'.Auth::id(), 20, fn () => true, 3600)) {
            $this->addError('file', 'Zu viele Uploads. Bitte versuchen Sie es später erneut.');

            return;
        }

        $service->store(Auth::user(), $this->file, [
            'type' => $this->type,
            'document_number' => $this->documentNumber ?: null,
            'issuing_authority' => $this->issuingAuthority ?: null,
            'valid_until' => $this->validUntil,
        ]);

        $this->reset(['file', 'documentNumber', 'issuingAuthority', 'validUntil', 'confirm']);
        $this->dispatch('toast', message: 'Dokument hochgeladen. Wir prüfen es zeitnah.');
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.account.documents', [
            'documents' => $user->documents()->latest()->get(),
            'entitlements' => $user->activeEntitlements()->with('caliber')->get(),
            'user' => $user,
        ])->layout('components.layouts.account', ['active' => 'documents']);
    }
}
