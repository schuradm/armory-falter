<?php

namespace App\Support;

use App\Http\Middleware\PageCache;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Backend-pflegbare Einstellungen mit Fallback auf config/shop.php.
 * Ein Cache-Eintrag für alle Werte → kein DB-Zugriff pro Seitenaufruf.
 */
class Settings
{
    private ?array $values = null;

    public function all(): array
    {
        return $this->values ??= Cache::rememberForever('settings.v1', function () {
            try {
                return Setting::pluck('value', 'key')->all();
            } catch (Throwable) {
                return []; // z. B. vor der ersten Migration
            }
        });
    }

    /** Punkt-Notation: get('home.hero.headline'). */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Arr::get($this->all(), $key);

        return ($value === null || $value === '' || $value === []) ? $default : $value;
    }

    /**
     * Startseiteninhalte: Standardwerte aus config/shop.php rekursiv überschrieben.
     * Listen (Kennzahlen, USPs, Schritte) ersetzt das Backend komplett, statt sie elementweise zu mischen.
     */
    public function home(): array
    {
        $saved = (array) $this->get('home', []);
        $home = array_replace_recursive((array) config('shop.home'), $saved);
        foreach (['stats', 'usps', 'steps'] as $list) {
            if (isset($saved[$list]) && is_array($saved[$list])) {
                $home[$list] = array_values($saved[$list]);
            }
        }

        return $home;
    }

    public function set(string $key, mixed $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $this->forget();
    }

    public function forget(): void
    {
        $this->values = null;
        Cache::forget('settings.v1');
        PageCache::flush();
    }
}
