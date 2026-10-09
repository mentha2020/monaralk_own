<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locale($request);

        App::setLocale($locale);
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);

        return $next($request);
    }

    private function locale(Request $request): string
    {
        if ($request->is('admin*')) {
            return 'en';
        }

        $supported = config('app.supported_locales', ['en']);

        $candidate = $request->session()->get('locale') ?? $request->cookies->get('locale');

        if (is_string($candidate) && in_array($candidate, $supported, true)) {
            return $candidate;
        }

        return (string) config('app.locale');
    }
}
