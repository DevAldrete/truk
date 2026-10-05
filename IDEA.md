# Product Idea and Domain Rules

## 1. The idea

Build a **full-lifecycle, multi-tenant Transportation Management System (TMS)**
for road freight in Mexico and cross-border trade. It coordinates the movement
of goods from order intake and pricing through dispatch, real-time tracking,
proof of delivery, fiscal compliance, settlement, and billing — and it treats
route and fleet **optimization** as a first-class capability rather than an
afterthought.

The platform is **multi-tenant and multi-sided**: organizations can operate
their own fleet, subcontract to carriers, or both, while interacting with
customers, carriers, suppliers, drivers, and fiscal authorities.

Two things are non-negotiable:

-   **Compliance is upstream.** In Mexico, SAT (CFDI 4.0 + Carta Porte 3.0/3.1)
    and SICT data gate dispatch. We cannot model compliance as a downstream
    billing step; the operational records must carry valid fiscal and transport
    data before goods move.
-   **Exceptional UX.** Dispatchers, warehouse staff, and drivers must be fast
    and confident on the first try, on desktop and on the road, including on
    poor connections. Usability is a feature with acceptance criteria.

## 2. Scope

### 2.1 In scope — the full product

Every pillar below is part of the target product. `PROCESS.md` sequences the
build into phases.

| Pillar | Capabilities |
| --- | --- |
| **Platform** | Multi-tenancy, RBAC, audit log, outbox, idempotent commands, versioned API + webhooks, async queues, private file storage, offline-first driver app |
| **Master data** | Parties, contacts, locations (coordinates + timezone), drivers, vehicles, trailers, compliance documents, global reference catalogs |
| **Compliance & fiscal** | SAT catalogs (`ClaveProdServ`, `ClaveUnidad`, `FracciónArancelaria`, `MaterialPeligroso`), CFDI 4.0 Ingreso/Traslado, Carta Porte 3.0/3.1, PAC stamping/cancellation, dispatch gate, SICT profiles, pedimento linkage, HazMat/UN, insurance |
| **Commercial** | Quotes, rate cards, contract & spot pricing, rate engine matrix (distance/weight/volume/zone/flat), carrier allocation and tenders |
| **Order intake** | Service requests, orders, shipments, shipment items, packages with barcode/SSCC, snapshots, ERP/WMS ingestion (REST/webhooks/EDI 204/211) |
| **Planning & dispatch** | Loads, consolidation (LTL→FTL), trips, ordered stops, multi-stop sequencing, planned **and measured** capacity, per-axle/gross limits, resource conflicts, dispatch board |
| **Execution** | Offline-first driver PWA, stop workflow, package scans, delivery attempts, partials, returns, POD (signature/photo/scan), incidents/exceptions, expenses & fuel |
| **Visibility** | Append-only tracking timeline, hardware-agnostic GPS/telematics aggregator, geofences, predictive ETA, proactive alerts |
| **Optimization** | Routing engine, route/fuel optimization, constraint engine (tolls, bridge weight/height, driver rest/HOS, urban access windows), toll-aware costing |
| **Settlement & billing** | 3-way matching (quote + POD + invoice), accessorials (detention, demurrage, layover), fuel surcharge, customer invoicing, carrier settlement, payment status, SaaS subscription billing |
| **Yard & dock** | Appointment scheduling, gate-in/gate-out, dock slots, trailer yard tracking |
| **Analytics & KPIs** | OTIF, cost per ton/km, utilization, driver safety, carrier rating, margin/profitability |
| **Integrations** | PAC providers, telematics vendors, toll-booth catalog + telepeaje, ERP/WMS, EDI, maps/routing, notifications, billing |
| **Experience** | Bilingual ES/EN, keyboard-first operations, command palette, real-time feedback, mobile-first driver UX, accessibility, deliberate empty/loading/error states |

### 2.2 Out of scope (explicit)

-   Operating our own PAC or tax authority; we integrate certified providers.
-   Building geocoding, map tiles, or traffic data from scratch; we consume
    providers.
-   Custom telematics hardware; we integrate existing vendors.
-   Native mobile apps first; start with an installable, offline-first PWA.

