# PROCESS.md — Truk TMS

Working document: where the project stands, what comes next, and the decisions that
are already locked in. Read this before starting a session. `README.md` holds the
product scope, `IDEA.md` the domain rules; this file holds the _execution_ state.

Last updated after the P1a milestone.

---

## 1. Stack and verified commands

**Stack (settled — do not re-litigate):** Laravel 13 + Inertia 3 + Vue 3 +
Fortify + Wayfinder + Tailwind 4 + shadcn-vue (reka-ui) + Pest 5 + Larastan 7.
`IDEA.md` was written for a Node stack (Clerk/Zod/Drizzle); its **domain rules**
apply, its stack does not.

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

| Phase   | Scope                                                                      | Status     |
| ------- | -------------------------------------------------------------------------- | ---------- |
| **P0**  | Bilingual shell (ES/EN), locales, operational roles, tenancy foundation    | ✅ Done    |
| **P1a** | Parties (+contacts), Locations, ⌘K command palette                         | ✅ Done    |
| **P1b** | Drivers, Vehicles, Trailers, Compliance documents                          | ⏭ **Next** |
| **P2**  | Order intake: service requests → shipments/items/packages                  | ⬜         |
| **P3**  | Planning and dispatch: loads, trips, stops, assignments, state machines    | ⬜         |
| **P4**  | Driver execution: portal, delivery attempts, POD, expenses                 | ⬜         |
| **P5**  | Hardening: tracking timeline, notifications/outbox, audit UI, file storage | ⬜         |
| **P6**  | Integrations: CFDI import, toll catalog, customer portal                   | ⬜         |

Milestone from `README.md`: _create a customer and location → create an order and
shipment → list the records within the correct tenant._ P1a covers the first half.

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
   allow-list in `tests/Feature/Tenancy/TenancyConventionsTest.php`. The test is the
   enforcement; the allow-list is a deliberate, reviewable exception.
6. **Money is integer minor units; weight is integer grams; volume is integer cm³.**
   No floats on anything summed or compared. Convert at the form boundary only.
7. **The database is the last line of defence.** Prefer a constraint over app logic,
   and when a soft delete is involved use a **partial unique index**
   (`WHERE deleted_at IS NULL`) so a deleted record never reserves a value forever.
   Validation rules must agree with the constraint — P1a shipped a bug where they did
   not (see §4.4).
8. **Every user-facing string is bilingual.** The English text _is_ the key.

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
  timezone on locations** yet.
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

---

## 5. Domain logic to honour (the trap list)

Carry these forward into P2–P4. Each one has burned a real TMS.

1. **Status derivation.** Stops and trips transition explicitly; **shipments are
   derived** from the sum of successful delivery-attempt lines, in one idempotent use
   case. Never mark a shipment delivered because a stop completed.
2. **No `trip_id` on a shipment.** Shipments reach trips _through stops_
   (`stop_shipments`). This is what makes one request → many trips, partial pickups,
   and reschedules work.
3. **Quantity accounting.** Delivered = `SUM(attempt lines where success)`; guard
   `<= planned`; record discrepancies, never clamp silently.
4. **Idempotency.** A driver on 3G submits twice. Client-generated UUID + a unique
   column on attempts/expenses/POD, not a generic middleware. Repeat returns the
   existing row.
5. **Capacity.** Per trip = sum over **distinct** shipments on its stops (a shipment
   on two stops must not count twice). Effective limit is `min(vehicle, trailer)`.
   Block, with a permissioned override that records a reason — overweight fines are
   why the block exists.
6. **Resource conflicts.** One driver/vehicle/trailer per overlapping window:
   `lockForUpdate` on the resource row inside the transaction, then check the window.
   App-level so it works on SQLite and Postgres; note the ceiling (Postgres exclusion
   constraints when throughput demands it).
7. **Out-of-order scans.** Package state derives from the latest event by
   `occurred_at`, not insert order, through an allowed-transition map.
8. **Casetas.** Toll price depends on booth × axle configuration × direction — a
   catalog that cannot be won up front. P4 ships free-entry expenses with a receipt
   photo; a booth catalog with per-axle prices is P6.
9. **Snapshots.** Freeze party/address/commercial data on the shipment at creation,
   and the address on the stop. Otherwise editing a location rewrites delivered
   history and invalidates every POD.
10. **Timezone.** Store UTC (`timestampTz`), keep a timezone on the team and the trip.
    Mexico dropped DST in 2022 _except_ border municipalities — never hardcode −6/−7.
11. **Files.** Private disk + temporary signed URLs. POD evidence append-only;
    corrections create a new record. Compress photos client-side.
