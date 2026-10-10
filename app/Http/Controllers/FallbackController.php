<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\Request;

/** 301-Weiterleitungen (z. B. alte WordPress-URLs von armory-falter.de), sonst 404. */
class FallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = '/'.ltrim($request->path(), '/');
        $redirect = Redirect::where('from_path', $path)->orWhere('from_path', rtrim($path, '/').'/')->first();
        if ($redirect) {
            $redirect->increment('hits');

            return redirect($redirect->to_path, $redirect->status_code);
        }
        if ($path !== '/' && str_ends_with($path, '/')) {
            return redirect(rtrim($path, '/'), 301);
        }
        abort(404);
    }
}
