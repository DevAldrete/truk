<?php

namespace App\Data;

use Closure;

/**
 * Holds the tenant that the current request, job, or command operates on.
 *
 * The context is never inferred from client input. It is set once per request
 * from the authenticated user's current team, and explicitly by background
 * work through {@see self::run()}.
 */
class TeamContext
{
    protected ?int $teamId = null;

    /**
     * Get the active team id, if any.
     */
    public function id(): ?int
    {
        return $this->teamId;
    }

    /**
     * Set the active team id.
     */
    public function set(?int $teamId): void
    {
        $this->teamId = $teamId;
    }

    /**
     * Clear the active team id.
     */
    public function forget(): void
    {
        $this->teamId = null;
    }

    /**
     * Run the callback with the given team as the active context.
     */
    public function run(int $teamId, Closure $callback): mixed
    {
        $previous = $this->teamId;

        $this->teamId = $teamId;

        try {
            return $callback();
        } finally {
            $this->teamId = $previous;
        }
    }
}
