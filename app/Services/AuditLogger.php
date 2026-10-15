<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Revisionssicheres Audit-Log mit Hash-Kette: jeder Eintrag enthält den Hash des vorherigen.
 * Eine nachträgliche Änderung bricht die Kette und wird von `php artisan audit:verify` erkannt.
 */
class AuditLogger
{
    public function log(string $action, ?Model $subject = null, array $old = [], array $new = [], ?string $actorType = null): AuditLog
    {
        return DB::transaction(function () use ($action, $subject, $old, $new, $actorType) {
            $prev = AuditLog::query()->latest('id')->lockForUpdate()->value('hash');
            $user = Auth::user();
            $payload = [
                'actor_id' => $user?->id,
                'actor_type' => $actorType ?? ($user ? ($user->is_staff ? 'admin' : 'user') : 'system'),
                'action' => $action,
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? 'cli' : substr((string) request()->userAgent(), 0, 255),
                'prev_hash' => $prev,
                'created_at' => now()->format('Y-m-d H:i:s.v'),
            ];
            $payload['hash'] = self::hash($payload);

            return AuditLog::create($payload);
        });
    }

    public static function hash(array $payload): string
    {
        $fields = ['actor_id', 'actor_type', 'action', 'subject_type', 'subject_id', 'old_values', 'new_values', 'prev_hash', 'created_at'];
        $data = [];
        foreach ($fields as $f) {
            $v = $payload[$f] ?? null;
            $data[$f] = is_array($v) ? json_encode(self::canonical($v), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $v;
        }

        return hash_hmac('sha256', implode('|', $data), config('app.key'));
    }

    /** Schlüssel rekursiv sortieren: MySQL speichert JSON-Objekte mit eigener Schlüsselreihenfolge. */
    private static function canonical(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(fn ($v) => is_array($v) ? self::canonical($v) : $v, $value);
    }
}
