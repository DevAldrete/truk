<?php

use App\Http\Middleware\EnsureTeamMembership;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
        ]);

        // The team of the URL is resolved before route model binding, so that
        // tenanted models are bound (or rejected) inside the right tenant.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureTeamMembership::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // A body larger than `post_max_size` is discarded by PHP before any
        // validation runs. This fires before the session middleware, so we
        // cannot flash a message; return a plain 413 and let the client error
        // handler explain it in the user's language.
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if (! $request->header('X-Inertia')) {
                return null;
            }

            return response()->json([
                'message' => __('The file is too large to upload. Please choose a smaller file.'),
            ], 413);
        });
    })->create();
