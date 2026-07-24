<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'tr'));

        if (! in_array($locale, ['tr', 'en'], true)) {
            $locale = 'tr';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