## 3. Core domain model

  -----------------------------------------------------------------------
  Entity                              Responsibility
  ----------------------------------- -----------------------------------
  Tenant (Team)                        Security and data-isolation
                                       boundary / operating company

  Membership / Role / Permission       Access control within a tenant

  Party / Contact                      Customer, carrier, supplier, or
                                       other relationship

  Location                             Pickup, delivery, warehouse, or
                                       other site (with coordinates and
                                       timezone)

  Driver / Vehicle / Trailer           Resources used to execute
                                       transportation

  Compliance document                  Legal evidence attached to a
                                       resource (license, insurance,
                                       permit, inspection)

  Fiscal profile / SAT catalog entry   Reference data for CFDI/Carta
                                       Porte (claves, units, tariff
                                       codes, HazMat)

  Rate / Rate card / Quote             Commercial offer and pricing

  Order                                Commercial request to move goods

  Shipment                             Operational unit representing goods
                                       to be transported

  Shipment item                        Priced, quantified line of a
                                       shipment

  Package                              Trackable physical unit with a
                                       barcode/SSCC and lifecycle

  Load                                 Group of shipments planned together

  Trip                                 A planned execution using assigned
                                       resources

  Stop                                 A planned pickup, delivery, or
                                       other action within a trip

  Delivery attempt                     A specific attempt to complete a
                                       delivery

  Proof of delivery (POD)             Evidence recorded for a delivery
                                       outcome

  Scan event                           A package/asset movement or
                                       custody event

  Tracking event                      An append-only record of a relevant
                                       operational event

  Position / telemetry sample         A GPS fix or IoT reading

  Exception / Incident                A problem requiring review or
                                       action

  Expense                             An operational cost (fuel,
                                       tolls, lodging, misc.)

  Tax document (CFDI + Carta Porte)   A stamped or cancelable fiscal
                                       document and its payload

  Invoice / Settlement                A customer invoice or carrier
                                       settlement

  Route plan / Route constraint       Optimizer output and the rules
                                       that produced it

  Audit log entry                     Who changed what, when, and why
  -----------------------------------------------------------------------

Do not collapse these concepts into a single "status" record. They represent
different stages, responsibilities, and legal meanings.

## 4. Most important business logic

### Tenant and organization boundaries

-   Every operational record belongs to a tenant.
-   A user can belong to one or more organizations through explicit
    memberships.
-   Every request must verify membership and permission for the target
    resource.
-   Enforce tenant consistency in application logic and, where practical, with
    composite database keys and foreign keys.
-   Never rely only on a client-provided tenant ID.
-   **Global catalogs are the exception**: SAT/SCT codes, toll booths, and units
    of measure are shared reference data, not tenant data. They live outside the
    tenant scope and are versioned.

### Order-to-cash flow

`Quote → Order → Shipment(s) → Package(s) → Load(s) → Trip → Stop(s) → Delivery
attempt → POD → Invoice → Settlement`

-   One order may produce multiple shipments.
-   A shipment has items (priced, quantified) and packages (physical, tracked).
-   A load groups shipments for planning; a trip executes one or more loads.
-   A trip contains ordered, sequenced stops; a stop may serve one or more
    shipments.
-   A delivery attempt records what happened at a particular attempt; a POD
    stores the evidence; neither is the same as the stop.
-   Model partial deliveries, failed attempts, rescheduling, returns, and
    exceptions explicitly.

### Compliance gate (Mexico)

-   Dispatch must be blocked until the fiscal/transport payload is complete and
    valid: weight, packaging type, operator RFC, vehicle configuration, SAT
    claves, and HazMat flags where applicable.
-   A compliance document or fiscal validation failure is a **hard gate**, not a
    warning. A permissioned override may permit dispatch but records who
    overrode it, when, and why.
-   Carta Porte payloads are **snapshotted** onto the trip: changing a vehicle or
    address later must not rewrite an already-stamped document.
-   Stamp asynchronously through a PAC adapter; cancellation creates a new
    auditable record and never deletes the original.
-   Never compute taxes ourselves; we prepare payloads and consume certified
    provider results.

### Capacity: planned and measured

-   Capacity is not a single ceiling. Track **planned** weight/volume against the
    effective limit `min(vehicle, trailer)` and, for the legal check, per-axle
    weight, gross vehicle weight, and dimensions (NOM-012-SCT-2).
-   Track **utilization** (how full): weight fill % and volume fill % so an
    almost-empty truck is visibly under-utilized and a candidate for LTL
    consolidation.
-   Record **measured/actual** loaded weight where available (scale, warehouse)
    so the plan can be compared with reality.
-   Block over-capacity dispatches, with a permissioned override that records a
    reason. Overweight fines are why the block exists.

### Mixed fleet and resources

-   A trip can use company-owned resources or a subcontracted carrier.
-   Record who is responsible for execution and which driver/vehicle are
    assigned.
-   Keep assignment history; do not overwrite past assignments without an audit
    trail.
-   Validate resource availability and prevent conflicting active assignments
    (one driver/vehicle/trailer per overlapping window).
-   Respect driver hours-of-service/rest constraints in optimization.

### Package custody and tracking

-   A package has a lifecycle derived from scan events (out-of-order scans
    resolve by `occurred_at`, through an allowed-transition map).
