# Truk - a TMS Transport Management System

A multi-tenant transportation management platform for managing
customers, carriers, fleets, shipments, dispatch, delivery execution,
and proof of delivery.

The system supports a **mixed fleet model**: companies can use their own
vehicles and drivers or subcontract transportation to third-party
carriers. It is designed to support multiple organizations and business
relationships without mixing their data.

## Product scope

The initial release focuses on transportation operations:

-   Manage organizations, customers, carriers, contacts, and locations.
-   Maintain drivers, vehicles, trailers, assignments, and compliance
    documents.
-   Create quotes, orders, shipments, and shipment items.
-   Group shipments into loads and plan trips, stops, and assignments.
-   Provide a web-based driver experience for executing stops and
    recording delivery outcomes.
-   Capture proof of delivery (POD), signatures, photos, quantities, and
    exceptions.
-   Track shipment and trip progress with an auditable event history.
-   Import external documents, with fiscal-document issuance
    integrations left for a later phase.

## Core business flow

1.  Register an organization and its customers, carriers, locations, and
    resources.
2.  Create a quote or order.
3.  Convert the order into one or more shipments.
4.  Group shipments into a load and assign the load to a trip.
5.  Plan stops and assign the driver, vehicle, and trailer.
6.  The driver executes each stop and records quantities, outcomes, and
    exceptions.
7.  Store proof of delivery and update shipment status.
8.  Notify relevant users and retain an auditable event history.

## Important design rules

-   Enforce tenant isolation on every data access and mutation.
-   Treat authentication and authorization as separate concerns.
-   Keep orders, shipments, loads, trips, stops, delivery attempts, and
    proof of delivery as distinct entities.
-   Preserve historical snapshots of addresses, parties, and commercial
    details where operational records depend on them.
-   Use explicit state transitions rather than allowing arbitrary status
    updates.
-   Use database transactions for multi-record operations and an outbox
    for reliable event publishing.
-   Use idempotency keys for commands that may be retried.
-   Store timestamps in UTC and keep time-zone context for local
    schedules.
-   Store files in private object storage; persist metadata in
    PostgreSQL and use short-lived access URLs.
-   Never trust a tenant ID supplied by the client without checking the
    authenticated user's membership and permissions.

## Initial milestone

Deliver one complete vertical slice before broadening the feature set:

**Create a customer and location → create an order and shipment → list
the records within the correct tenant.**
