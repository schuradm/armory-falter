<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Schlüssel und Invalidierung für CachePublicPages.
 * Der Host gehört in den Schlüssel: Seiten enthalten absolute URLs (CSS, Canonical), sonst bekäme www.… die Seite von armory-falter.de oder der LAN-IP.
 */
final class PageCache
{
    public static function key(Request $request): string
    {
        $query = $request->query();
        ksort($query);

        return 'page:'.Cache::get('page-cache-version', 1).':'.sha1($request->getSchemeAndHttpHost().'/'.$request->path().'?'.http_build_query($query));
    }

    public static function flush(): void
    {
        Cache::forever('page-cache-version', ((int) Cache::get('page-cache-version', 1)) + 1);
        Cache::forget('nav.v1');
    }
}
