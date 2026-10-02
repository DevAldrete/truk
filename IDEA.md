# Product Idea and Domain Rules

## 1. The idea

Build a web-based Transportation Management System (TMS) that
coordinates the movement of goods from order intake through dispatch and
delivery.

The platform is **multi-tenant and multi-sided**: organizations can
operate the system while interacting with customers, carriers,
subcontractors, and other business partners. A company may use its own
fleet, external carriers, or both.

The first version should prioritize reliable day-to-day operations over
advanced optimization, accounting, or billing.

## 2. Initial scope

### Include

-   Organizations, memberships, roles, and permissions.
-   Customers, carriers, contacts, and pickup/delivery locations.
-   Drivers, vehicles, trailers, assignments, and compliance documents.
-   Quotes, orders, shipments, and shipment items.
-   Loads, trips, stops, and dispatch.
-   Driver web portal for stop execution.
-   Delivery attempts, proof of delivery, tracking events, and
    exceptions.
-   Document metadata and import of external documents.
-   Audit history and operational notifications.

### Defer

-   Route optimization and advanced fleet optimization.
-   Full accounting, invoicing, and payment processing.
-   A custom tax or fiscal-compliance engine.
-   Native mobile apps; start with a responsive driver web app.
-   Complex analytics and customer-specific workflow builders.
-   Direct fiscal-document issuance. Design an integration boundary for
    a certified provider when needed.

## 3. Core domain model

  -----------------------------------------------------------------------
  Entity                              Responsibility
  ----------------------------------- -----------------------------------
  Tenant                              Security and data-isolation
                                      boundary

  Organization                        A company or operating entity using
                                      the platform

  Party                               A customer, carrier, supplier, or
                                      other business relationship

  Location                            A pickup, delivery, warehouse, or
                                      other operational site

  Driver / Vehicle / Trailer          Resources used to execute
                                      transportation

  Order                               Commercial request to move goods

  Shipment                            Operational unit representing goods
                                      to be transported

  Load                                Group of shipments planned together

  Trip                                A planned execution using assigned
                                      resources

  Stop                                A planned pickup, delivery, or
                                      other action within a trip

  Delivery attempt                    A specific attempt to complete a
                                      delivery

  Proof of delivery (POD)             Evidence recorded for a delivery
                                      outcome

  Tracking event                      An append-only record of a relevant
                                      operational event

  Exception                           A problem requiring review or
                                      action
  -----------------------------------------------------------------------

Do not collapse these concepts into a single "shipment status" record.
They represent different stages and responsibilities.

## 4. Most important business logic

### Tenant and organization boundaries

-   Every operational record belongs to a tenant.
-   A user can belong to one or more organizations through explicit
    memberships.
-   Every request must verify membership and permission for the target
    resource.
-   Enforce tenant consistency in application logic and, where
    practical, with composite database keys and foreign keys.
-   Never rely only on a client-provided tenant ID.

### Order-to-delivery flow

`Order → Shipment(s) → Load(s) → Trip → Stop(s) → Delivery attempt → POD`

-   One order may produce multiple shipments.
-   A load groups shipments for planning.
-   A trip executes one or more loads.
-   A trip contains ordered stops.
-   A stop may serve one or more shipments.
-   A delivery attempt records what happened at a particular attempt.
-   POD stores evidence of the outcome; it is not the same thing as the
    stop or attempt.

Model partial deliveries, failed attempts, rescheduling, returns, and
exceptions explicitly.

### Mixed fleet

-   A trip can use company-owned resources or a subcontracted carrier.
-   Record who is responsible for execution and which driver/vehicle are
    assigned.
-   Keep assignment history; do not overwrite past assignments without
    an audit trail.
-   Validate resource availability and prevent conflicting active
    assignments.

### State transitions

Use explicit, validated transitions for orders, shipments, trips, and
stops. Examples include planned, dispatched, in progress, completed,
failed, and cancelled, as appropriate to each entity.

-   Reject invalid transitions.
-   Record who or what initiated each transition and when.
-   Do not mark a shipment delivered merely because a trip or stop is
    completed.
-   Derive or update aggregate status through a clear application use
    case, not scattered UI logic.

### Delivery evidence

A POD may include a recipient name, signature, photos, delivered
quantities, timestamp, location when available, and notes.

-   Allow partial quantities and failed delivery reasons.
-   Keep original evidence immutable; corrections should create an
    auditable record.
-   Restrict access to sensitive files and use short-lived URLs.
-   Define retention and privacy rules before collecting location data
    or signatures.

### Reliability and auditability

-   Use transactions when one command changes multiple related records.
-   Use idempotency keys for retryable commands such as dispatch and POD
    submission.
-   Record important changes in an audit log.
-   Use an outbox pattern to publish events reliably after database
    commits.
-   Make background jobs safe to retry.
-   Keep operational events append-only where practical.

## 5. Architecture and integrations

Start as a **modular monolith** with clear boundaries:

-   **Web app:** operations and administration.
-   **Driver web app:** assigned trips, stop execution, and POD.
-   **API:** authentication context, authorization, validation, and use
    cases.
-   **Worker:** notifications, imports, and asynchronous processing.
-   **Database:** PostgreSQL as the source of truth.
-   **Integrations:** adapters around external providers.

Use Clerk for identity, but enforce application-level tenant membership
and permissions in the backend. Use Zod contracts at API boundaries and
Drizzle migrations to evolve the schema.

Keep provider-specific logic behind interfaces:

-   Maps/routing provider.
-   Email/SMS/push notification provider.
-   Object storage for photos and documents.
-   Fiscal-document import and, later, certified issuance provider.
-   Billing provider if SaaS subscriptions are introduced.

Do not build tax calculation, payment processing, or route optimization
from scratch unless they become explicit product requirements.

## 6. Roadmap

### Phase 1 --- Foundation

-   Configure PostgreSQL and migrations using docker compose
-   Configure tenants, organizations, memberships,
    and permissions.
-   Add audit logging and tenant-isolation tests.

### Phase 2 --- Master data and order intake

-   Implement parties, contacts, locations, drivers, vehicles, and
    trailers.
-   Build order and shipment workflows.
-   Deliver the first end-to-end vertical slice.

### Phase 3 --- Planning and dispatch

-   Implement loads, trips, stops, assignments, and state transitions.
-   Add validation for resource conflicts and shipment allocation.
-   Build the operations dispatch views.

### Phase 4 --- Driver execution

-   Build the responsive driver portal.
-   Support trip/stop execution, delivery attempts, partial deliveries,
    exceptions, and POD.
-   Add secure media uploads and access controls.

### Phase 5 --- Operational hardening

-   Add tracking timelines, notifications, retryable background jobs,
    and outbox processing.
-   Improve observability, access reviews, backups, and recovery
    procedures.
-   Expand integration and end-to-end test coverage.

### Phase 6 --- Integrations and expansion

-   Add external document imports.
-   Evaluate routing, customer-facing tracking, reporting, billing, and
    fiscal issuance based on validated business needs.

## 7. Definition of a useful first release

The first release should let an authorized operator create an order,
turn it into a shipment, plan it on a trip, assign resources, and let a
driver record the delivery outcome with evidence. All records must
remain tenant-isolated, auditable, and recoverable from retryable
operations.
