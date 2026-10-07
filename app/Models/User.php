<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Data\TeamContext;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $locale
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team|null $currentTeam
 * @property-read Collection<int, Team> $ownedTeams
 * @property-read Collection<int, Membership> $teamMemberships
 * @property-read Collection<int, Team> $teams
 * @property-read Collection<int, Driver> $driverProfiles
 */
#[Fillable(['name', 'username', 'email', 'password', 'locale', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasTeams, Notifiable;

    /**
     * Determine whether the user has a verified email.
     *
     * A staff account created by an organization may have no email at all; it
     * is treated as verified so the `verified` middleware never blocks it.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email === null || parent::hasVerifiedEmail();
    }

    /**
     * Get the driver profiles linked to this login, across every team.
     *
     * @return HasMany<Driver, $this>
     */
    public function driverProfiles(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get the driver profile linked to this login within the given team.
     */
    public function driverProfileFor(Team $team): ?Driver
    {
        return app(TeamContext::class)->run(
            $team->id,
            fn (): ?Driver => $this->driverProfiles()->where('team_id', $team->id)->first(),
        );
    }

    /**
     * Get the locale that mail and notifications should be rendered in.
     */
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
