<?php

namespace App\Filament\Widgets;

use App\Enums\ComplianceStatus;
use App\Enums\DocumentStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\AppointmentResource;
use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\CustomerDocumentResource;
use App\Filament\Resources\OrderResource;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\CustomerDocument;
use App\Models\Order;
use App\Models\TradeRequest;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/** Kennzahlen für den Tagesstart: wenige, indexfreundliche Aggregat-Abfragen statt Einzelzählungen pro Kachel. */
class ShopStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $month = now()->startOfMonth();
        $excluded = [OrderStatus::Cancelled->value, OrderStatus::Refunded->value];

        // Eine Abfrage für alle Bestellkennzahlen: nur Bestellungen dieses Monats oder mit offener Freigabe.
        $orders = Order::query()
            ->where(fn ($q) => $q->where('placed_at', '>=', $month)->orWhere('compliance_status', ComplianceStatus::Pending->value))
            ->selectRaw('SUM(CASE WHEN placed_at >= ? THEN 1 ELSE 0 END) AS today_count', [$today])
            ->selectRaw('SUM(CASE WHEN placed_at >= ? AND status NOT IN (?, ?) THEN total_gross ELSE 0 END) AS today_revenue', [$today, ...$excluded])
            ->selectRaw('SUM(CASE WHEN compliance_status = ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) AS pending_compliance', [ComplianceStatus::Pending->value, ...$excluded])
            ->selectRaw('SUM(CASE WHEN placed_at >= ? AND status NOT IN (?, ?) THEN total_gross ELSE 0 END) AS month_revenue', [$month, ...$excluded])
            ->selectRaw('SUM(CASE WHEN placed_at >= ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) AS month_count', [$month, ...$excluded])
            ->toBase()->first();

        $documents = CustomerDocument::query()
            ->whereIn('status', [DocumentStatus::Uploaded->value, DocumentStatus::PrecheckPassed->value, DocumentStatus::PrecheckFlagged->value])
            ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS flagged', [DocumentStatus::PrecheckFlagged->value])
            ->toBase()->first();

        $requests = DB::query()
            ->selectSub(ContactMessage::query()->where('status', 'new')->selectRaw('COUNT(*)'), 'contact')
            ->selectSub(TradeRequest::query()->where('status', 'new')->selectRaw('COUNT(*)'), 'trade')
            ->selectSub(Appointment::query()->where('status', 'requested')->selectRaw('COUNT(*)'), 'appointments')
            ->first();

        $monthCount = (int) ($orders->month_count ?? 0);
        $pending = (int) ($orders->pending_compliance ?? 0);
        $docTotal = (int) ($documents->total ?? 0);
        $newRequests = (int) $requests->contact + (int) $requests->trade + (int) $requests->appointments;

        return [
            Stat::make('Bestellungen heute', (string) (int) ($orders->today_count ?? 0))
                ->description('Umsatz heute: '.Money::format((int) ($orders->today_revenue ?? 0)))
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->url(self::resourceUrl(OrderResource::class)),
            Stat::make('Offene Compliance-Freigaben', (string) $pending)
                ->description($pending ? 'Bestellungen warten auf Prüfung' : 'Alles freigegeben')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($pending ? 'warning' : 'success')
                ->url(self::resourceUrl(OrderResource::class)),
            Stat::make('Zu prüfende Dokumente', (string) $docTotal)
                ->description(((int) ($documents->flagged ?? 0)).' mit auffälliger Vorprüfung')
                ->descriptionIcon('heroicon-m-identification')
                ->color($docTotal ? 'warning' : 'success')
                ->url(self::resourceUrl(CustomerDocumentResource::class)),
            Stat::make('Umsatz '.now()->translatedFormat('F'), Money::format((int) ($orders->month_revenue ?? 0)))
                ->description($monthCount.' '.($monthCount === 1 ? 'Bestellung' : 'Bestellungen').', ohne Stornos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Neue Anfragen', (string) $newRequests)
                ->description("{$requests->contact} Kontakt · {$requests->trade} Ankauf/Verkauf · {$requests->appointments} Termine")
                ->descriptionIcon('heroicon-m-inbox')
                ->color($newRequests ? 'warning' : 'gray')
                ->url(self::resourceUrl((int) $requests->contact ? ContactMessageResource::class : AppointmentResource::class)),
        ];
    }

    /** @param class-string<\Filament\Resources\Resource> $resource */
    private static function resourceUrl(string $resource): ?string
    {
        try {
            return class_exists($resource) && $resource::canViewAny() ? $resource::getUrl('index') : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
