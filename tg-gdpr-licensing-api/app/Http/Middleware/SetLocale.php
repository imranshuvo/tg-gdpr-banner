<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supported = array_keys(config('locales.supported', []));
        $fallback  = config('app.fallback_locale', 'en');

        $locale = $request->session()->get('locale')
            ?? $request->query('lang')
            ?? $this->fromAcceptLanguage($request, $supported)
            ?? $fallback;

        if (! in_array($locale, $supported, true)) {
            $locale = $fallback;
        }

        App::setLocale($locale);

        return $next($request);
    }

    private function fromAcceptLanguage(Request $request, array $supported): ?string
    {
        $header = $request->header('Accept-Language');
        if (! $header) {
            return null;
        }

        foreach (explode(',', $header) as $entry) {
            $tag = strtolower(trim(explode(';', $entry)[0]));
            $primary = substr($tag, 0, 2);
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return null;
    }
}
