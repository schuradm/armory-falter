<?php

namespace App\Filament\Resources\GiftVoucherResource\Pages;

use App\Filament\Resources\GiftVoucherResource;
use App\Filament\Support\Euro;
use App\Models\GiftVoucher;
use App\Services\Vouchers\GiftVoucherService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewGiftVoucher extends ViewRecord
{
    protected static string $resource = GiftVoucherResource::class;

    public function getTitle(): string
    {
        return 'Gutschein '.$this->getRecord()->formattedCode();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')->label('PDF herunterladen')->icon('heroicon-o-document-arrow-down')->color('gray')
                ->action(fn (GiftVoucher $record) => response()->streamDownload(
                    fn () => print (app(GiftVoucherService::class)->renderPdf($record)), 'Gutschein-'.$record->formattedCode().'.pdf', ['Content-Type' => 'application/pdf'])),
            Actions\Action::make('resend')->label('Erneut senden')->icon('heroicon-o-paper-airplane')->color('gray')
                ->visible(fn (GiftVoucher $record) => $record->isRedeemable() && ($record->recipient_email || $record->purchaser_id))
                ->requiresConfirmation()
                ->action(function (GiftVoucher $record) {
                    app(GiftVoucherService::class)->send($record);
                    Notification::make()->success()->title('Gutschein versendet')->send();
                }),
            // Korrektur, z. B. Einlösung im Laden oder Kulanzgutschrift; jede Buchung erscheint im Verlauf
            Actions\Action::make('adjust')->label('Guthaben buchen')->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (GiftVoucher $record) => $record->status !== 'cancelled')
                ->form([
                    Forms\Components\Select::make('direction')->label('Art')->required()->default('redeem')
                        ->options(['redeem' => 'Einlösung im Laden (abbuchen)', 'credit' => 'Gutschrift (aufbuchen)']),
                    Euro::input('amount', 'Betrag')->required(),
                    Forms\Components\TextInput::make('note')->label('Vermerk')->required()->maxLength(255)->placeholder('z. B. Kassenbon 4711'),
                ])
                ->action(function (array $data, GiftVoucher $record) {
                    $amount = (int) $data['amount'];
                    $ok = DB::transaction(function () use ($record, $data, $amount) {
                        $v = GiftVoucher::lockForUpdate()->find($record->id);
                        $delta = $data['direction'] === 'redeem' ? -$amount : $amount;
                        if ($amount <= 0 || $v->balance + $delta < 0) {
                            return false;
                        }
                        $v->balance += $delta;
                        $v->status = $v->balance === 0 ? 'redeemed' : 'active';
                        $v->save();
                        $v->transactions()->create(['type' => $data['direction'] === 'redeem' ? 'redeem' : 'adjust', 'amount' => $delta,
                            'balance_after' => $v->balance, 'note' => $data['note'], 'created_by' => auth()->id()]);

                        return true;
                    });
                    $ok ? Notification::make()->success()->title('Gebucht')->send()
                        : Notification::make()->danger()->title('Betrag übersteigt das Restguthaben')->send();
                    $this->record->refresh();
                }),
            Actions\Action::make('cancel')->label('Sperren')->icon('heroicon-o-no-symbol')->color('danger')->outlined()
                ->visible(fn (GiftVoucher $record) => $record->status === 'active')
                ->requiresConfirmation()
                ->modalDescription('Der Code kann danach nicht mehr eingelöst werden (z. B. bei Verlust nach Ersatzausstellung oder Rückabwicklung).')
                ->form([Forms\Components\TextInput::make('note')->label('Grund')->required()->maxLength(255)])
                ->action(function (array $data, GiftVoucher $record) {
                    $record->update(['status' => 'cancelled']);
                    $record->transactions()->create(['type' => 'cancel', 'amount' => 0, 'balance_after' => $record->balance, 'note' => $data['note'], 'created_by' => auth()->id()]);
                }),
        ];
    }
}
