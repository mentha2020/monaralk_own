<?php

namespace App\Modules\Shared\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class LanguageController
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('app.supported_locales', []), true), 404);

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        Cookie::queue('locale', $locale, 60 * 24 * 365);

        return back(fallback: route('home'));
    }
}
