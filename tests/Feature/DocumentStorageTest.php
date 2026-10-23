<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\EntitlementType;
use App\Livewire\Account\Documents;
use App\Models\CustomerDocument;
use App\Services\Documents\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/** Erwerbsdokumente: verschlüsselte Ablage, automatische Vorprüfung und Löschkonzept (documents:housekeeping). */
class DocumentStorageTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
        Storage::fake('documents');
        config(['shop.documents.disk' => 'documents']);
    }

    private function store($user, UploadedFile $file, array $data = []): CustomerDocument
    {
        return app(DocumentService::class)->store($user, $file, $data + ['type' => 'wbk_green', 'valid_until' => now()->addYears(3)->toDateString()]);
    }

    public function test_datei_und_dokumentnummer_liegen_nur_verschluesselt_vor(): void
    {
        $user = $this->customer();
        $file = UploadedFile::fake()->image('wbk.png', 1400, 1000);
        $plain = $file->get();

        $doc = $this->store($user, $file, ['document_number' => '08/15-WBK-4711']);

        $path = $doc->getRawOriginal('storage_path');
        $this->assertStringStartsWith($user->id.'/', $path);
        $this->assertStringEndsWith('.bin', $path, 'Kein Originalname und keine Dateiendung auf der Disk');
        $raw = Storage::disk('documents')->get($path);
        $this->assertNotSame($plain, $raw);
        $this->assertStringNotContainsString("\x89PNG", $raw);
        $this->assertSame($plain, app(DocumentService::class)->contents($doc));
        $this->assertSame(hash('sha256', $plain), $doc->sha256);
        $this->assertSame('image/png', $doc->mime_type);

        $column = DB::table('customer_documents')->where('id', $doc->id)->value('document_number');
        $this->assertStringNotContainsString('4711', $column);
        $this->assertSame('08/15-WBK-4711', $doc->fresh()->document_number);
        $this->assertArrayNotHasKey('storage_path', $doc->toArray(), 'Speicherpfad wird nie serialisiert');
    }

    public function test_vorpruefung_markiert_auffaellige_uploads(): void
    {
        $user = $this->customer();
        $ok = $this->store($user, UploadedFile::fake()->image('gut.png', 1400, 1000), ['document_number' => 'WBK-1']);
        $this->assertSame(DocumentStatus::PrecheckPassed, $ok->status);
        $this->assertSame([], $ok->precheck_result['flags']);
        $this->assertSame('1400×1000', $ok->precheck_result['info']['dimensions']);

        $small = $this->store($user, UploadedFile::fake()->image('klein.png', 600, 400));
        $this->assertSame(DocumentStatus::PrecheckFlagged, $small->status);
        $this->assertStringContainsString('Auflösung gering', implode(' ', $small->precheck_result['flags']));

        // Dieselbe Datei und dieselbe Dokumentnummer bei einem anderen Konto: Hinweis auf Missbrauch
        $copy = UploadedFile::fake()->createWithContent('kopie.png', app(DocumentService::class)->contents($ok));
        $other = $this->customer('Eve', 'Fremd');
        $dupe = $this->store($other, $copy, ['document_number' => 'WBK-1']);
        $flags = implode(' | ', $dupe->precheck_result['flags']);
        $this->assertStringContainsString('Identische Datei bei einem anderen Kundenkonto', $flags);
        $this->assertStringContainsString('Dokumentnummer bei einem anderen Kundenkonto', $flags);
        $this->assertSame(DocumentStatus::PrecheckFlagged, $dupe->status);

        // Ohne Geburtsdatum im Konto
        $noBirth = $this->customer('Ohne', 'Datum');
        $noBirth->forceFill(['birth_date' => null])->save();
        $this->assertContains('Kein Geburtsdatum im Kundenkonto', $this->store($noBirth, UploadedFile::fake()->image('a.png', 1400, 1000))->precheck_result['flags']);
    }

    public function test_upload_formular_nimmt_nur_pdf_und_bilder(): void
    {
        $user = $this->customer();

        Livewire::actingAs($user)->test(Documents::class)
            ->set('type', 'wbk_green')->set('validUntil', now()->addYear()->toDateString())
            ->set('file', UploadedFile::fake()->createWithContent('wbk.html', '<script>alert(1)</script>'))
            ->set('confirm', true)
            ->call('save')
            ->assertHasErrors('file');

        $this->assertSame(0, $user->documents()->count());
        $this->assertSame([], Storage::disk('documents')->allFiles());
    }

    public function test_housekeeping_markiert_abgelaufene_und_loescht_faellige_dateien(): void
    {
        $user = $this->customer();
        $service = app(DocumentService::class);
        $doc = $this->store($user, UploadedFile::fake()->image('wbk.png', 1400, 1000), ['valid_until' => now()->addMonth()->toDateString()]);
        $service->approve($doc, $this->admin, [['type' => EntitlementType::HuntingLicenseLonggun]], false);
        $ent = $user->entitlements()->sole();
        $this->assertEquals(now()->addMonth()->addMonths(6)->toDateString(), $doc->fresh()->delete_after->toDateString());

        $rejected = $this->store($user, UploadedFile::fake()->image('alt.png', 1400, 1000));
        $service->reject($rejected, $this->admin, 'unleserlich');

        // Kurz nach Ablauf: Dokument und Berechtigung abgelaufen, Datei noch vorhanden
        $this->travel(2)->months();
        $this->artisan('documents:housekeeping')->expectsOutput('Abgelaufen markiert: 1, Dateien gelöscht: 1')->assertSuccessful();
        $this->assertSame(DocumentStatus::Expired, $doc->fresh()->status);
        $this->assertSame('expired', $ent->fresh()->status);
        Storage::disk('documents')->assertExists($doc->getRawOriginal('storage_path'));
        // Abgelehnte Unterlagen nach einem Monat gelöscht
        Storage::disk('documents')->assertMissing($rejected->getRawOriginal('storage_path'));
        $this->assertSame('deleted', $rejected->fresh()->getRawOriginal('storage_path'));

        // Nach Ablauf der Aufbewahrungsfrist auch die Datei des abgelaufenen Dokuments
        $this->travel(6)->months();
        $this->artisan('documents:housekeeping')->expectsOutput('Abgelaufen markiert: 0, Dateien gelöscht: 1')->assertSuccessful();
        Storage::disk('documents')->assertMissing($doc->getRawOriginal('storage_path'));
        $this->assertNull($doc->fresh()->document_number);
    }
}
