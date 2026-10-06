<?php

namespace Database\Seeders;

use App\Actions\Execution\RecordDeliveryAttempt;
use App\Actions\Execution\RecordExpense;
use App\Actions\Execution\RecordProofOfDelivery;
use App\Actions\Execution\RecordScan;
use App\Actions\Execution\ReportIncident;
use App\Actions\Execution\UpdateStopStatus;
use App\Actions\Loads\SaveLoad;
use App\Actions\Orders\ConvertOrderToShipment;
use App\Actions\Orders\SaveOrder;
use App\Actions\Trips\AssignTripResources;
use App\Actions\Trips\SaveStop;
use App\Data\TeamContext;
use App\Enums\ComplianceDocumentType;
use App\Enums\ExpenseType;
use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Enums\LoadStatus;
use App\Enums\OrderStatus;
use App\Enums\PartyType;
use App\Enums\ScanType;
use App\Enums\StopStatus;
use App\Enums\StopType;
use App\Enums\TeamRole;
use App\Enums\TripStatus;
use App\Models\Driver;
use App\Models\Location;
use App\Models\Order;
use App\Models\Party;
use App\Models\Shipment;
use App\Models\Stop;
use App\Models\Team;
use App\Models\Trailer;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds one operating company with a realistic, connected dataset: customers,
 * sites, fleet, orders turned into shipments and packages, loads, dispatched
 * trips with execution evidence, incidents, and expenses.
 *
 * Every record goes through the real domain actions, so statuses are derived the
 * same way they are at runtime. Run it with `composer db:reset`.
 */
class DemoSeeder extends Seeder
{
    /**
     * A tiny PNG used as the demo signature so PODs have real evidence.
     */
    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $team = Team::factory()->create([
            'name' => 'Transportes del Norte',
            'slug' => 'transportes-del-norte',
            'is_personal' => false,
        ]);

        $users = $this->seedUsers($team);

        app(TeamContext::class)->run($team->id, function () use ($team, $users): void {
            DB::transaction(function () use ($team, $users): void {
                $parties = $this->seedParties($team);
                $locations = $this->seedLocations($team, $parties);
                $fleet = $this->seedFleet($team, $parties, $users);
                $orders = $this->seedOrders($team, $parties, $locations);
                $shipments = $this->seedShipments($team, $orders, $locations);
                $this->seedLoads($team, $shipments);
                $this->seedTrips($team, $fleet, $shipments, $users);
            });
        });

