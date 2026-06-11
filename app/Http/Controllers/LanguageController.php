<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    private const LOCALE_COOKIE = 'locale';

    private const LOCALE_COOKIE_DAYS = 365;

    public function switch(Request $request)
    {
        $locale = $request->input('locale');
        $enabledLanguages = config('qrcode.enabled_languages', ['en', 'es']);

        if (! in_array($locale, $enabledLanguages, true)) {
            return back();
        }

        Session::put('locale', $locale);

        if ($request->user()) {
            $request->user()->update(['locale' => $locale]);
        }

        $cookie = Cookie::make(
            self::LOCALE_COOKIE,
            $locale,
            self::LOCALE_COOKIE_DAYS * 24 * 60,
            '/',
            config('session.domain'),
            false,
            true,
            false,
            'lax'
        );

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return back()->cookie($cookie);
    }
}