12. **Retryable jobs.** `ShouldBeUnique`, idempotent, and they receive `team_id`
    explicitly.

---

## 6. Next: P1b — fleet and compliance

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

## 7. Then: P2 → P6 outline

### P2 — order intake

`service_requests` (+items) → `shipments` (+items, +packages). Freeze the snapshot
fields. Derive the shipment totals (weight/volume/pieces) from items. **Do not put
`trip_id` on a shipment.** First real vertical slice: list the records of the tenant.

### P3 — planning and dispatch

`loads` (decided: a separate entity), `trips`, `trip_assignments`, `stops`,
`stop_shipments`. State machines via one `TransitionStatus` action that validates,
persists, writes the tracking event, and audits. Capacity guard, resource-conflict
guard, and a dispatch board as the primary UX (dragging shipments onto a trip with a
live capacity gauge) — not a planning tab.

### P4 — driver execution

Responsive driver portal (its own shell: _next stop_, three big targets), delivery
attempts with partial quantities, POD (signature, photos, recipient, consent),
expenses with a receipt photo, and an offline/cold-start retry queue. Add
`drivers.user_id` here.

### P5 — hardening

Tracking timeline, notifications (start with `DB::afterCommit()` + queued listeners;
move to an outbox when customer notification becomes contractual), audit UI, real
file storage with temporary URLs, retention/pruning, exports.

### P6 — integrations

CFDI import boundary (never compute taxes), toll-booth catalog, customer portal,
routing/ETA providers, billing.

---

## 8. i18n workflow

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

## 9. Testing conventions

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
- Don't add a test for framework behaviour. Do add one when a _project_ rule is at
  stake (the tenancy convention test is the model to copy).

---

## 10. Open questions for the user

1. **Cross-tenant carrier access** — do carriers eventually log in and see trips
   offered to them? Today they are plain parties. If yes, that becomes a first-class
   phase (sharing grants, invitations, per-record visibility).
2. **Hours of service / driver shifts** — is `trip_assignments` enough, or do we need
   duty-time tracking? It changes the driver model.
3. **Multi-currency** — MXN only for now. Cross-border work would force a currency
   column on money and the caseta catalog.
4. **Warehouse vs dispatcher permissions** — both currently manage the whole catalog.
   If warehouse staff must not edit customers, split `ManageCatalog`.
5. **Partial loads** — real LTL will need a shipment on multiple trips at once
   (quantities split per leg). The schema allows it via stops; confirm it is in scope
   before P3 freezes the planning UI.

---

## 11. Known debt and deferred items

| Item                                                                                        | Trigger to pick it up                 |
| ------------------------------------------------------------------------------------------- | ------------------------------------- |
| `NavFooter.vue` is unused after the starter-kit links were removed                          | delete with the next nav change       |
| No "restore" action for soft-deleted records                                                | a user asks for a deleted record back |
| State/postal code are free text                                                             | SEPOMEX address catalog (P6)          |
| Full Spanish `validation.php` covers common rules only; anything else falls back to English | a missing message is reported         |
| Contact "primary" flag, party multi-type                                                    | a workflow demands it                 |
| Compliance document files                                                                   | P5 (private storage + signed URLs)    |
| `ManageCatalog` is coarse                                                                   | see §10.4                             |
| `⌘K` has no visible affordance in the header                                                | first UX polish pass                  |
| Load/Trip feasibility is unmodelled (no drive-time estimate)                                | P3, and only as a warning             |

---

## 12. File map

```
app/Concerns/BelongsToTeam.php        tenancy scope + team_id fill + team() relation
app/Data/TeamContext.php              per-request/job tenant, set by EnsureTeamMembership
app/Enums/                            Locale, TeamRole, TeamPermission, PartyType, …
app/Http/Controllers/Parties|Locations|SearchController.php
app/Http/Middleware/                  EnsureTeamMembership (priority: before binding), SetLocale
app/Policies/TeamPolicy.php           manageCatalog
app/Rules/Rfc.php                     RFC shape (no check digit yet)
lang/es.json                          the interface dictionary (English key → Spanish)
lang/{es,en}/                         roles, party_types, validation, auth, passwords
resources/js/lib/i18n.ts              t() and the global $t
resources/js/composables/useFilteredList.ts   URL-backed, debounced list filtering
resources/js/components/CommandPalette.vue    ⌘K: nav commands + server search
resources/js/components/catalog/      master–detail building blocks
resources/js/pages/                   parties/, locations/ (one page per area)
tests/Feature/Tenancy/                trait behaviour + the model convention test
```

Use `php artisan make:*` for new files, `--no-interaction`, and follow the sibling
file's structure before writing a new one.
