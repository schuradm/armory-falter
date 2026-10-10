<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use Illuminate\Http\Request;

/** Protokolliert Cookie-Einwilligungen (Nachweispflicht Art. 7 Abs. 1 DSGVO), pseudonym. */
class ConsentController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:64'],
            'v' => ['required', 'string', 'max:10'],
            'statistics' => ['boolean'], 'media' => ['boolean'], 'marketing' => ['boolean'],
        ]);
        ConsentRecord::create([
            'consent_id' => $data['id'],
            'user_id' => $request->user()?->id,
            'categories' => ['necessary' => true, 'statistics' => (bool) ($data['statistics'] ?? false), 'media' => (bool) ($data['media'] ?? false), 'marketing' => (bool) ($data['marketing'] ?? false)],
            'banner_version' => $data['v'],
            'ip_hash' => hash('sha256', $request->ip().config('app.key')),
        ]);

        return response()->noContent();
    }
}
