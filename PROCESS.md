# PROCESS.md — Truk TMS

Working document: where the project stands, what comes next, and the decisions that
are already locked in. Read this before starting a session. `README.md` holds the
product scope, `IDEA.md` the domain rules; this file holds the _execution_ state.

Last updated after the P1b milestone, with the scope locked to a **full-lifecycle
TMS**: fiscal compliance (SAT/CFDI/Carta Porte), billing/settlement, and route
optimization are now in scope, not deferred.

---

## 1. Stack and verified commands

**Stack (settled — do not re-litigate):** Laravel 13 + Inertia 3 + Vue 3 +
Fortify + Wayfinder + Tailwind 4 + shadcn-vue (reka-ui) + Pest 5 + Larastan 7.
PostgreSQL is the production source of truth (`database.sqlite` is the local dev
DB). `IDEA.md` was originally written for a Node stack (Clerk/Zod/Drizzle); its
**domain rules** apply, its stack does not.

| Task             | Command                                                          |
| ---------------- | ---------------------------------------------------------------- |
| Tests            | `php artisan test --compact`                                     |
| One file         | `php artisan test --compact tests/Feature/Parties/PartyTest.php` |
| Formatter (PHP)  | `vendor/bin/pint --format agent`                                 |
| Static analysis  | `vendor/bin/phpstan analyse --memory-limit=1G`                   |
| JS lint + format | `npx vp check --fix resources/js`                                |
| Types            | `npm run types:check`                                            |
| Build            | `npm run build`                                                  |
| Routes           | `php artisan route:list --except-vendor`                         |
| Wayfinder        | `php artisan wayfinder:generate --with-form`                     |

`composer test` runs lint + types + the full suite.

### Gotchas that cost time before

- **`--with-form` is mandatory** when running `wayfinder:generate` by hand. The Vite
  plugin passes it (`formVariants: true` in `vite.config.ts`); without it every
  `.form()` call disappears from the generated routes and `vue-tsc` fails everywhere.
- **`vp check --fix` on the whole repo reformats `IDEA.md` and `README.md`**, which
  already fail the markdown formatter. Only ever pass paths: `npx vp check --fix resources/js`.
- **A shell that exported the old `.env` shadows the file.** `.env` is loaded
  immutably, so an exported `APP_ENV=local` disables Laravel's test-mode CSRF bypass
  (419 on every POST) and an exported `APP_LOCALE` breaks the locale tests. If the
  suite fails that way, run it as:
    ```sh
    env -u APP_ENV -u APP_LOCALE -u APP_NAME -u APP_MAINTENANCE_DRIVER \
        -u BROADCAST_CONNECTION -u CACHE_STORE -u DB_CONNECTION -u MAIL_MAILER \
        -u QUEUE_CONNECTION -u SESSION_DRIVER php artisan test --compact
    ```
- **PHPStan hits the `php.ini` memory limit** at the default 128M, which also breaks
  `composer test` and `composer types:check`. Pass `--memory-limit=1G` or raise
  `memory_limit`.
- `database/database.sqlite` is the dev database and is empty, so
  `php artisan migrate:fresh` is safe.
- `tests/Feature/LocaleTest.php` proves the locale chain. `Symfony\Request::create()`
  injects `Accept-Language: en-us,en` in tests, so a test that asserts Spanish must
  send a header or a session value.

---

## 2. Status board

| Phase    | Scope                                                                                          | Status     |
| -------- | ---------------------------------------------------------------------------------------------- | ---------- |
| **P0**   | Bilingual shell (ES/EN), locales, operational roles, tenancy foundation                         | ✅ Done    |
| **P1a**  | Parties (+contacts), Locations, ⌘K command palette                                              | ✅ Done    |
| **P1b**  | Drivers, Vehicles, Trailers, Compliance documents                                               | ✅ Done    |
| **P2**   | Order intake: orders → shipments/items/packages + location geodata. Rate cards & quotes pending   | 🚧 In progress |
| **P3**   | Planning & dispatch: loads ✅, trips ✅, stops, capacity, dispatch board                         | 🚧 In progress |
| **P4**   | Driver execution: offline PWA, scans, delivery attempts, POD, exceptions, fuel expenses          | ⬜         |
| **P5**   | Visibility & hardening: tracking timeline, telematics, geofence/ETA, outbox, audit UI, files     | ⬜         |
| **P6**   | Fiscal compliance: SAT catalogs, CFDI 4.0 + Carta Porte 3.0/3.1, PAC adapter, dispatch gate     | ⬜         |
| **P7**   | Pricing & billing: rate engine, accessorials, fuel surcharge, 3-way match, invoicing, settlement | ⬜         |
| **P8**   | Optimization: routing, multi-stop sequencing, LTL→FTL consolidation, constraint engine           | ⬜         |
| **P9**   | Integrations: EDI, ERP/WMS, toll catalog + telepeaje, customer/carrier portal, SaaS billing      | ⬜         |
| **P10**  | Analytics & KPIs: OTIF, cost per ton/km, utilization, safety, carrier rating                     | ⬜         |

