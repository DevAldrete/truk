<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request)->value);

        return $next($request);
    }

    /**
     * Resolve the locale for the current request.
     *
     * The order matters: an explicit choice for this session wins, then the
     * user's saved preference, then the browser, then the application default.
     */
    protected function resolveLocale(Request $request): Locale
    {
        return Locale::tryFrom((string) $request->session()->get('locale'))
            ?? Locale::tryFrom((string) $request->user()?->locale)
            ?? Locale::tryFrom((string) $request->getPreferredLanguage(Locale::values()))
            ?? Locale::tryFrom((string) config('app.locale'))
            ?? Locale::En;
    }
}
