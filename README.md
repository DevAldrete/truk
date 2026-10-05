# Truk - a Transportation Management System (TMS)

A multi-tenant, multi-sided platform for the full life of a road-freight shipment:
order intake, pricing, planning, dispatch, real-time tracking, proof of delivery,
fiscal compliance, settlement, and billing. Built for Mexico domestic and
cross-border operations, where **fiscal and transport compliance is an upstream
gatekeeper**: goods cannot move legally without valid tax and transport data.

The system supports a **mixed fleet model**: companies can use their own
vehicles and drivers or subcontract transportation to third-party carriers. It
is designed to support multiple organizations and business relationships
without mixing their data.

An **exceptional, low-friction experience is a product requirement, not polish**.
Dispatchers must be fast and confident on the first try; drivers must be able to
execute a stop on a phone, on a bad connection, in a few taps.

## Product scope

The product covers the complete operational and commercial pipeline:

### Platform and foundation

-   Multi-tenant data isolation, roles, permissions, and audit history.
-   API-first surface (versioned REST + webhooks) for every core entity.
-   Asynchronous job queues, an outbox for reliable events, and idempotent
    commands that are safe to retry.
-   Private file storage with short-lived access URLs.
-   Bilingual (ES/EN), keyboard-first operations, command palette.

### Master data and fleet

-   Organizations, memberships, customers, carriers, suppliers, contacts, and
    pickup/delivery locations with coordinates and timezone.
-   Drivers, vehicles, and trailers with SICT/SCT legal profiles and compliance
    documents (licenses, insurance, permits, inspections, HazMat).

### Commercial and order intake

-   Quotes, rate cards, contract and spot pricing.
-   A rate engine matrix: distance, weight/volume, zone, and flat contract rates.
-   Orders, shipments, shipment items, and packages with barcode/SSCC identity.
-   ERP/WMS ingestion through REST APIs, webhooks, and EDI (204, 211).

### Planning, dispatch, and execution

-   Loads, load consolidation (LTL to FTL), trips, ordered stops, and assignments.
-   Planned **and measured** capacity: payload, volume, per-axle and gross
    weight limits, with permissioned overrides for legal exceptions.
-   A dispatch board that treats planning as the primary workflow.
-   An offline-first driver PWA: stop execution, package scans, delivery
    attempts, partial deliveries, returns, and exceptions.
-   Digital proof of delivery (e-POD): signature, photos, scanned documents,
    delivered quantities, and capture location.

### Visibility and telematics

-   A hardware-agnostic GPS/telematics aggregator (Samsara, Geotab, Omnitracs,
    Encontrack, and local providers).
-   An append-only tracking timeline, geofences, predictive ETA, and proactive
    arrival/departure and exception alerts.

### Compliance and fiscality (Mexico)

-   CFDI 4.0 (Ingreso and Traslado) with **Carta Porte 3.0/3.1**.
-   Certified provider (PAC) integration for stamping (_timbrado_) and
    cancellation, run asynchronously.
-   SAT catalog normalization: `ClaveProdServ`, `ClaveUnidad`,
    `FracciónArancelaria`, and `MaterialPeligroso` (HazMat/UN).
-   Hard payload validation that **gates dispatch** until mandatory fields are
    complete.
-   SICT fleet legal profiles and cross-border pedimento linkage.

### Optimization

-   A routing engine with a constraints engine: toll roads, bridge weight/height
    limits, driver rest/hours-of-service, and urban access windows.
-   Multi-stop sequencing optimized for time windows and fuel.
-   Load consolidation and utilization planning.

### Settlement, billing, and analytics

-   3-way matching between rate quote, e-POD, and invoice.
-   Accessorials: detention, demurrage, layover, and fuel surcharges.
-   Customer invoicing, carrier settlement, and payment status.
-   Metrics: OTIF, cost per ton/km, fleet utilization, driver safety, and
    carrier performance ratings.

### Yard, docking, and integrations

-   Appointment scheduling, gate-in/gate-out, dock slots, and trailer yard
    tracking.
-   Toll-booth catalog with per-axle pricing and telepeaje (IAVE/TAG)
    reconciliation.
-   Maps/routing, notification, and billing providers behind stable interfaces.

## Core business flow

1.  Register an organization and its customers, carriers, locations, and
    resources.
2.  Create a quote or order and price it with the rate engine.
3.  Convert the order into one or more shipments, items, and packages.
4.  Group shipments into a load and assign the load to a trip.
5.  Plan and sequence stops; assign the driver, vehicle, and trailer.
6.  Validate compliance and capacity; dispatch (which stamps Carta Porte data).
7.  The driver executes each stop offline-first: scans packages, records
    quantities, outcomes, photos, and signatures.
8.  Track progress through the timeline and telematics; react to exceptions.
9.  Reconcile rate quote + e-POD + invoice; bill the customer and settle the
    carrier.
10. Notify relevant users, keep an auditable history, and feed the KPI
    dashboards.

## Important design rules

-   Enforce tenant isolation on every data access and mutation.
-   Treat authentication and authorization as separate concerns.
-   Keep orders, shipments, packages, loads, trips, stops, delivery attempts,
    and proof of delivery as distinct entities.
-   Preserve historical snapshots of addresses, parties, commercial details, and
    compliance payloads where operational and fiscal records depend on them.
-   Use explicit state transitions rather than allowing arbitrary status
    updates; derive aggregate status in one place.
-   Gate dispatch on compliance and capacity; allow a permissioned override that
    records a reason.
-   Use database transactions for multi-record operations and an outbox for
    reliable event publishing.
-   Use idempotency keys for commands that may be retried.
-   Store timestamps in UTC and keep time-zone context for local schedules.
-   Store money as integer minor units, weight as integer grams, and volume as
    integer cubic centimetres; never floats on summed or compared values.
-   Store files in private object storage; persist metadata in PostgreSQL and use
    short-lived access URLs.
-   Never trust a tenant ID supplied by the client without checking the
    authenticated user's membership and permissions.
-   Keep every provider (PAC, telematics, maps, notifications, billing) behind an
    interface so it can be swapped.

## Definition of a real release

An authorized operator can price an order, turn it into shipments and packages,
plan and dispatch it onto a compliant trip within legal capacity, and let a
driver execute it offline-first with evidence. The system tracks the trip,
reconciles the delivery against the quote, issues the fiscal document, bills the
customer, and records everything in an auditable, tenant-isolated history.