**Ordering rationale.** The audit guide treats SAT/Carta Porte as an upstream
gatekeeper, but building a PAC integration before the operational records exist
would be premature. We therefore split compliance:

- The **compliance data model and validation rules** land with the operational
  entities (P2/P3) so every shipment/trip carries the fields and the hard gate has
  something to validate.
- The **certified issuance workflow** (PAC stamping/cancellation, XML) lands in P6,
  behind an adapter boundary designed in P3.
- **Coordinates and timezone** must land by P2/P3: routing, ETA, geofence, and
  toll costing are impossible without them.

Milestone from `README.md`: _create a customer and location → create an order and
shipment → list the records within the correct tenant._ P1a covers the master data and
P1b the fleet; **P2 is the remaining half of the milestone** (order → shipment → list).

---

## 3. Non-negotiable invariants

Break one of these and the bug will be silent and expensive.

1. **Tenant context precedes route model binding.** `EnsureTeamMembership` is
   registered in the middleware priority list _before_ `SubstituteBindings`
   (`bootstrap/app.php`). This is load-bearing: it verifies membership first (so a
   non-member gets 403 for any id and cannot probe for existence) and sets
   `TeamContext` to the URL's team before tenanted models bind (so a deep link into
   another of your teams works). Do not "tidy" this away.
2. **`BelongsToTeam` is fail-closed.** Querying or creating a tenanted model with no
   context throws `LogicException`. Background work must opt in:
   `app(TeamContext::class)->run($teamId, fn () => ...)`. Jobs never infer the tenant
   from a request — a queue worker has no request.
3. **`team_id` is never mass assignable.** Writes go through the team:
   `$current_team->parties()->create($request->validated())`. A nested record is
   created through the team too, because a parent relation does not fill `team_id`:
   `$current_team->partyContacts()->create([... , 'party_id' => $party->id])`.
4. **Factories always declare the tenant:** `Party::factory()->for($team)->create()`
   (chain twice for a nested record: `->for($team)->for($party)`).
