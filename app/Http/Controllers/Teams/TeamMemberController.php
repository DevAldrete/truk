<?php

namespace App\Http\Controllers\Teams;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateTeamMemberRequest;
use App\Http\Requests\Teams\UpdateTeamMemberRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TeamMemberController extends Controller
{
    /**
     * Create a staff account and add it to the team with the given role.
     */
    public function store(CreateTeamMemberRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('addMember', $team);

        $user = User::create([
            'name' => $request->validated('name'),
            'username' => $request->validated('login'),
            'email' => null,
            'password' => $request->validated('password'),
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $team->memberships()->create([
            'user_id' => $user->id,
            'role' => TeamRole::from($request->validated('role')),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was created.', ['name' => $user->name])]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Update the specified team member's role.
     */
    public function update(UpdateTeamMemberRequest $request, Team $team, User $user): RedirectResponse
    {
        Gate::authorize('updateMember', $team);

        $newRole = TeamRole::from($request->validated('role'));

        $membership = $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->guardRoleChange($request, $team, $membership, $newRole);

        $membership->update(['role' => $newRole]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Remove the specified team member.
     */
    public function destroy(Team $team, User $user): RedirectResponse
    {
        Gate::authorize('removeMember', $team);

        abort_if($team->owner()?->is($user), 403, __('The organization owner cannot be removed.'));

        $membership = $team->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($this->isLastAdministrator($team, $membership)) {
            throw ValidationException::withMessages([
                'role' => __('The organization must keep at least one owner or admin.'),
            ]);
        }

        $membership->delete();

        if ($user->isCurrentTeam($team)) {
            $user->switchTeam($user->personalTeam());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member removed.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Refuse a role change that would leave the organization without an owner
     * or an admin, or that targets the owner or the acting user themselves.
     */
    protected function guardRoleChange(
        UpdateTeamMemberRequest $request,
        Team $team,
        Membership $membership,
        TeamRole $newRole,
    ): void {
        if ($team->owner()?->is($membership->user)) {
            throw ValidationException::withMessages([
                'role' => __('The organization owner role cannot be changed.'),
            ]);
        }

        if ($request->user()?->is($membership->user)) {
            throw ValidationException::withMessages([
                'role' => __('You cannot change your own role.'),
            ]);
        }

        if (! $newRole->isAtLeast(TeamRole::Admin) && $this->isLastAdministrator($team, $membership)) {
            throw ValidationException::withMessages([
                'role' => __('The organization must keep at least one owner or admin.'),
            ]);
        }
    }

    /**
     * Determine whether this membership is the team's only administrative one.
     */
    protected function isLastAdministrator(Team $team, Membership $membership): bool
    {
        if (! $membership->role->isAtLeast(TeamRole::Admin)) {
            return false;
        }

        return $team->memberships()
            ->whereIn('role', [TeamRole::Owner->value, TeamRole::Admin->value])
            ->count() <= 1;
    }
}
