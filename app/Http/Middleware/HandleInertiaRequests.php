<?php

namespace App\Http\Middleware;

use App\Enums\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'locale' => app()->getLocale(),
            'availableLocales' => Locale::options(),
            'uploadLimits' => [
                'maxKilobytes' => config('uploads.max_kilobytes'),
                'maxSignatureKilobytes' => config('uploads.signature_max_kilobytes'),
                'maxFiles' => 10,
            ],
            'translations' => fn (): array => $this->translations(),
        ];
    }

    /**
     * Get the interface strings for the active locale.
     *
     * @return array<string, string>
     */
    protected function translations(): array
    {
        $path = lang_path(app()->getLocale().'.json');

        if (! File::exists($path)) {
            return [];
        }

        /** @var array<string, string>|null $translations */
        $translations = json_decode(File::get($path), true);

        return $translations ?? [];
    }
}
