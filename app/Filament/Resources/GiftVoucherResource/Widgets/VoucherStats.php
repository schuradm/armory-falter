<?php

namespace App\Filament\Resources\GiftVoucherResource\Widgets;

use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Models\GiftVoucher;
use App\Models\GiftVoucherTransaction;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Kennzahlen zu Gutscheinen und Rabattcodes (nur auf den Listen, nicht im Dashboard).
 * Offenes Guthaben = Verbindlichkeit gegenüber Gutscheininhabern (Bilanz), abgelaufene getrennt ausgewiesen.
 */
class VoucherStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $year = now()->startOfYear();
        $open = GiftVoucher::query()->where('status', 'active')
            ->selectRaw('SUM(CASE WHEN expires_at >= ? THEN balance ELSE 0 END) AS open_balance', [today()->toDateString()])
            ->selectRaw('SUM(CASE WHEN expires_at >= ? AND balance > 0 THEN 1 ELSE 0 END) AS open_count', [today()->toDateString()])
            ->selectRaw('SUM(CASE WHEN expires_at < ? THEN balance ELSE 0 END) AS expired_balance', [today()->toDateString()])
            ->toBase()->first();
        $tx = GiftVoucherTransaction::query()->where('created_at', '>=', $year)
            ->selectRaw("SUM(CASE WHEN type = 'issue' THEN amount ELSE 0 END) AS issued")
            ->selectRaw("SUM(CASE WHEN type IN ('redeem', 'release') THEN -amount ELSE 0 END) AS redeemed")
            ->toBase()->first();
        $discount = DiscountCodeRedemption::where('status', 'active')->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('COUNT(*) AS c, COALESCE(SUM(amount_gross), 0) AS s')->toBase()->first();

        return [
            Stat::make('Offenes Gutscheinguthaben', Money::format((int) ($open->open_balance ?? 0)))
                ->description(((int) ($open->open_count ?? 0)).' gültige Gutscheine mit Restguthaben')
                ->descriptionIcon('heroicon-m-gift')->color('primary'),
            Stat::make('Ausgegeben '.now()->year, Money::format((int) ($tx->issued ?? 0)))
                ->description('Eingelöst '.now()->year.': '.Money::format((int) ($tx->redeemed ?? 0)))
                ->descriptionIcon('heroicon-m-arrow-path'),
            Stat::make('Abgelaufenes Guthaben', Money::format((int) ($open->expired_balance ?? 0)))
                ->description('Verjährt, nicht mehr einlösbar')->descriptionIcon('heroicon-m-clock')->color('gray'),
            Stat::make('Rabatt '.now()->translatedFormat('F'), Money::format((int) $discount->s))
                ->description(((int) $discount->c).' Einlösungen · '.DiscountCode::where('is_active', true)->count().' aktive Codes')
                ->descriptionIcon('heroicon-m-receipt-percent'),
        ];
    }
}
