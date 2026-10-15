<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Löst `schedule:run` per HTTP aus (externer Cron-Dienst, z. B. cron-job.org, jede Minute),
 * falls das Shared Hosting keinen Cronjob anbietet. Geschützt durch ein geheimes Token aus CRON_TOKEN.
 */
class CronController extends Controller
{
    public function __invoke(string $token): Response
    {
        $expected = (string) config('shop.cron.token');
        // Ohne (ausreichend langes) Token ist der Endpunkt deaktiviert; falsches Token → 404 statt 403 (kein Hinweis)
        if (strlen($expected) < 32 || ! hash_equals($expected, $token)) {
            abort(404);
        }

        ignore_user_abort(true);
        if (function_exists('set_time_limit')) {
            @set_time_limit((int) config('shop.cron.queue_max_time', 50) + 60);
        }

        try {
            $exit = Artisan::call('schedule:run');
            $output = Artisan::output();
        } catch (\Throwable $e) {
            Log::error('Cron per HTTP fehlgeschlagen', ['exception' => $e]);

            return response("ERROR\n", 500, $this->headers());
        }

        return response(($exit === 0 ? "OK\n" : "EXIT {$exit}\n").$output, 200, $this->headers());
    }

    private function headers(): array
    {
        return ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex'];
    }
}