        $this->command->info('Seeded "Transportes del Norte".');
        $this->command->info('Log in with owner@truk.test / dispatcher@truk.test / driver@truk.test (password: password).');
    }

    /**
     * Create the demo logins and attach them to the team.
     *
     * @return array<string, User>
     */
    protected function seedUsers(Team $team): array
    {
        $definitions = [
            'owner' => ['Ana Gómez', 'owner@truk.test', TeamRole::Owner],
            'dispatcher' => ['Luis Ramírez', 'dispatcher@truk.test', TeamRole::Dispatcher],
            'driver' => ['Juan Pérez', 'driver@truk.test', TeamRole::Driver],
            'warehouse' => ['Marta Sánchez', 'warehouse@truk.test', TeamRole::Warehouse],
        ];

        $users = [];

        foreach ($definitions as $key => [$name, $email, $role]) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
                'locale' => 'es',
            ]);

            $team->members()->attach($user, ['role' => $role->value]);
            $user->switchTeam($team);

            $users[$key] = $user;
        }

        return $users;
    }

    /**
     * Create customers, carriers, and a fuel supplier with a contact each.
     *
     * @return array<string, Party>
     */
    protected function seedParties(Team $team): array
    {
        $definitions = [
            'cemex' => [PartyType::Customer, 'Cemex México', 'CEMEX México, S.A. de C.V.', 'CEM250101AB1', 'logistica@cemex.test', '8180001000', 'Roberto Díaz', 'Coordinador de logística'],
            'bimbo' => [PartyType::Customer, 'Grupo Bimbo', 'Grupo Bimbo, S.A.B. de C.V.', 'BIM640101CD2', 'trafico@bimbo.test', '5550002000', 'Paola Núñez', 'Jefa de tráfico'],
            'soriana' => [PartyType::Customer, 'Organización Soriana', 'Organización Soriana, S.A.B. de C.V.', 'SOR680101EF3', 'transporte@soriana.test', '8710003000', 'Héctor Vázquez', 'Gerente de transporte'],
            'femsa' => [PartyType::Customer, 'Coca-Cola FEMSA', 'Coca-Cola FEMSA, S.A.B. de C.V.', 'FEM910101GH4', 'distribucion@femsa.test', '8180004000', 'Sofía Castro', 'Planeadora'],
            'heineken' => [PartyType::Customer, 'Heineken México', 'Cervecería Cuauhtémoc Moctezuma, S.A. de C.V.', 'CCM900101IJ5', 'logistica@heineken.test', '8180005000', 'Diego Flores', 'Analista de logística'],
            'castores' => [PartyType::Carrier, 'Transportes Castores', 'Transportes Castores de Baja California, S.A. de C.V.', 'TCA850101KL6', 'operaciones@castores.test', '4770006000', 'Ernesto Salas', 'Coordinador de flota'],
            'diesel' => [PartyType::Supplier, 'Diesel del Norte', 'Diesel del Norte, S.A. de C.V.', 'DNO120101MN7', 'ventas@diesel.test', '8180007000', 'Verónica Peña', 'Ejecutiva de cuenta'],
        ];

        $parties = [];

        foreach ($definitions as $key => [$type, $name, $legal, $rfc, $email, $phone, $contact, $position]) {
            $party = Party::factory()->for($team)->type($type)->create([
                'name' => $name,
                'legal_name' => $legal,
                'rfc' => $rfc,
                'email' => $email,
                'phone' => $phone,
            ]);

            $team->partyContacts()->create([
                'party_id' => $party->id,
                'name' => $contact,
                'position' => $position,
                'email' => Str::slug($contact, '.').'@'.$key.'.test',
                'phone' => $phone,
            ]);

            $parties[$key] = $party;
        }

        return $parties;
    }

    /**
     * Create warehouses and customer sites across Mexico with coordinates.
     *
     * @param  array<string, Party>  $parties
     * @return array<string, Location>
     */
    protected function seedLocations(Team $team, array $parties): array
    {
        $definitions = [
            'cedis_mty' => [null, 'CEDIS Transportes del Norte', 'Av. Lázaro Cárdenas', '2400', 'Nueva Industrial Vallejo', 'Monterrey', 'Nuevo León', '64500', 25.6866, -100.3161, 'America/Monterrey'],
            'cedis_gdl' => [null, 'Patio Guadalajara', 'Av. López Mateos Sur', '5150', 'Ciudad del Sol', 'Guadalajara', 'Jalisco', '45050', 20.6597, -103.3496, 'America/Mexico_City'],
            'planta_cemex_mty' => ['cemex', 'Planta Cemex Monterrey', 'Carretera Miguel Alemán', '1000', 'Las Torres', 'Monterrey', 'Nuevo León', '64500', 25.7290, -100.2410, 'America/Monterrey'],
            'obra_cdmx' => ['cemex', 'Obra Torre Reforma CDMX', 'Paseo de la Reforma', '505', 'Cuauhtémoc', 'Ciudad de México', 'Ciudad de México', '06500', 19.4326, -99.1332, 'America/Mexico_City'],
            'cedis_bimbo_cdmx' => ['bimbo', 'CEDIS Bimbo Azcapotzalco', 'Calzada San Juan de Aragón', '200', 'Santa María Ticomán', 'Ciudad de México', 'Ciudad de México', '07330', 19.5040, -99.1520, 'America/Mexico_City'],
            'cedis_soriana_trc' => ['soriana', 'CEDIS Soriana Torreón', 'Boulevard Independencia', '3200', 'Las Huertas', 'Torreón', 'Coahuila', '27000', 25.5439, -103.4070, 'America/Monterrey'],
            'sucursal_mty' => ['soriana', 'Soriana Hiper Valle Oriente', 'Av. Lázaro Cárdenas', '1000', 'Valle Oriente', 'San Pedro Garza García', 'Nuevo León', '66260', 25.6522, -100.3510, 'America/Monterrey'],
            'planta_femsa_mty' => ['femsa', 'Planta Coca-Cola FEMSA Monterrey', 'Av. Constitución', '444', 'Fierro', 'Monterrey', 'Nuevo León', '64000', 25.7000, -100.3300, 'America/Monterrey'],
            'centro_gdl' => ['femsa', 'Centro de Distribución Guadalajara', 'Av. Colón', '1500', 'Moderna', 'Guadalajara', 'Jalisco', '44190', 20.6800, -103.3600, 'America/Mexico_City'],
            'bodega_heineken_mty' => ['heineken', 'Bodega Heineken Monterrey', 'Av. Adolfo Ruiz Cortines', '800', 'Roma', 'Monterrey', 'Nuevo León', '64500', 25.6900, -100.2900, 'America/Monterrey'],
            'centro_qro' => ['heineken', 'Centro de Distribución Querétaro', 'Av. 5 de Febrero', '1200', 'Zapata', 'Querétaro', 'Querétaro', '76030', 20.5888, -100.3899, 'America/Mexico_City'],
            'centro_ver' => ['soriana', 'Bodega Veracruz', 'Av. Ruiz Cortines', '500', 'Costa Verde', 'Veracruz', 'Veracruz', '91700', 19.1738, -96.1342, 'America/Mexico_City'],
        ];

        $locations = [];

        foreach ($definitions as $key => [$partyKey, $name, $street, $exterior, $neighborhood, $city, $state, $postalCode, $latitude, $longitude, $timezone]) {
            $locations[$key] = $team->locations()->create([
                'party_id' => $partyKey === null ? null : $parties[$partyKey]->id,
                'name' => $name,
                'street' => $street,
                'exterior_number' => $exterior,
                'neighborhood' => $neighborhood,
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'timezone' => $timezone,
            ]);
        }

        return $locations;
    }

    /**
     * Create the drivers, vehicles, trailers, and their compliance documents.
     *
     * @param  array<string, Party>  $parties
     * @param  array<string, User>  $users
     * @return array{drivers: array<string, Driver>, vehicles: array<string, Vehicle>, trailers: array<string, Trailer>}
     */
    protected function seedFleet(Team $team, array $parties, array $users): array
    {
        $drivers = [
            'juan' => $team->drivers()->create([
                'user_id' => $users['driver']->id,
                'name' => 'Juan Pérez',
                'phone' => '8112345678',
                'license_number' => 'NL-1234567',
                'license_expires_at' => now()->addMonths(14)->toDateString(),
            ]),
            'miguel' => $team->drivers()->create([
                'name' => 'Miguel Hernández',
                'phone' => '8198765432',
                'license_number' => 'NL-7654321',
                'license_expires_at' => now()->addDays(20)->toDateString(),
            ]),
            'carlos' => $team->drivers()->create([
                'carrier_party_id' => $parties['castores']->id,
                'name' => 'Carlos Ruiz',
                'phone' => '4421234567',
                'license_number' => 'QRO-1122334',
                'license_expires_at' => now()->subDays(5)->toDateString(),
            ]),
        ];

        $vehicles = [
            't101' => $team->vehicles()->create([
                'name' => 'T-101',
                'plate' => 'NL-12345',
                'configuration' => 'T3S2',
                'max_payload_grams' => 40_000_000,
                'max_volume_cm3' => 90_000_000,
            ]),
            't102' => $team->vehicles()->create([
                'name' => 'T-102',
                'plate' => 'NL-67890',
                'configuration' => 'T3S2',
                'max_payload_grams' => 38_000_000,
                'max_volume_cm3' => 85_000_000,
            ]),
            't103' => $team->vehicles()->create([
                'carrier_party_id' => $parties['castores']->id,
                'name' => 'T-103',
                'plate' => 'QRO-24680',
                'configuration' => 'C3',
                'max_payload_grams' => 24_000_000,
                'max_volume_cm3' => 60_000_000,
            ]),
        ];

        $trailers = [
            'r201' => $team->trailers()->create([
                'name' => 'R-201',
                'plate' => 'NL-55001',
                'configuration' => 'Caja seca',
                'max_payload_grams' => 30_000_000,
                'max_volume_cm3' => 100_000_000,
            ]),
            'r202' => $team->trailers()->create([
                'name' => 'R-202',
                'plate' => 'NL-55002',
                'configuration' => 'Plataforma',
                'max_payload_grams' => 32_000_000,
            ]),
            'r203' => $team->trailers()->create([
                'name' => 'R-203',
                'plate' => 'NL-55003',
                'configuration' => 'Refrigerado',
                'max_payload_grams' => 28_000_000,
                'max_volume_cm3' => 95_000_000,
            ]),
        ];

        $this->seedComplianceDocuments($team, $drivers, $vehicles, $trailers);

        return compact('drivers', 'vehicles', 'trailers');
    }

    /**
     * Attach legal documents, including a couple that need attention.
     *
     * @param  array<string, Driver>  $drivers
     * @param  array<string, Vehicle>  $vehicles
     * @param  array<string, Trailer>  $trailers
     */
    protected function seedComplianceDocuments(Team $team, array $drivers, array $vehicles, array $trailers): void
    {
        foreach ($drivers as $driver) {
            $this->document($team, $driver, ComplianceDocumentType::License, now()->addMonths(14));
            $this->document($team, $driver, ComplianceDocumentType::Insurance, now()->addMonths(3));
        }

        $this->document($team, $drivers['miguel'], ComplianceDocumentType::License, now()->addDays(20));

        foreach ($vehicles as $vehicle) {
            $this->document($team, $vehicle, ComplianceDocumentType::Verification, now()->addMonths(8));
            $this->document($team, $vehicle, ComplianceDocumentType::Insurance, now()->addMonths(6));
        }

        $this->document($team, $vehicles['t102'], ComplianceDocumentType::Verification, now()->subDays(2));

        foreach ($trailers as $trailer) {
            $this->document($team, $trailer, ComplianceDocumentType::Inspection, now()->addMonths(10));
        }
    }

    /**
     * Attach one compliance document to a documentable resource.
     */
    protected function document(Team $team, Driver|Vehicle|Trailer $resource, ComplianceDocumentType $type, \DateTimeInterface $expiresAt): void
    {
        $team->complianceDocuments()->create([
            'documentable_type' => $resource->getMorphClass(),
            'documentable_id' => $resource->id,
            'type' => $type->value,
            'number' => strtoupper(Str::random(10)),
            'issued_at' => now()->subYear()->toDateString(),
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Create confirmed orders for the demo customers.
     *
     * @param  array<string, Party>  $parties
     * @param  array<string, Location>  $locations
     * @return array<int, Order>
     */
    protected function seedOrders(Team $team, array $parties, array $locations): array
    {
        $definitions = [
            [
                'customer' => 'cemex',
                'pickup' => 'planta_cemex_mty',
                'delivery' => 'obra_cdmx',
                'notes' => 'Entrega en obra con ventana de 8:00 a 12:00.',
                'items' => [
                    ['Cemento gris CPC 30R en saco de 50 kg', 12, 'Tarima', 1_000_000, 1_200_000, false],
                ],
            ],
            [
                'customer' => 'bimbo',
                'pickup' => 'cedis_bimbo_cdmx',
                'delivery' => 'cedis_gdl',
                'notes' => 'Cadena de frío no requerida.',
                'items' => [
                    ['Pan blanco grande', 200, 'Caja', 400, 8_000, false],
                    ['Pan integral', 120, 'Caja', 420, 8_200, false],
                ],
            ],
            [
                'customer' => 'femsa',
                'pickup' => 'planta_femsa_mty',
                'delivery' => 'centro_gdl',
                'notes' => 'Entregar con carta porte.',
                'items' => [
                    ['Refresco 2 L (charola)', 300, 'Caja', 12_000, 24_000, false],
                ],
            ],
            [
                'customer' => 'heineken',
                'pickup' => 'bodega_heineken_mty',
                'delivery' => 'centro_qro',
                'notes' => 'No apilar más de 4 tarimas.',
                'items' => [
                    ['Cerveza caguama 940 ml (24 pzas)', 240, 'Caja', 18_000, 30_000, false],
                ],
            ],
            [
                'customer' => 'soriana',
                'pickup' => 'cedis_soriana_trc',
                'delivery' => 'sucursal_mty',
                'notes' => 'Cita en andén 7.',
                'items' => [
                    ['Abarrotes surtidos', 60, 'Tarima', 250_000, 800_000, false],
                ],
            ],
            [
                'customer' => 'soriana',
                'pickup' => 'cedis_soriana_trc',
                'delivery' => 'centro_ver',
                'notes' => 'Incluye 3 cajas frágiles.',
                'items' => [
                    ['Electrodomésticos', 40, 'Pieza', 35_000, 120_000, false],
                    ['Pantallas 55"', 6, 'Pieza', 18_000, 90_000, false],
                ],
            ],
        ];

        $orders = [];

        foreach ($definitions as $index => $definition) {
            $orders[] = app(SaveOrder::class)->create($team, [
                'customer_party_id' => $parties[$definition['customer']]->id,
                'status' => OrderStatus::Confirmed->value,
                'currency' => 'MXN',
                'requested_pickup_at' => now()->addDays($index)->setTime(8, 0),
                'requested_delivery_at' => now()->addDays($index + 1)->setTime(14, 0),
                'notes' => $definition['notes'],
                'items' => array_map(fn (array $item): array => [
                    'description' => $item[0],
                    'quantity' => $item[1],
                    'unit' => $item[2],
                    'weight_grams' => $item[3],
                    'volume_cm3' => $item[4],
                    'hazmat' => $item[5],
                ], $definition['items']),
            ]);
        }

        return $orders;
    }

    /**
     * Turn every order into a shipment with items and packages.
     *
     * @param  array<int, Order>  $orders
     * @param  array<string, Location>  $locations
     * @return array<int, Shipment>
     */
    protected function seedShipments(Team $team, array $orders, array $locations): array
    {
        $routes = [
            ['pickup' => 'planta_cemex_mty', 'delivery' => 'obra_cdmx', 'packages' => 12],
            ['pickup' => 'cedis_bimbo_cdmx', 'delivery' => 'cedis_gdl', 'packages' => 12],
            ['pickup' => 'planta_femsa_mty', 'delivery' => 'centro_gdl', 'packages' => 10],
            ['pickup' => 'bodega_heineken_mty', 'delivery' => 'centro_qro', 'packages' => 10],
            ['pickup' => 'cedis_soriana_trc', 'delivery' => 'sucursal_mty', 'packages' => 8],
            ['pickup' => 'cedis_soriana_trc', 'delivery' => 'centro_ver', 'packages' => 6],
        ];

        $shipments = [];

        foreach ($orders as $index => $order) {
            $shipments[] = app(ConvertOrderToShipment::class)->handle($team, $order, [
                'pickup_location_id' => $locations[$routes[$index]['pickup']]->id,
                'delivery_location_id' => $locations[$routes[$index]['delivery']]->id,
                'package_count' => $routes[$index]['packages'],
            ]);
        }

        return $shipments;
    }

    /**
     * Group shipments into two planning loads.
     *
     * @param  array<int, Shipment>  $shipments
     */
    protected function seedLoads(Team $team, array $shipments): void
    {
        $first = app(SaveLoad::class)->create($team, [
            'status' => LoadStatus::Planned->value,
            'notes' => 'Ruta del noreste al bajío.',
        ]);

        $second = app(SaveLoad::class)->create($team, [
            'status' => LoadStatus::Planned->value,
            'notes' => 'Carga ligera consolidada.',
        ]);

        foreach ([0, 1, 2] as $index) {
            $shipments[$index]->update(['load_id' => $first->id]);
        }

        foreach ([3, 4, 5] as $index) {
            $shipments[$index]->update(['load_id' => $second->id]);
        }
    }

    /**
     * Build the trips and record the execution that goes with them.
     *
     * @param  array{drivers: array<string, Driver>, vehicles: array<string, Vehicle>, trailers: array<string, Trailer>}  $fleet
     * @param  array<int, Shipment>  $shipments
     * @param  array<string, User>  $users
     */
    protected function seedTrips(Team $team, array $fleet, array $shipments, array $users): void
    {
        // Trip 1: on the road. One delivery done, one partial, one failed.
        $tripA = $this->buildTrip(
            $team,
            $fleet,
            ['driver' => 'juan', 'vehicle' => 't101', 'trailer' => 'r201'],
            [$shipments[0], $shipments[1], $shipments[2]],
            TripStatus::Dispatched,
            now()->addDay()->setTime(6, 0),
        );

        $this->deliverFully($team, $users['driver'], $tripA, $shipments[0]);
        $this->deliverPartially($team, $users['driver'], $tripA, $shipments[1]);
        $this->failDelivery($team, $users['driver'], $tripA, $shipments[2]);
        $this->recordTripCosts($team, $tripA, $users['driver']);

        // Trip 2: planned, resources reserved, nothing executed yet.
        $this->buildTrip(
            $team,
            $fleet,
            ['driver' => 'miguel', 'vehicle' => 't102', 'trailer' => 'r202'],
            [$shipments[3], $shipments[4]],
            TripStatus::Planned,
            now()->addDays(2)->setTime(7, 0),
        );

        // Trip 3: completed, both deliveries signed off.
        $tripC = $this->buildTrip(
            $team,
            $fleet,
            ['driver' => 'carlos', 'vehicle' => 't103', 'trailer' => 'r203'],
            [$shipments[5]],
            TripStatus::Completed,
            now()->subDays(2)->setTime(5, 30),
        );

        $this->deliverFully($team, $users['driver'], $tripC, $shipments[5]);
        $this->recordTripCosts($team, $tripC, $users['driver']);
    }

    /**
     * Create a trip, assign its resources, and lay out its stops.
     *
     * @param  array{drivers: array<string, Driver>, vehicles: array<string, Vehicle>, trailers: array<string, Trailer>}  $fleet
     * @param  array{driver: string, vehicle: string, trailer: string}  $resources
     * @param  array<int, Shipment>  $shipments
     */
    protected function buildTrip(Team $team, array $fleet, array $resources, array $shipments, TripStatus $status, \DateTimeImmutable $start): Trip
    {
        $trip = $team->trips()->create([
            'number' => 'TRP-'.str_pad((string) (Trip::withTrashed()->where('team_id', $team->id)->count() + 1), 5, '0', STR_PAD_LEFT),
            'status' => TripStatus::Planned->value,
            'planned_start_at' => $start,
            'planned_end_at' => (clone $start)->modify('+10 hours'),
            'timezone' => 'America/Monterrey',
            'notes' => 'Viaje demo generado por el seeder.',
        ]);

        app(AssignTripResources::class)->handle($team, $trip, [
            'driver_id' => $fleet['drivers'][$resources['driver']]->id,
            'vehicle_id' => $fleet['vehicles'][$resources['vehicle']]->id,
            'trailer_id' => $fleet['trailers'][$resources['trailer']]->id,
        ]);

        $pickup = app(SaveStop::class)->create($team, $trip, [
            'type' => StopType::Pickup->value,
            'location_id' => $shipments[0]->pickup_location_id,
            'status' => StopStatus::Pending->value,
            'planned_at' => $start,
        ]);

        foreach ($shipments as $offset => $shipment) {
            $delivery = app(SaveStop::class)->create($team, $trip, [
                'type' => StopType::Delivery->value,
                'location_id' => $shipment->delivery_location_id,
                'status' => StopStatus::Pending->value,
                'planned_at' => (clone $start)->modify('+'.(4 + $offset * 2).' hours'),
            ]);

            $team->stopShipments()->create(['stop_id' => $pickup->id, 'shipment_id' => $shipment->id]);
            $team->stopShipments()->create(['stop_id' => $delivery->id, 'shipment_id' => $shipment->id]);
        }

        $trip->update(['status' => $status->value]);

        return $trip;
    }

    /**
     * Complete a delivery: full attempt, custody scans, and a signed POD.
     */
    protected function deliverFully(Team $team, User $user, Trip $trip, Shipment $shipment): void
    {
        $stop = $this->deliveryStop($trip, $shipment);

        app(RecordDeliveryAttempt::class)->handle($team, $stop, [
            'outcome' => 'delivered',
            'recipient_name' => 'Recibió '.fake()->name(),
            'occurred_at' => now()->subHours(3),
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [[
                'shipment_id' => $shipment->id,
                'quantity' => $shipment->pieces,
                'success' => true,
            ]],
        ], $user);

        foreach ($shipment->packages as $package) {
            foreach ([ScanType::Loaded, ScanType::InTransit, ScanType::Delivered] as $type) {
                app(RecordScan::class)->handle($team, [
                    'package_id' => $package->id,
                    'type' => $type->value,
                    'occurred_at' => now()->subHours(4),
                    'idempotency_key' => (string) Str::uuid(),
                ], $user);
            }
        }

        app(RecordProofOfDelivery::class)->handle($team, $stop, [
            'shipment_id' => $shipment->id,
            'recipient_name' => 'Recibió '.fake()->name(),
            'signature' => self::SIGNATURE,
            'consent' => true,
            'captured_at' => now()->subHours(3),
            'idempotency_key' => (string) Str::uuid(),
        ], $user);

        app(UpdateStopStatus::class)->handle($stop, StopStatus::Completed);
    }

    /**
     * Deliver part of a shipment and leave the stop open.
     */
    protected function deliverPartially(Team $team, User $user, Trip $trip, Shipment $shipment): void
    {
        $stop = $this->deliveryStop($trip, $shipment);
        $quantity = max((int) floor($shipment->pieces / 2), 1);

        app(RecordDeliveryAttempt::class)->handle($team, $stop, [
            'outcome' => 'partially_delivered',
            'recipient_name' => 'Recibió '.fake()->name(),
            'notes' => 'El cliente solo recibió parte del pedido.',
            'occurred_at' => now()->subHours(2),
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [[
                'shipment_id' => $shipment->id,
                'quantity' => $quantity,
                'success' => true,
            ]],
        ], $user);
    }

    /**
     * Fail a delivery and raise an incident for dispatch to review.
     */
    protected function failDelivery(Team $team, User $user, Trip $trip, Shipment $shipment): void
    {
        $stop = $this->deliveryStop($trip, $shipment);

        app(RecordDeliveryAttempt::class)->handle($team, $stop, [
            'outcome' => 'failed',
            'failure_reason' => 'recipient_absent',
            'notes' => 'Nadie recibió en el domicilio.',
            'occurred_at' => now()->subHour(),
            'idempotency_key' => (string) Str::uuid(),
            'lines' => [[
                'shipment_id' => $shipment->id,
                'quantity' => 0,
                'success' => false,
            ]],
        ], $user);

        app(ReportIncident::class)->handle($team, $trip, [
            'type' => IncidentType::Customer->value,
            'severity' => IncidentSeverity::High->value,
            'description' => 'Destinatario ausente; se requiere reprogramar la entrega.',
            'stop_id' => $stop->id,
            'shipment_id' => $shipment->id,
            'occurred_at' => now()->subHour(),
            'idempotency_key' => (string) Str::uuid(),
        ], $user);

        app(UpdateStopStatus::class)->handle($stop, StopStatus::Failed);
    }

    /**
     * Record the fuel, toll, and lodging costs of a trip.
     */
    protected function recordTripCosts(Team $team, Trip $trip, User $user): void
    {
        app(RecordExpense::class)->handle($team, $trip, [
            'type' => ExpenseType::Fuel->value,
            'amount' => 9_480.50,
            'amount_minor' => 948_050,
            'currency' => 'MXN',
            'liters' => 380.5,
            'liters_ml' => 380_500,
            'price_per_liter' => 24.91,
            'price_per_liter_minor' => 2_491,
            'odometer_km' => 128_450.0,
            'odometer_meters' => 128_450_000,
            'tank' => 'main',
            'vendor' => 'Diesel del Norte',
            'incurred_at' => now()->subHours(6),
            'idempotency_key' => (string) Str::uuid(),
        ], $user);

        app(RecordExpense::class)->handle($team, $trip, [
            'type' => ExpenseType::Toll->value,
            'amount' => 1_450.00,
            'amount_minor' => 145_000,
            'currency' => 'MXN',
            'vendor' => 'Caminos y Puentes Federales',
            'incurred_at' => now()->subHours(5),
            'idempotency_key' => (string) Str::uuid(),
        ], $user);

        app(RecordExpense::class)->handle($team, $trip, [
            'type' => ExpenseType::Lodging->value,
            'amount' => 950.00,
            'amount_minor' => 95_000,
            'currency' => 'MXN',
            'vendor' => 'Hotel Industrial',
            'incurred_at' => now()->subHours(8),
            'idempotency_key' => (string) Str::uuid(),
        ], $user);
    }

    /**
     * Find the delivery stop that serves the given shipment on a trip.
     */
    protected function deliveryStop(Trip $trip, Shipment $shipment): Stop
    {
        return $trip->stops()
            ->where('type', StopType::Delivery->value)
            ->whereHas('shipments', fn ($query) => $query->whereKey($shipment->id))
            ->orderBy('sequence')
            ->firstOrFail();
    }
}
