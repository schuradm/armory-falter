<?php

namespace App\Support;

use App\Http\Middleware\PageCache;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
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
