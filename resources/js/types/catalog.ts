export type Option = {
    value: string;
    label: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type PartyType = 'customer' | 'carrier' | 'supplier';

export type Party = {
    id: number;
    type: PartyType;
    type_label: string;
    name: string;
    legal_name: string | null;
    rfc: string | null;
    email: string | null;
    phone: string | null;
    contacts_count: number;
    locations_count: number;
};

export type PartyContact = {
    id: number;
    name: string;
    position: string | null;
    email: string | null;
    phone: string | null;
};

export type PartyLocation = {
    id: number;
    name: string;
    city: string;
    state: string;
};

export type PartyDetail = Party & {
    contacts: PartyContact[];
    locations: PartyLocation[];
};

export type Location = {
    id: number;
    name: string;
    party_id: number | null;
    party_name: string | null;
    street: string;
    exterior_number: string | null;
    interior_number: string | null;
    neighborhood: string | null;
    city: string;
    state: string;
    postal_code: string;
    references: string | null;
    latitude: number | null;
    longitude: number | null;
    timezone: string | null;
};

export type LocationSnapshot = {
    name: string;
    street: string;
    exterior_number: string | null;
    interior_number: string | null;
    neighborhood: string | null;
    city: string;
    state: string;
    postal_code: string;
    latitude: number | null;
    longitude: number | null;
};

export type Driver = {
    id: number;
    name: string;
    phone: string;
    license_number: string | null;
    license_expires_at: string | null;
    license_expired: boolean;
    carrier_party_id: number | null;
    carrier_name: string | null;
    documents_count: number;
    has_expired_documents: boolean;
    documents?: ComplianceDocument[];
};

export type Vehicle = {
    id: number;
    name: string;
    plate: string;
    configuration: string;
    carrier_party_id: number | null;
    carrier_name: string | null;
    max_payload_grams: number;
    max_payload_kg: number;
    max_volume_cm3: number | null;
    max_volume_m3: number | null;
    documents_count: number;
    has_expired_documents: boolean;
    documents?: ComplianceDocument[];
};

export type Trailer = {
    id: number;
    name: string;
    plate: string;
    configuration: string;
    carrier_party_id: number | null;
    carrier_name: string | null;
    max_payload_grams: number;
    max_payload_kg: number;
    max_volume_cm3: number | null;
    max_volume_m3: number | null;
    documents_count: number;
    has_expired_documents: boolean;
    documents?: ComplianceDocument[];
};

export type ComplianceDocument = {
    id: number;
    type: string;
    type_label: string;
    number: string | null;
    issued_at: string | null;
    expires_at: string | null;
    expired: boolean;
    expiring_soon: boolean;
    notes: string | null;
};

export type OrderStatus =
    | 'draft'
    | 'confirmed'
    | 'in_progress'
    | 'completed'
    | 'cancelled';

export type Order = {
    id: number;
    number: string;
    status: OrderStatus;
    status_label: string;
    currency: string;
    customer_party_id: number | null;
    customer_name: string | null;
    customer_rfc: string | null;
    requested_pickup_at: string | null;
    requested_delivery_at: string | null;
    notes: string | null;
    items_count: number;
    shipments_count: number;
    created_at: string | null;
};

export type OrderItem = {
    id: number;
    description: string;
    quantity: number;
    unit: string;
    weight_grams: number;
    weight_kg: number;
    volume_cm3: number;
    volume_m3: number;
    hazmat: boolean;
};

export type OrderShipmentRef = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    pieces: number;
};

export type OrderTotals = {
    weight_grams: number;
    volume_cm3: number;
    pieces: number;
    hazmat: boolean;
};

export type OrderDetail = Order & {
    items: OrderItem[];
    shipments: OrderShipmentRef[];
    totals: OrderTotals;
};

export type OrderLineInput = {
    description: string;
    quantity: number;
    unit: string;
    weight_kg: string | number;
    volume_m3: string | number;
    hazmat: boolean;
};

export type ShipmentStatus =
    | 'planned'
    | 'dispatched'
    | 'in_transit'
    | 'delivered'
    | 'partially_delivered'
    | 'failed'
    | 'cancelled';

