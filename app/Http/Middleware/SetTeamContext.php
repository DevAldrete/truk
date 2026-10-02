<?php

namespace App\Http\Middleware;

use App\Data\TeamContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTeamContext
{
    public function __construct(protected TeamContext $context) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->context->set($request->user()?->current_team_id);

        return $next($request);
    }
}
