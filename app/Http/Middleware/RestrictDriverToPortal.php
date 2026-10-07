<?php

namespace App\Http\Middleware;

use App\Enums\TeamRole;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps a pure driver inside the execution portal.
 *
 * A user whose role on the team is `driver` executes trips and has no business
 * in the office dashboard or the master-data lists. Owners, admins, and
 * dispatchers keep full access even though they can also drive.
 */
class RestrictDriverToPortal
{
    /**
     * Route names a driver may still reach inside the team.
     *
     * @var array<int, string>
     */
    protected const ALLOWED_ROUTES = [
        'search',
        'help',
    ];

    /**
     * Route name prefixes a driver may still reach inside the team.
     *
     * @var array<int, string>
     */
    protected const ALLOWED_PREFIXES = [
        'driver.',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $team = $this->team($request);

        if ($user === null || $team === null || $user->teamRole($team) !== TeamRole::Driver) {
            return $next($request);
        }

        $name = $request->route()?->getName();

        if ($name === 'dashboard') {
            return redirect()->route('driver.index', ['current_team' => $team->slug]);
        }

        if ($name === null || in_array($name, self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $next($request);
            }
        }

        abort(403, __('Drivers work from the driver portal.'));
    }

    /**
     * Resolve the team of the request, whether bound or still a slug.
     */
    protected function team(Request $request): ?Team
    {
        $team = $request->route('current_team');

        if (is_string($team)) {
            $team = Team::where('slug', $team)->first();
        }

        return $team instanceof Team ? $team : null;
    }
}