export type Shipment = {
    id: number;
    number: string;
    status: ShipmentStatus;
    status_label: string;
    currency: string;
    customer_name: string | null;
    order_id: number | null;
    order_number: string | null;
    pickup_location_id: number | null;
    delivery_location_id: number | null;
    pickup_snapshot: LocationSnapshot | null;
    delivery_snapshot: LocationSnapshot | null;
    weight_grams: number;
    volume_cm3: number;
    pieces: number;
    items_count: number;
    packages_count: number;
    created_at: string | null;
};

export type ShipmentItem = {
    id: number;
    description: string;
    quantity: number;
    unit: string;
    weight_grams: number;
    weight_kg: number;
    volume_cm3: number;
    volume_m3: number;
    hazmat: boolean;
};

export type ShipmentPackage = {
    id: number;
    code: string;
    status: string;
    status_label: string;
    weight_grams: number | null;
};

export type ShipmentDetail = Shipment & {
    items: ShipmentItem[];
    packages: ShipmentPackage[];
};

export type LoadStatus =
    | 'draft'
    | 'planned'
    | 'in_transit'
    | 'completed'
    | 'cancelled';

export type LoadShipmentRef = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    customer_name: string | null;
    pieces: number;
    weight_grams: number;
};

export type Load = {
    id: number;
    number: string;
    status: LoadStatus;
    status_label: string;
    notes: string | null;
    shipments_count: number;
    created_at: string | null;
};

export type LoadTotals = {
    weight_grams: number;
    volume_cm3: number;
    pieces: number;
};

export type LoadDetail = Load & {
    shipments: LoadShipmentRef[];
    totals: LoadTotals;
};

export type TripStatus =
    | 'planned'
    | 'dispatched'
    | 'in_transit'
    | 'completed'
    | 'cancelled';

export type Trip = {
    id: number;
    number: string;
    status: TripStatus;
    status_label: string;
    planned_start_at: string | null;
    planned_end_at: string | null;
    timezone: string | null;
    notes: string | null;
    driver_id: number | null;
    driver_name: string | null;
    vehicle_id: number | null;
    vehicle_name: string | null;
    trailer_id: number | null;
    trailer_name: string | null;
    created_at: string | null;
};

export type TripAssignmentRef = {
    id: number;
    resource: 'driver' | 'vehicle' | 'trailer';
    name: string | null;
    assigned_at: string;
    released_at: string | null;
};

export type TripCapacity = {
    shipments_count: number;
    weight_grams: number;
    volume_cm3: number;
    weight_limit_grams: number | null;
    volume_limit_cm3: number | null;
    weight_utilization: number | null;
    volume_utilization: number | null;
    over_weight: boolean;
    over_volume: boolean;
    over: boolean;
};

export type TripDetail = Trip & {
    assignments: TripAssignmentRef[];
    stops: StopRef[];
    capacity: TripCapacity;
    capacity_override_reason: string | null;
    capacity_overridden_at: string | null;
};

export type DispatchShipmentRef = {
    id: number;
    number: string;
    customer_name: string | null;
};

export type DispatchTrip = {
    id: number;
    number: string;
    status: TripStatus;
    status_label: string;
    planned_start_at: string | null;
    driver_id: number | null;
    driver_name: string | null;
    vehicle_id: number | null;
    vehicle_name: string | null;
    trailer_id: number | null;
    trailer_name: string | null;
    capacity: TripCapacity;
    shipments: DispatchShipmentRef[];
};

export type DispatchPoolShipment = {
    id: number;
    number: string;
    customer_name: string | null;
    status: string;
    status_label: string;
    pieces: number;
    weight_grams: number;
    destination: string | null;
};

export type StopType = 'pickup' | 'delivery' | 'other';

export type StopStatus =
    | 'pending'
    | 'arrived'
    | 'completed'
    | 'failed'
    | 'skipped';

export type StopShipmentRef = {
    id: number;
    number: string;
    customer_name: string | null;
};

export type StopRef = {
    id: number;
    sequence: number;
    type: StopType;
    type_label: string;
    status: StopStatus;
    status_label: string;
    location_id: number | null;
    location_name: string | null;
    location_snapshot: LocationSnapshot | null;
    planned_at: string | null;
    notes: string | null;
    shipments: StopShipmentRef[];
};

export type SearchResult = {
    type:
        | 'party'
        | 'location'
        | 'driver'
        | 'vehicle'
        | 'order'
        | 'shipment'
        | 'load'
        | 'trip';
    title: string;
    subtitle: string;
    url: string;
};
