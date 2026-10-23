<?php

namespace Tests\Feature;

use App\Filament\Resources\WeaponBookEntryResource\Pages\CreateWeaponBookEntry;
use App\Filament\Resources\WeaponBookEntryResource\Pages\EditWeaponBookEntry;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\WeaponBookEntry;
use App\Models\WeaponItem;
use App\Services\AuditLogger;
use App\Services\NumberGenerator;
use App\Services\Orders\InvoiceService;
use App\Services\Orders\OrderService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use LogicException;
use Tests\Feature\Concerns\ShopFixtures;
use Tests\TestCase;

/**
 * Aufbewahrungspflichtige Aufzeichnungen: Waffenhandelsbuch (§ 23 WaffG), lückenlose Rechnungsnummern (GoBD)
 * und die Hash-Kette des Audit-Logs (audit:verify).
 */
class RecordKeepingTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedShop();
    }

    private function weapon(string $serial = 'BAFX123'): WeaponItem
    {
        return WeaponItem::create(['product_id' => Product::where('sku', 'GLO-G17-G5')->value('id'), 'serial_number' => $serial, 'status' => 'in_stock']);
    }

    private function entry(WeaponItem $item): WeaponBookEntry
    {
        return WeaponBookEntry::create(['weapon_item_id' => $item->id, 'direction' => 'out', 'booked_by' => $this->admin->id, 'booked_at' => now(),
            'counterparty' => ['name' => 'Max Testkunde', 'address' => 'Hauptstraße 12, 72649 Wolfschlugen'], 'permit_reference' => 'WBK 08/15']);
    }

    public function test_seriennummer_und_gegenpartei_verschluesselt_mit_eindeutigem_suchindex(): void
    {
        $item = $this->weapon('BAFX123');
        $entry = $this->entry($item);

        $raw = DB::table('weapon_items')->where('id', $item->id)->first();
        $this->assertStringNotContainsString('BAFX123', $raw->serial_number);
        $this->assertSame(64, strlen($raw->serial_bidx));
        $this->assertSame('BAFX123', $item->fresh()->serial_number);
        $this->assertStringNotContainsString('Testkunde', DB::table('weapon_book_entries')->where('id', $entry->id)->value('counterparty'));
        $this->assertSame('Max Testkunde', $entry->fresh()->counterparty['name']);

        // Dieselbe Seriennummer kann nicht zweimal als eigene Waffe angelegt werden
        $this->expectException(QueryException::class);
        $this->weapon('BAFX123');
    }

    public function test_eintraege_sind_unveraenderlich_nur_nwr_meldung_nachtragbar(): void
    {
        $entry = $this->entry($this->weapon());

        $entry->update(['nwr_reported_at' => now(), 'nwr_reference' => 'NWR-2026-42']);
        $this->assertSame('NWR-2026-42', $entry->fresh()->nwr_reference);

        try {
            $entry->update(['direction' => 'in']);
            $this->fail('Buchung wurde geändert');
        } catch (LogicException) {
        }
        try {
            $entry->update(['counterparty' => ['name' => 'Jemand anderes', 'address' => 'x']]);
            $this->fail('Gegenpartei wurde geändert');
        } catch (LogicException) {
        }
        try {
            $entry->delete();
            $this->fail('Eintrag wurde gelöscht');
        } catch (LogicException) {
        }

        $fresh = $entry->fresh();
        $this->assertSame('out', $fresh->direction);
        $this->assertSame('Max Testkunde', $fresh->counterparty['name']);
    }

    public function test_buchung_im_backend_mit_bucher_und_audit_eintrag(): void
    {
        $item = $this->weapon();
        $this->actingAs($this->admin);

        Livewire::test(CreateWeaponBookEntry::class)
            ->fillForm([
                'weapon_item_id' => $item->id, 'direction' => 'in', 'booked_at' => now()->subHour(),
                'counterparty' => ['name' => 'Glock GmbH', 'address' => 'Deutsch-Wagram'], 'permit_reference' => 'Händler',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $entry = WeaponBookEntry::sole();
        $this->assertSame($this->admin->id, $entry->booked_by);
        $this->assertSame('Glock GmbH', $entry->counterparty['name']);
        $this->assertTrue(AuditLog::where('action', 'weapon_book.booked')->where('subject_id', $item->id)->exists());

        // Bearbeiten speichert nur die NWR-Felder, auch wenn gesperrte Felder manipuliert werden
        Livewire::test(EditWeaponBookEntry::class, ['record' => $entry->getRouteKey()])
            ->fillForm(['direction' => 'out', 'nwr_reported_at' => now(), 'nwr_reference' => 'NWR-77'])
            ->call('save')
            ->assertHasNoFormErrors();

        $entry->refresh();
        $this->assertSame('in', $entry->direction);
        $this->assertSame('NWR-77', $entry->nwr_reference);
    }

    public function test_rechnungsnummern_fortlaufend_einmalig_und_je_jahr_neu(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 31)->setTime(12, 0));
        $orders = [];
        foreach (['Anna', 'Bert'] as $name) {
            $user = $this->customer($name);
            $this->fillCart($user, ['WAL-CLEAN-UNI' => 1]);
            $orders[] = $this->checkout($user);
        }
        $this->assertSame(['AF-2026-000001', 'AF-2026-000002'], array_map(fn ($o) => $o->order_number, $orders));

        $service = app(OrderService::class);
        $service->markPaid($orders[1], $this->admin);
        $service->markPaid($orders[0], $this->admin);
        $service->markPaid($orders[0]->refresh(), $this->admin); // doppelte Zahlungsmeldung
        $this->assertSame(1, $orders[0]->payments()->count());

        // Nummer nach Rechnungsdatum, nicht nach Bestellnummer; keine zweite Rechnung je Bestellung
        $this->assertSame('RE-2026-000001', $orders[1]->invoices()->sole()->invoice_number);
        $this->assertSame('RE-2026-000002', $orders[0]->invoices()->sole()->invoice_number);
        $again = app(InvoiceService::class)->createFor($orders[0]);
        $this->assertSame('RE-2026-000002', $again->invoice_number);
        $this->assertSame(2, Invoice::count());
        Storage::disk('local')->assertExists('invoices/2026/RE-2026-000001.pdf');
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get('invoices/2026/RE-2026-000001.pdf'));

        // Neues Jahr: Zählung beginnt neu
        $this->travelTo(now()->setDate(2027, 1, 1)->setTime(0, 5));
        $this->assertSame('RE-2027-000001', app(NumberGenerator::class)->next('invoice', 'RE'));
        $this->assertSame('AF-2027-000001', app(NumberGenerator::class)->next('order', 'AF'));
    }

    public function test_audit_log_erkennt_manipulation_und_luecken(): void
    {
        $audit = app(AuditLogger::class);
        $first = $audit->log('test.one', $this->admin, new: ['b' => 2, 'a' => 1]);
        $second = $audit->log('test.two', $this->admin, ['status' => 'alt'], ['status' => 'neu']);
        $third = $audit->log('test.three');
        $this->assertSame($first->hash, $second->prev_hash);

        $this->artisan('audit:verify')->assertSuccessful();

        // Nachträgliche Änderung eines Werts
        DB::table('audit_logs')->where('id', $second->id)->update(['new_values' => json_encode(['status' => 'gefälscht'])]);
        $this->artisan('audit:verify')
            ->expectsOutput("#{$second->id}: Hash ungültig (Eintrag wurde verändert)")
            ->assertFailed();

        // Gelöschter Eintrag bricht die Kette
        DB::table('audit_logs')->where('id', $second->id)->delete();
        $this->artisan('audit:verify')
            ->expectsOutput("#{$third->id}: Verkettung gebrochen (prev_hash passt nicht zum Vorgänger)")
            ->assertFailed();
    }
}
