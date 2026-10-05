import type { LocationSnapshot, Option } from './catalog';

export type DriverTripSummary = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    planned_start_at: string | null;
    vehicle_name: string | null;
    trailer_name: string | null;
    stops_count: number;
};

export type DriverPackage = {
    id: number;
    code: string;
    status: string;
    status_label: string;
    terminal: boolean;
};

export type DriverShipment = {
    id: number;
    number: string;
    customer_name: string | null;
    status: string;
    status_label: string;
    pieces: number;
    delivered_quantity: number;
    remaining_quantity: number;
    packages: DriverPackage[];
};

export type DriverAttempt = {
    id: number;
    outcome: string;
    outcome_label: string;
    failure_reason_label: string | null;
    recipient_name: string | null;
    occurred_at: string;
    notes: string | null;
};

export type DriverPod = {
    id: number;
    recipient_name: string | null;
    captured_at: string;
    signature_url: string | null;
    photo_count: number;
    document_count: number;
};

export type DriverStop = {
    id: number;
    sequence: number;
    type: string;
    type_label: string;
    status: string;
    status_label: string;
    is_pickup: boolean;
    location_name: string | null;
    location_snapshot: LocationSnapshot | null;
    planned_at: string | null;
    notes: string | null;
    shipments: DriverShipment[];
    attempts: DriverAttempt[];
    pods: DriverPod[];
};

export type DriverIncident = {
    id: number;
    type: string;
    type_label: string;
    severity: string;
    severity_label: string;
    status: string;
    status_label: string;
    description: string;
    occurred_at: string;
};

export type DriverExpense = {
    id: number;
    type: string;
    type_label: string;
    amount_minor: number;
    currency: string;
    incurred_at: string;
    vendor: string | null;
};

export type DriverTripDetail = {
    id: number;
    number: string;
    status: string;
    status_label: string;
    planned_start_at: string | null;
    timezone: string | null;
    notes: string | null;
    driver_name: string | null;
    vehicle_name: string | null;
    vehicle_plate: string | null;
    trailer_name: string | null;
    stops: DriverStop[];
    incidents: DriverIncident[];
    expenses: DriverExpense[];
};

export type DriverOptions = {
    outcomes: Option[];
    failureReasons: Option[];
    scanTypes: Option[];
    incidentTypes: Option[];
    incidentSeverities: Option[];
    expenseTypes: Option[];
};