-   Scans record custody changes: loaded, in transit, delivered, returned,
    damaged.
-   A package may be split across partial deliveries; delivered quantities are
    accounted, never silently clamped.

### State transitions

Use explicit, validated transitions for orders, shipments, trips, stops, and
packages. Examples include planned, dispatched, in progress, completed, failed,
and cancelled.

-   Reject invalid transitions.
-   Record who or what initiated each transition and when.
-   Do not mark a shipment delivered merely because a trip or stop is completed.
-   Derive or update aggregate status through a clear application use case, not
    scattered UI logic.

### Delivery evidence

A POD may include a recipient name, signature, photos, scanned documents,
delivered quantities, timestamp, and location.

-   Allow partial quantities and failed-delivery reasons.
-   Keep original evidence immutable; corrections create an auditable record.
-   Restrict access to sensitive files and use short-lived URLs.
-   Define retention and privacy rules before collecting location data or
    signatures.

### Settlement and billing

-   3-way match the rate quote, the e-POD quantities, and the invoice; flag
    variances instead of paying silently.
-   Compute accessorials (detention, demurrage, layover) and fuel surcharges
    from recorded events and rates.
-   Customer invoicing and carrier settlement are separate flows over the same
    POD.
-   Keep money as integer minor units and carry a currency; cross-border work
    forces multi-currency.

### Reliability and auditability

-   Use transactions when one command changes multiple related records.
-   Use idempotency keys for retryable commands such as dispatch, scan, and POD
    submission.
-   Record important changes in an audit log.
-   Use an outbox pattern to publish events reliably after database commits.
-   Make background jobs safe to retry.
-   Keep operational events append-only where practical.

## 5. Architecture and integrations

Start as a **modular monolith** with clear boundaries:

-   **Web app:** operations and administration (Inertia + Vue).
-   **Driver web app:** an offline-first PWA for assigned trips, stop execution,
    scans, and POD.
-   **API:** versioned REST + webhooks for orders, shipments, trips, packages,
    invoices, and locations; used by ERPs, WMS, EDI gateways, and portals.
-   **Worker:** notifications, imports, PAC stamping, telematics ingestion, and
    optimization runs.
-   **Database:** PostgreSQL as the source of truth.
-   **Integrations:** adapters around external providers.

The stack is settled in `PROCESS.md` (Laravel 13 + Inertia 3 + Vue 3 +
Fortify + Wayfinder + Tailwind 4 + shadcn-vue + Pest + Larastan). The domain
rules here are stack-independent.

Keep provider-specific logic behind interfaces:

-   PAC / fiscal stamping provider.
-   Maps/routing and optimization provider.
-   Telematics/GPS aggregator.
-   Email/SMS/push notification provider.
-   Object storage for photos and documents.
-   Billing provider for SaaS subscriptions.

Do not build tax calculation, geocoding, or map data from scratch. Do build the
domain logic — capacity, custody, dispatch, matching, and compliance gating —
ourselves, because it is the product.

## 6. Roadmap

Phases are sequenced in `PROCESS.md`. In summary:

1.  **Foundation** — tenancy, roles, audit, isolation tests.
2.  **Master data** — parties, contacts, locations (coordinates/timezone),
    fleet, compliance documents.
3.  **Order intake** — quotes/rates, orders, shipments, items, packages,
    snapshots.
4.  **Planning & dispatch** — loads, trips, stops, capacity (planned/measured),
    conflicts, dispatch board.
5.  **Execution** — offline driver PWA, scans, attempts, POD, exceptions, fuel
    expenses.
6.  **Visibility** — tracking timeline, telematics aggregation, geofences, ETA,
    notifications/outbox, audit UI, file storage.
7.  **Fiscal compliance** — SAT catalogs, CFDI 4.0 + Carta Porte, PAC adapter,
    dispatch gate, pedimentos.
8.  **Pricing & billing** — rate engine, accessorials, fuel surcharge, 3-way
    match, invoicing, settlement.
9.  **Optimization** — routing, multi-stop sequencing, consolidation,
    constraint engine, toll-aware costing.
10. **Integrations & expansion** — EDI, ERP/WMS, toll catalog/telepeaje,
    customer/carrier portal, SaaS billing, multi-currency.
11. **Analytics** — OTIF, cost per ton/km, utilization, safety, carrier rating.

## 7. Definition of a real release

An authorized operator can price an order, turn it into shipments and packages,
plan and dispatch it onto a compliant trip within legal capacity, and let a
driver execute it offline-first with evidence. The system tracks the trip,
reconciles the delivery against the quote, issues the fiscal document, bills the
customer, and records everything in an auditable, tenant-isolated history.
