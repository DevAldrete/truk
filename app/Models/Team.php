<?php

namespace App\Models;

use App\Concerns\GeneratesUniqueTeamSlugs;
use App\Enums\TeamRole;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property bool $is_personal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, TeamInvitation> $invitations
 * @property-read Collection<int, Membership> $memberships
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Driver> $drivers
 * @property-read Collection<int, Vehicle> $vehicles
 * @property-read Collection<int, Trailer> $trailers
 * @property-read Collection<int, ComplianceDocument> $complianceDocuments
 * @property-read Collection<int, Order> $orders
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Collection<int, Shipment> $shipments
 * @property-read Collection<int, ShipmentItem> $shipmentItems
 * @property-read Collection<int, Package> $packages
 * @property-read Collection<int, Load> $loads
 * @property-read Collection<int, Trip> $trips
 * @property-read Collection<int, TripAssignment> $tripAssignments
 * @property-read Collection<int, Stop> $stops
 * @property-read Collection<int, StopShipment> $stopShipments
 * @property-read Collection<int, DeliveryAttempt> $deliveryAttempts
 * @property-read Collection<int, DeliveryAttemptLine> $deliveryAttemptLines
 */
#[Fillable(['name', 'slug', 'is_personal'])]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use GeneratesUniqueTeamSlugs, HasFactory, SoftDeletes;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = static::generateUniqueTeamSlug($team->name);
            }
        });

        static::updating(function (Team $team) {
            if ($team->isDirty('name')) {
                $team->slug = static::generateUniqueTeamSlug($team->name, $team->id);
            }
        });
    }

    /**
     * Get the team owner.
     */
    public function owner(): ?Model
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Owner->value)
            ->first();
    }

    /**
     * Get all members of this team.
     *
     * @return BelongsToMany<User, $this, Membership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members', 'team_id', 'user_id')
            ->using(Membership::class)
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Get all memberships for this team.
     *
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Get all invitations for this team.
     *
     * @return HasMany<TeamInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    /**
     * Get all parties (customers, carriers, suppliers) of this team.
     *
     * @return HasMany<Party, $this>
     */
    public function parties(): HasMany
    {
        return $this->hasMany(Party::class);
    }

    /**
     * Get all pickup and delivery sites of this team.
     *
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get all party contacts of this team.
     *
     * @return HasMany<PartyContact, $this>
     */
    public function partyContacts(): HasMany
    {
        return $this->hasMany(PartyContact::class);
    }

    /**
     * Get all drivers registered by this team, own fleet or subcontracted.
     *
     * @return HasMany<Driver, $this>
     */
    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    /**
     * Get all motorised units of this team, own fleet or subcontracted.
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all non-motorised units of this team, own fleet or subcontracted.
     *
     * @return HasMany<Trailer, $this>
     */
    public function trailers(): HasMany
    {
        return $this->hasMany(Trailer::class);
    }

    /**
     * Get all compliance documents of this team.
     *
     * @return HasMany<ComplianceDocument, $this>
     */
    public function complianceDocuments(): HasMany
    {
        return $this->hasMany(ComplianceDocument::class);
    }

    /**
     * Get all orders created by this team.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get all order lines of this team.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all shipments of this team.
     *
     * @return HasMany<Shipment, $this>
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * Get all shipment lines of this team.
     *
     * @return HasMany<ShipmentItem, $this>
     */
    public function shipmentItems(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Get all packages of this team.
     *
     * @return HasMany<Package, $this>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * Get all loads of this team.
     *
     * @return HasMany<Load, $this>
     */
    public function loads(): HasMany
    {
        return $this->hasMany(Load::class);
    }

    /**
     * Get all trips of this team.
     *
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get all trip assignment history rows of this team.
     *
     * @return HasMany<TripAssignment, $this>
     */
    public function tripAssignments(): HasMany
    {
        return $this->hasMany(TripAssignment::class);
    }

    /**
     * Get all stops of this team.
     *
     * @return HasMany<Stop, $this>
     */
    public function stops(): HasMany
    {
        return $this->hasMany(Stop::class);
    }

    /**
     * Get all stop-to-shipment links of this team.
     *
     * @return HasMany<StopShipment, $this>
     */
    public function stopShipments(): HasMany
    {
        return $this->hasMany(StopShipment::class);
    }

    /**
     * Get all delivery attempts of this team.
     *
     * @return HasMany<DeliveryAttempt, $this>
     */
    public function deliveryAttempts(): HasMany
    {
        return $this->hasMany(DeliveryAttempt::class);
    }

    /**
     * Get all delivery attempt lines of this team.
     *
     * @return HasMany<DeliveryAttemptLine, $this>
     */
    public function deliveryAttemptLines(): HasMany
    {
        return $this->hasMany(DeliveryAttemptLine::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_personal' => 'boolean',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
