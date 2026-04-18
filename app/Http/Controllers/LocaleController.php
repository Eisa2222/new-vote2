<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LocaleController extends Controller
{
    /** Locales the UI is translated into. */
    public const SUPPORTED = ['ar', 'en'];

    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, self::SUPPORTED, true)) {
            $request->session()->put('locale', $locale);
            app()->setLocale($locale);
        }

        return redirect()->back();
    }
}