5. **Every new model in `app/Models` must use `BelongsToTeam`** or be added to the
   allow-list in `tests/Feature/Tenancy/TenancyConventionsTest.php`. The only legitimate
   allow-list members are **global catalogs** (see #10). The test is the enforcement.
6. **Money is integer minor units; weight is integer grams; volume is integer cm³.**
   No floats on anything summed or compared. Convert at the form boundary only.
   **Every money value carries a currency** (MXN default; cross-border needs more).
7. **The database is the last line of defence.** Prefer a constraint over app logic,
   and when a soft delete is involved use a **partial unique index**
   (`WHERE deleted_at IS NULL`) so a deleted record never reserves a value forever.
   Validation rules must agree with the constraint — P1a shipped a bug where they did
   not (see §4.4).
8. **Every user-facing string is bilingual.** The English text _is_ the key.
9. **Compliance gates dispatch.** A trip/shipment cannot be dispatched until the
   fiscal/transport payload validates. Overrides are permissioned and record who,
   when, and why — never silent.
10. **Global reference data is not tenant data.** SAT/SCT claves, units of measure,
    tariff codes, and toll booths are shared, versioned reference tables. They are
    queried without `BelongsToTeam` and must never be mutated by tenant requests.
11. **Fiscal and delivery evidence is append-only and files are private.** A stamped
    CFDI, a POD, a scan, and a tracking event are never overwritten; corrections
    create a new record. Files live on a private disk behind short-lived URLs.
12. **Every external provider is behind an interface.** PAC, maps/routing, telematics,
    notifications, storage, and billing all sit behind a contract with a fake for the
    test suite. No vendor SDK leaks into domain code.

---

## 4. Decision log

### 4.1 Product and tenancy (settled by the user)

- **Keep Laravel + Vue.** Reuse the existing auth, teams, and component library.
- **`Team` = tenant = the operating company.** Label it _Organización / Organization_
  in the UI. No separate `Tenant` or `Organization` entity.
- **Subcontracted carriers are `Party` records without logins.** Cross-tenant carrier
  accounts (a carrier logging into their own team and seeing shared trips) are
  deferred; it is the single most expensive thing in the spec.
- **`Load` and `Trip` stay separate entities** ("keep Load and Trip separate"), even
  though P1a does not need `Load` yet. Model `Load` in P3 when trips are built.
- Carriers are modelled as parties; a resource points at the subcontractor with a
  nullable `carrier_party_id` (null = own fleet).

### 4.2 Computed in P0/P1a

- **ES is the default locale**; EN is the fallback and the source of the dictionary.
- Roles: `owner` (60) > `admin` (50) > `dispatcher` (40) > `warehouse` (30) >
  `driver` (20) > `member` (10). `driver`/`member` are read-only.
- One coarse permission guards master data: `TeamPermission::ManageCatalog`, checked
  via `Gate::authorize('manageCatalog', $team)` (`TeamPolicy`). Per-entity permissions
  only when the rules genuinely differ.
- Master data is **soft deleted only**; there is no `is_active` flag. Deleting hides
  the record and "inactive" is not a separate concept yet.
- `Party.type` is single-valued (customer | carrier | supplier). A company that is
  both needs two records until a real case appears.
- Addresses are free text except the individual fields; **no coordinates, no
  timezone on locations** yet. _(Superseded by §4.6: coordinates and timezone land
  in P2 so routing, ETA, geofencing, and toll costing are possible.)_
- UI term: Party = **Tercero** (cliente / transportista / proveedor); Location =
  **Ubicación**.

### 4.3 Schema conventions

- Every tenanted table carries `team_id` (denormalised on purpose: defence in depth
  and single-table filtering), with `cascadeOnDelete` on the team.
- Human-facing uniqueness is per tenant and partial (`parties_team_id_rfc_unique`).
- Indexes mirror the actual access pattern: `(team_id, name)`, `(team_id, type)`,
  `(team_id, postal_code)`.
- Names in the database are English and language-neutral. Never store a translated
  label.

### 4.4 Fixed during P1a (do not reintroduce)

- A plain `unique(['team_id','rfc'])` plus a `withoutTrashed()` validation rule are
  contradictory. One of them blocks re-creating a record whose twin was deleted. Use
  the partial index _and_ keep the rule consistent.
- Deleting a record must not be a dead end. Since the RFC is released on delete, a
  user can re-create the party; that is the reason no "restore" UI exists yet.

### 4.5 Computed in P1b

- **One controller per documentable parent.** A single morph controller cannot
  type-hint `Driver`, `Vehicle`, and `Trailer` at once, and scoped bindings only
  resolve the parent when it is a typed method argument. `DriverDocumentController`,
  `VehicleDocumentController`, and `TrailerDocumentController` each declare their
  parent, so `Route::scopeBindings()` returns 404 for a document reached through the
  wrong unit.
- **Capacity is stored as grams and cm³.** The form takes kg/m³,
  `prepareForValidation()` converts, and `max_payload_kg` / `max_volume_m3` are
  read-only accessors for display. Validation keys stay on the kg/m³ inputs so the
  errors land on the field the user edited.
- **A live plate or licence cannot be reused; a deleted one can.** Same partial-index
  rule as the RFC (see §4.4).
- **Compliance documents are metadata only in P1b.** No file column; the upload lands
  in P5 with private storage and signed URLs. `expires_at` is what the warning reads.
- **The dashboard reads the current tenant through `TeamContext`.** It takes no `Team`
  argument; `EnsureTeamMembership` has already set the context, and the
  `BelongsToTeam` scope does the filtering.

### 4.6 Full-scope decision (this change)

The product is no longer a lean dispatch tool. Locked in:

- **Tax/fiscal compliance is in scope.** CFDI 4.0 (Ingreso/Traslado) + Carta Porte
  3.0/3.1 through a PAC adapter; we prepare and validate payloads, the provider
  stamps and cancels. We **never compute taxes ourselves**.
- **Billing/settlement is in scope.** Rate engine, accessorials, fuel surcharge,
  3-way match, customer invoicing, and carrier settlement.
- **Optimization is in scope.** Routing, multi-stop sequencing, consolidation, and a
  constraint engine — but optimization is **advisory and guarded**: it proposes, the
  dispatcher confirms, and capacity/compliance hard gates still apply.
- **The driver app is an offline-first PWA**, not a native app, for now. It must
  work with no signal and sync idempotently.
- **Locations gain coordinates and a timezone** (P2) because routing/ETA/geofence/toll
  costing depend on them.
- **Global catalogs exist** outside tenant scope (SAT, SCT, toll booths, units).
- **Multi-currency is anticipated** in the money model even if MXN ships first.

---

## 5. Domain logic to honour (the trap list)

Carry these forward into every phase. Each one has burned a real TMS.

1. **Status derivation.** Stops and trips transition explicitly; **shipments are
   derived** from the sum of successful delivery-attempt lines, in one idempotent use
   case. Never mark a shipment delivered because a stop completed.
2. **No `trip_id` on a shipment.** Shipments reach trips _through stops_
   (`stop_shipments`). This is what makes one request → many trips, partial pickups,
   and reschedules work.
3. **Quantity accounting.** Delivered = `SUM(attempt lines where success)`; guard
   `<= planned`; record discrepancies, never clamp silently.
4. **Idempotency.** A driver on 3G submits twice. Client-generated UUID + a unique
   column on attempts/scans/expenses/POD, not a generic middleware. Repeat returns the
   existing row.
5. **Capacity is more than a ceiling.** Effective limit is `min(vehicle, trailer)`,
   but also track utilization (weight/volume fill %) and, for the legal check,
   per-axle and gross weight plus dimensions (NOM-012-SCT-2). An almost-empty truck
   is a consolidation candidate; an overloaded one is blocked. Overweight fines are
   why the block exists.
6. **Resource conflicts.** One driver/vehicle/trailer per overlapping window:
   `lockForUpdate` on the resource row inside the transaction, then check the window.
   App-level so it works on SQLite and Postgres; note the ceiling (Postgres exclusion
   constraints when throughput demands it).
7. **Out-of-order scans.** Package state derives from the latest event by
   `occurred_at`, not insert order, through an allowed-transition map.
8. **Casetas.** Toll price depends on booth × axle configuration × direction — a
   catalog that cannot be won up front. Ship free-entry expenses with a receipt photo
   first; the booth catalog with per-axle prices lands in P9. Design the expense shape
   so the catalog can attach later.
9. **Snapshots.** Freeze party/address/commercial data on the shipment at creation,
   the address on the stop, and the **fiscal payload on the trip** at stamping.
   Otherwise editing a location rewrites delivered history and invalidates every POD
   and CFDI.
10. **Timezone.** Store UTC (`timestampTz`), keep a timezone on the team, the
    location, and the trip. Mexico dropped DST in 2022 _except_ border municipalities
    — never hardcode −6/−7.
11. **Files.** Private disk + temporary signed URLs. POD evidence append-only;
    corrections create a new record. Compress photos client-side.
12. **Retryable jobs.** `ShouldBeUnique`, idempotent, and they receive `team_id`
    explicitly.
13. **Compliance is a gate, not a warning.** Validate the payload before allowing
    dispatch. An override is permissioned and audited. Prepare Carta Porte data as a
    snapshot; never regenerate a stamped document from live data.
14. **PAC calls are slow and can fail.** Stamp asynchronously in a queued job with
    retry/backoff; show the document as `pending`/`stamped`/`error` and never block the
    UI. Cancellation never deletes the original.
15. **Fiscal catalogs are versioned.** SAT codes change; a stamped document must be
    able to reference the catalog version in force when it was issued.
16. **Package custody.** A package's state comes from scans, and a package can be
    split across partial deliveries. Reconcile the manifest against scanned reality
    and surface mismatches as exceptions.
17. **Fuel and tolls are first-class costs.** Capture liters, price/liter, odometer,
    and tank so fuel efficiency (km/L) and fuel surcharges are computable. Do not
    model fuel as a generic "expense" with only an amount.
18. **Money needs a currency.** Even with MXN only today, the column must exist;
    cross-border totals mix currencies and must never be summed naively.
19. **Global vs tenant catalogs.** A tenant request must never be able to write a
    global catalog row. Seed/version them; keep them out of `BelongsToTeam`.
20. **Optimization is advisory.** The optimizer proposes plans; it must never
    silently override a dispatcher, violate a hard capacity/compliance gate, or lose
    a manually pinned stop. Every accepted plan is attributable.

---

## 6. P1b — fleet and compliance (delivered)

Goal: the resources and the legality data that P3 needs in order to answer _"is this
unit allowed to roll today, and is it free?"_

### 6.1 Schema

```
drivers                id, team_id, carrier_party_id?, name, phone, license_number?,
                       license_expires_at?, timestamps, softDeletes
                       partial unique (team_id, license_number) where deleted_at is null
vehicles               id, team_id, carrier_party_id?, name (unit code), plate,
                       configuration, max_payload_grams, max_volume_cm3?, timestamps, softDeletes
                       partial unique (team_id, plate) where deleted_at is null
trailers               id, team_id, carrier_party_id?, name, plate, configuration,
                       max_payload_grams, max_volume_cm3?, timestamps, softDeletes
compliance_documents   id, team_id, documentable_type/id, type, number?, issued_at?,
                       expires_at?, notes?, timestamps, softDeletes
                       index (team_id, documentable_type, documentable_id)
```

- `carrier_party_id` nullable → null means own fleet; set means a subcontracted
  party. Validate it with `Rule::exists('parties','id')->where('team_id', ...)`.
- `ComplianceDocumentType` enum: `License | Insurance | Verification | Permit |
  Inspection | Other`, labels in `lang/{es,en}/compliance_document_types.php`.
- **No file column in P1b.** The upload lands in P5 with private storage and signed
  URLs; a metadata-only row is still useful (it is what expiry checks read).
- **No `drivers.user_id` yet.** Add it in P4 when the portal needs to link a login to
  a driver.
- Capacity arrives in **kg/m³ in the form, grams/cm³ in the database.** Convert in
  `prepareForValidation()` and expose `max_payload_kg` as a float for display only.

### 6.2 Backend checklist

- [ ] Migrations: `drivers`, `vehicles`, `trailers`, `compliance_documents`
      (partial unique indexes via `DB::statement`, as in `create_parties_table`).
- [ ] Enums: `ComplianceDocumentType` (+ labels in both locales).
- [ ] Models: `Driver`, `Vehicle`, `Trailer`, `ComplianceDocument` — all
      `BelongsToTeam` + `SoftDeletes`; `ComplianceDocument` gets
      `documentable()` morphTo plus a `documents()` morphMany on the three parents.
- [ ] Factories (+ `for($team)` in every test), with a plausible Mexican plate
      (`ABC-12-34`) and configuration.
- [ ] `App\Rules\` — plate/license normalisation lives in the FormRequest
      (`strtoupper`, trim), not a rule.
- [ ] Requests: `SaveDriverRequest`, `SaveVehicleRequest`, `SaveTrailerRequest`,
      `SaveComplianceDocumentRequest`.
- [ ] Controllers: `Fleet\{DriverController,VehicleController,TrailerController}` and
      one nested `ComplianceDocumentController` (morph, scoped bindings).
- [ ] Routes under the `{current_team}` prefix; `Route::scopeBindings()` for the
      nested document routes.
- [ ] Extend the `search` endpoint with vehicles and drivers (the palette is the
      primary way to jump to a unit).
- [ ] `Team` relations: `drivers()`, `vehicles()`, `trailers()`, `complianceDocuments()`.

### 6.3 Frontend checklist

- [ ] **Extract the shared list layout now** — parties and locations already
      duplicate ~80 lines of list/pagination markup and fleet makes it a third use.
      `components/catalog/CatalogListLayout.vue` (header, toolbar slot, scrolling
      list slot, pagination footer) + keep `useFilteredList` as is.
- [ ] `components/catalog/ComplianceDocsSection.vue` + `DocumentRow.vue` (three uses:
      driver, vehicle, trailer) showing `expires_at` with an **expiry badge**
      (expired / expires within 30 days / valid).
- [ ] Pages: `pages/drivers/Index.vue`, `pages/vehicles/Index.vue`,
      `pages/trailers/Index.vue`, each following the master–detail pattern
      (`:key="record.id"` on the detail component to reset the form).
- [ ] Sidebar: a second nav group. Give `NavMain` a `label` prop (or a `groups`
      array) and add _Flota_ → Conductores / Unidades / Remolques.
- [ ] Dashboard: replace the placeholder grid with today's fleet warnings (units with
      expired documents). Cheap, and it makes the dashboard real.

### 6.4 Tests

CRUD + validation + authorization + isolation for each entity, plus:

- a unit can only be linked to a carrier party **of the same team** (validation);
- reusing a plate/licence after a soft delete works (partial index) and is blocked
  while the record is live;
- kg input is stored as grams;
- a compliance document cannot be attached through another team's vehicle
  (scoped bindings → 404);
- the list payload marks an expired licence/insurance, so the UI can warn.

---

## 7. Roadmap: P2 → P10

### P2 — order intake, rates, and packages

Goal: turn a commercial request into trackable, priced operational units, and give
every downstream record the fields compliance and routing will need.

**Delivered (slice 1):**

- `locations.latitude/longitude/timezone` (schema, request validation, factory,
  location form/detail UI) — unblocks routing/ETA/toll work later.
- `orders` (+`order_items`) with server-assigned numbers, customer snapshot,
  integer-gram/cm³ lines, per-tenant partial unique number, CRUD + master–detail UI.
- `shipments` (+`shipment_items`, `packages`) created from an order through
  `ConvertOrderToShipment`: copies lines, derives weight/volume/pieces, snapshots
  pickup/delivery addresses, and can generate packages.
- Order status and shipment status **transition maps** enforced on update.
- `TeamPermission::ManageOperations` (Owner/Admin/Dispatcher; Warehouse excluded).
- Search extended to orders and shipments; sidebar + ⌘K entries; bilingual copy.
- Feature tests: orders, conversion, shipments, packages, geodata, scoped bindings,
  authorization, and tenant isolation.

**Remaining in P2:**

- Rate cards/rates and quotes (the pricing engine v1) — currently planned here but
  could move to P7 with the full rate engine.
- Order-line partial allocation across shipments (LTL) and package-level scanning
  (P4).

- **Entities:** `rate_cards`, `rates`, `service_requests` (+items), `orders`,
  `shipments` (+items, +packages), `locations.coordinates`/`timezone`.
- **Rate engine (v1):** flat and distance/weight/volume/zone rates; quote snapshot.
- **Snapshots:** freeze party/address/commercial data on the shipment at creation.
- **Shipment totals** derive from items; packages carry a barcode/SSCC and a
  lifecycle.
- **Compliance fields on the payload** (weight, packaging, HazMat flags, claves) so
  the P6 gate has data to validate.
- **AC:** the README milestone completes — create customer + location → order →
  shipment → list within the correct tenant; and a quote prices an order.
- **Tests:** rate math in minor units, snapshot immutability, package identity
  uniqueness per tenant, coordinates validation.

### P3 — planning and dispatch

Goal: answer _"can this unit move this load, legally and physically, and is it free?"_

**Delivered (slice 1):**

- `loads` (group shipments for planning) with server-assigned numbers, a
  `LoadStatus` transition map, and `shipments.load_id`.
- Attach/detach shipments to a load through scoped nested routes; planner UI with
  per-load totals; search, sidebar and ⌘K entries; bilingual copy.
- Feature tests: CRUD, grouping, transitions, scoped bindings, authorization,
  tenant isolation.

**Remaining:** stops + `stop_shipments` (ordered sequencing), the capacity guard,
and the dispatch board.

**Delivered (slice 2):**

- `trips` (planned window, timezone, `TripStatus` transition map) and
  `trip_assignments` (append-only history).
- Driver/vehicle/trailer assignment through `AssignTripResources`: the resource
  row is locked for update and checked for **overlapping open trips**, so two
  dispatchers cannot book the same unit at once.
- Dispatch planner page, form sheet, detail with resource selects and assignment
  history; search, sidebar and ⌘K entries; bilingual copy.
- Feature tests: CRUD, transitions, assignment + history, overlap conflict,
  non-overlap, cross-team resources, authorization and tenant isolation.

- **Entities:** `loads`, `trips`, `trip_assignments`, `stops`, `stop_shipments`,
  `trip_compliance` (payload snapshot), `resource_reservations`.
- **One `TransitionStatus` action** that validates, persists, writes the tracking
  event, and audits.
- **Capacity guard:** planned weight/volume vs `min(vehicle, trailer)`; utilization
  display; per-axle/gross limits where data exists; permissioned override with reason.
- **Resource-conflict guard:** `lockForUpdate` per resource across overlapping windows.
- **Dispatch board** as the primary UX: drag shipments onto a trip with a live
  capacity/utilisation gauge, not a planning tab.
- **PAC adapter boundary** defined here (interface + fake); issuance is P6.
- **AC:** dispatch is blocked while a hard gate fails; a dispatcher can override with
  a reason; the board shows fill and conflicts in real time.

### P4 — driver execution

Goal: a driver can run a trip end-to-end on a phone, offline.

- Responsive driver portal (its own shell, mobile-first) built as an **offline-first
  PWA**: local cache of the assigned trip, a retry queue that syncs idempotently.
- Delivery attempts with partial quantities; returns; failed-delivery reasons.
- **Package scans** (barcode/QR) with custody transitions.
- POD: signature, photos, recipient, consent, location; append-only.
- **Incidents/exceptions** entity and workflow.
- **Fuel/expenses:** liters, price/liter, odometer, tank, receipt photo; tolls as
  generic expenses until the P9 catalog.
- Add `drivers.user_id` to link a login to a driver.
- **AC:** airplane-mode test passes; a double-submit returns the same row.

### P5 — visibility and hardening

Goal: everyone can see the truth as it happens, and the system survives failure.

- Tracking timeline (append-only), audit UI, notifications via
  `DB::afterCommit()` + queued listeners; migrate to an outbox.
- **Telematics aggregator:** hardware-agnostic adapter, position/telemetry storage,
  multi-provider handoff.
- Geofences and predictive ETA; proactive arrival/departure/exception alerts.
- Real file storage with temporary URLs; retention/pruning; exports.
- **AC:** a GPS fix from a fake provider advances the timeline and fires a geofence
  event; a failed job retries without duplicating records.

### P6 — fiscal compliance (Mexico)

Goal: issue and cancel legal fiscal documents without blocking operations.

- **Global catalogs:** `catCFDI` subsets (`ClaveProdServ`, `ClaveUnidad`,
  `FracciónArancelaria`, `MaterialPeligroso`), versioned.
- **CFDI 4.0** Ingreso and Traslado, plus **Carta Porte 3.0/3.1** payload builders.
- **PAC adapter:** async stamping (_timbrado_) and cancellation through a certified
  provider; `pending/stamped/error/cancelled` states; retries.
- **Dispatch gate** enforces mandatory fields (weight, packaging, operator RFC,
  vehicle configuration, HazMat); override is permissioned and audited.
- Cross-border: pedimento storage/linkage; drayage workflows.
- **AC:** a trip cannot dispatch with an incomplete payload; a stamped document is
  snapshotted and immutable; cancellation preserves the original.

### P7 — pricing, settlement, and billing

Goal: turn delivered work into money, correctly.

- Rate engine matrix completion (contract/spot, surcharges) and quote→order pricing.
- **3-way match:** rate quote + e-POD quantities + invoice; flag variances.
- **Accessorials:** detention, demurrage, layover, fuel surcharge — computed from
  recorded events.
- Customer **invoicing** and carrier **settlement** as separate flows over the same
  POD; payment status tracking.
- Multi-currency columns in place; MXN first.
- **AC:** a delivered trip produces a matched, itemized invoice; a variance is
  visible and resolvable, never silently absorbed.

### P8 — optimization

Goal: propose better plans without ever breaking a hard rule.

- Routing engine + constraint engine: toll roads, bridge weight/height, driver
  rest/HOS, urban access windows.
- Multi-stop sequencing for time windows and fuel; LTL→FTL consolidation on the
  dispatch board.
- Toll-aware costing (catalog from P9 can upgrade the estimate).
- **Advisory only:** optimizer proposes, dispatcher confirms; pinned stops and hard
  gates are respected; every accepted plan is attributable.
- **AC:** an optimizer run improves a toy plan and cannot violate capacity/compliance.

### P9 — integrations and expansion

- EDI 204/211/214/210; ERP/WMS connectors (SAP, NetSuite, Odoo).
- Toll-booth catalog with per-axle pricing + telepeaje (IAVE/TAG) reconciliation.
- Cross-tenant **customer/carrier portal** (the expensive one; gated behind sharing
  grants).
- SaaS subscription billing; multi-currency rollout.

### P10 — analytics and KPIs

- OTIF, cost per ton/km, utilization, driver safety, carrier rating,
  margin/profitability.
- Export/scheduled reporting; customer-specific dashboards.

---

## 8. UX/UI principles

The product wins on ease of use. These are acceptance criteria, not aspirations.

1. **The primary workflow is the first screen.** Dispatch is a board, driver
   execution is a phone-first next-stop view. Do not bury the job in tabs.
2. **Keyboard-first for office users.** ⌘K palette reaches any record; every list is
   fast to filter; forms are submittable without the mouse.
3. **Mobile-first for drivers.** Big tap targets, minimal typing, works offline,
   resumes cleanly after a cold start. Never require a network round-trip to finish a
   stop.
4. **Progressive disclosure.** Show the next decision, hide the rest. Advanced
   compliance and pricing fields expand on demand but validate visibly.
5. **Always show state.** Skeleton loaders for deferred props, deliberate empty
   states, optimistic updates with rollback, and clear success/error toasts.
6. **Bilingual and consistent.** Every string is translated; money, weight, volume,
   and dates format per locale; English text is the key.
7. **Reuse before inventing.** Check `components/catalog` and `components/ui` first;
   extend `CatalogListLayout` rather than duplicating list markup.
8. **Accessible by default.** shadcn-vue/reka-ui primitives, focus management,
   labelled controls, contrast, and reduced-motion support.
9. **Feedback over silence.** Long operations (stamping, optimization, imports) show
   progress and never block the UI; async jobs report status.
10. **Performance budget.** Lists paginate; the shared locale dictionary is split by
    namespace once it passes ~50 KB; images/signatures compress client-side.

---

## 9. i18n workflow

- Server: `__('English string')`; enums use dotted group files
  (`lang/{es,en}/roles.php`, `party_types.php`, …).
- Client: `{{ $t('English string') }}` in templates, `t('English string')` in
  `<script setup>` (import from `@/lib/i18n`). `$t` is registered globally.
- Spanish copy goes in `lang/es.json` (flat, sorted, `:placeholder` style). The file
  the server shares is the same one the client uses, so a server toast and a client
  label share a key.
- Add a key and its translation in the same change. Then verify:
    ```sh
    python3 - <<'PY'
    import json, re, pathlib
    d = json.load(open('lang/es.json'))
    used = {}
    for p in pathlib.Path('resources/js').rglob('*.vue'):
        for m in re.finditer(r'(?:\$t|(?<![\w$])t)\(\s*([\'"])((?:\\\1|(?!\1).)*)\1', p.read_text(), re.S):
            used.setdefault(m.group(2).replace("\\'", "'"), set())
    php = ''.join(p.read_text() for p in pathlib.Path('app').rglob('*.php'))
    print('untranslated:', [k for k in used if k not in d])
    print('orphans     :', [k for k in d if k not in used and k not in php])
    PY
    ```
- Both lists must print `[]`.
- Recipients get their own language: `User::preferredLocale()` plus
  `App::setLocale()` around notification rendering. Documents and emails are not
  rendered in the operator's language.
- The dictionary ships on every Inertia response (~200 keys ≈ 8 KB raw, ~3 KB gzip).
  Split it by namespace when it passes roughly 50 KB.

---

## 10. Testing conventions

- Pest, feature tests first (`tests/Feature/...`). One file per class/area; name the
  behaviour, not the method.
- Every entity needs: read scope, create, update, delete, validation, the
  authorization failure, the cross-tenant 404, and the non-member 403.
- Tenant isolation asserts **404, not 403** — a 403 confirms that another tenant's
  record exists.
- Assert state with `assertDatabaseHas`/`assertSoftDeleted` rather than
  `$model->fresh()`: the tenancy scope throws when no context is active, which is
  correct and should not be worked around in tests.
- Authorization matrices belong on the policy/rule class; the endpoint test only
  proves the endpoint applies it.
- Every provider interface gets a fake; domain tests never call a real PAC, GPS, or
  map provider.
- Don't add a test for framework behaviour. Do add one when a _project_ rule is at
  stake (the tenancy convention test is the model to copy).

---

## 11. Open questions for the user

1. **Cross-tenant carrier access** — do carriers eventually log in and see trips
   offered to them? Today they are plain parties. If yes, that becomes a first-class
   phase (sharing grants, invitations, per-record visibility).
2. **Hours of service / driver shifts** — is `trip_assignments` enough, or do we need
   duty-time tracking? It changes the driver model and the optimizer.
3. **Multi-currency** — MXN first, but cross-border forces a currency column and
   FX handling. Confirm when to turn it on.
4. **Warehouse vs dispatcher permissions** — both currently manage the whole catalog.
   If warehouse staff must not edit customers, split `ManageCatalog`.
5. **Partial loads** — real LTL will need a shipment on multiple trips at once
   (quantities split per leg). The schema allows it via stops; confirm it is in scope
   before P3 freezes the planning UI.
6. **PAC provider** — which certified provider (Finkok, SW, Edicom, …) do we target
   first? This shapes the adapter and sandbox.
7. **Delivery vs Traslado** — will tenants mostly bill transport (Ingreso + CCP) or
   move their own goods (Traslado + CCP)? It changes the default document flow.

---

## 12. Known debt and deferred items

| Item                                                                                        | Trigger to pick it up                 |
| ------------------------------------------------------------------------------------------- | ------------------------------------- |
| `NavFooter.vue` is unused after the starter-kit links were removed                          | delete with the next nav change       |
| No "restore" action for soft-deleted records                                                | a user asks for a deleted record back |
| State/postal code are free text                                                             | SEPOMEX address catalog (P2/P9)       |
| No coordinates/timezone on locations                                                         | P2 (routing/ETA/toll depend on it)    |
| Full Spanish `validation.php` covers common rules only; anything else falls back to English | a missing message is reported         |
| Contact "primary" flag, party multi-type                                                    | a workflow demands it                 |
| Compliance document files                                                                   | P5 (private storage + signed URLs)    |
| `ManageCatalog` is coarse                                                                   | see §11.4                             |
| `⌘K` has no visible affordance in the header                                                | first UX polish pass                  |
| Load/Trip feasibility is unmodelled (no drive-time estimate)                                | P8 (optimization)                     |
| Soft delete everywhere vs. fiscal immutability                                             | P6 (stamped docs must never vanish)   |

---

## 13. File map

```
app/Concerns/BelongsToTeam.php        tenancy scope + team_id fill + team() relation
app/Data/TeamContext.php              per-request/job tenant, set by EnsureTeamMembership
app/Enums/                            Locale, TeamRole, TeamPermission, PartyType, ComplianceDocumentType,
                                      OrderStatus, ShipmentStatus, PackageStatus
app/Http/Controllers/Parties|Locations|SearchController.php
app/Http/Controllers/Fleet/           Driver|Vehicle|Trailer (+ one document controller per parent)
app/Http/Controllers/Orders/          OrderController, OrderShipmentController
app/Http/Controllers/Shipments/       ShipmentController, ShipmentPackageController
app/Actions/Orders|Shipments/         SaveOrder, ConvertOrderToShipment, StorePackages
app/Http/Middleware/                  EnsureTeamMembership (priority: before binding), SetLocale
app/Policies/TeamPolicy.php           manageCatalog
app/Rules/Rfc.php                     RFC shape (no check digit yet)
lang/es.json                          the interface dictionary (English key → Spanish)
lang/{es,en}/                         roles, party_types, compliance_document_types, validation, auth, passwords
resources/js/lib/i18n.ts              t() and the global $t
resources/js/composables/useFilteredList.ts   URL-backed, debounced list filtering
resources/js/components/CommandPalette.vue    ⌘K: nav commands + server search
resources/js/components/catalog/      CatalogListLayout, party/location/fleet detail + form sheets,
                                      ComplianceDocsSection + DocumentRow + DocumentForm,
                                      OrderFormSheet/OrderDetail/OrderItemsEditor, ShipmentDetail
resources/js/pages/                   parties/, locations/, fleet/{drivers,vehicles,trailers},
                                      orders/, shipments/
tests/Feature/Tenancy/                trait behaviour + the model convention test
tests/Feature/Fleet/                  drivers, vehicles, trailers, compliance documents
tests/Feature/Orders|Shipments/       orders, conversion, shipments, packages
```

Planned (not yet built): `app/Models` gains Load/Trip/Stop/Attempt/Pod/TrackingEvent/
Position/Exception/Expense/CfdiDocument/Rate/Invoice;
`app/Contracts` holds the provider interfaces (Pac, Telematics, Routing, Storage,
Notifications, Billing); `app/Enums` gains the remaining status enums and compliance
types; `routes/api.php` exposes the versioned API.

Use `php artisan make:*` for new files, `--no-interaction`, and follow the sibling
file's structure before writing a new one.
