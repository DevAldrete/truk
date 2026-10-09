# Use Cases — Actors, Flows, Edge Cases

Format: **UC-ID — Name** · Actor · Trigger → Main flow → **Edge cases / Mexico-specific**. Priority: P0 (v1 core) / P1 / P2.

### UC-01 — Create and confirm a transport order · Sales/Customer service · P0
Trigger: customer asks for a move.
Flow: select/create customer → pickup & delivery locations → goods (SAT code, weight, packaging, value) → equipment type → time windows → price (rate card or manual) → confirm.
Edge: new customer without valid RFC/fiscal address; goods without SAT product code; hazmat; weight above vehicle capacity; time window impossible given distance + breaks; customer-specific document requirements.

### UC-02 — Assign truck and driver (dispatch) · Dispatcher · P0
Flow: view unassigned shipments → see available trucks/drivers (permit, insurance, license, hours, location) → assign → system runs compliance checks → trip created.
Edge: expired insurance/license; driver would exceed hours; truck config can't carry weight; driver unavailable after confirmation → reassign with Carta Porte update; double-booking; driver without GPS-capable device.

### UC-03 — Generate and stamp Carta Porte · Dispatcher/Compliance · P0
Flow: trip data → build CFDI + complement → validate → send to PAC → store XML/PDF/QR → send to driver.
Edge: PAC timeout; catalog mismatch; postal code invalid; mid-route vehicle change; cancellation + replacement; multi-stop; international leg; weight discrepancy at loading.

### UC-04 — Driver executes the trip · Driver · P0
Flow: receive instructions (app/WhatsApp) → go to pickup → check-in → load → seal/photo → depart → stops → deliver → capture POD.
Edge: no signal; customer refuses; dock closed; waiting time (estadías) must be evidenced; driver loses phone; driver cannot read app (offer voice/WhatsApp); need to show Carta Porte QR at checkpoint.

### UC-05 — Track and monitor in real time · Monitoring center · P0
Flow: GPS events → geofence arrivals/departures → rules (speed, stop, deviation, no-signal) → alert → protocol (call driver, escalate).
Edge: GPS jammers; signal loss in dead zones vs genuine tampering; false alarms; unauthorized stop in high-risk zone; shift handovers; insurer reporting requirements.

### UC-06 — Handle a theft/security incident · Monitoring + Security + Insurance · P0
Flow: alert → verify → contact driver → notify authorities/insurer/customer → freeze documents/evidence → incident record → claim.
Edge: driver coerced (silent alarm); partial theft; recovered truck; evidence retention; legal reporting windows; customer penalties; data privacy.

### UC-07 — Plan with legal driving limits · Planner · P0
Flow: estimate drive time + mandatory pauses + rest → feasible delivery windows → warn if infeasible → suggest relay/second driver.
Edge: team-driving (two operators); bitácora gaps; route changes; border waits; sources disagree on rule detail → configurable per NOM text.

### UC-08 — Capture trip costs and advances · Driver/Finance · P0
Flow: advance issued → driver uploads receipts / card data auto-feeds → compare planned vs actual → close trip costs.
Edge: cash without receipts; non-deductible expenses; toll tag charges arriving late; fuel theft/siphoning detection; currency for cross-border.

### UC-09 — Driver settlement (liquidación) · Finance/HR · P0
Flow: trip(s) completed → compute pay (per km/trip/%/day) → subtract advances/fines/loans → add bonuses → approve → pay → statement to driver.
Edge: disputed items; payroll vs contractor status; social-security implications; holiday/hours pay; negative balances; mid-trip termination.

### UC-10 — Invoice and collect · Billing/AR · P0
Flow: POD received → rate + extras (stays, maniobras, tolls) → CFDI de Ingreso w/ Carta Porte → send to customer portal/email → payment → REP complement → reconcile.
Edge: customer requires PO number/portal upload; rejected invoices; price disputes; payment terms 30–90 days; partial payments; credit notes; currency conversion; cancellation windows.

### UC-11 — Subcontract a carrier · Procurement/Dispatcher · P1
Flow: shortlist carriers → check compliance (RFC, permits, insurance, SAT standing) → tender → accept → their driver/truck data → Carta Porte chain → track via their GPS/app → pay.
Edge: carrier fails to deliver; fake or cloned identities; low-trust carriers; data sharing limits; margin visibility; multi-layer subcontracting forbidden by customer.

### UC-12 — Quote and rate a lane · Sales · P1
Flow: origin/destination → distance, tolls, fuel, driver costs, empty return probability → margin target → quote → compare with market.
Edge: toll table changes; diesel volatility → fuel surcharge clauses; seasonal peaks; backhaul availability; hazmat/cold-chain surcharges.

### UC-13 — Consolidated (LTL) shipments through a hub · Operations · P1
Flow: collect shipments → cross-dock → build outbound loads → track by shipment and by trip → deliver and POD each consignee.
Edge: damaged pallet; mixed hazmat compatibility; weight distribution; customs/consolidator notification duties; missing/extra pieces.

### UC-14 — Cross-border transfer at the border · Ops/Customs · P1
Flow: Mexican carrier delivers to border yard → documents matched (CCP international, pedimento/DODA reference) → transfer to US carrier → tracking continues → return load.
Edge: customs hold; inspection; CTPAT-type requirements [K]; driver visa/permit limits; trailer interchange; timing around bridge congestion.

### UC-15 — Fleet readiness and maintenance · Fleet manager · P2
Flow: odometer/engine hours → service schedule → block assignment if unsafe → record service → cost allocation.
Edge: breakdown mid-trip; tire tracking; verification/inspection deadlines; warranty.

### UC-16 — Customer self-service tracking · Shipper · P2
Flow: link or portal → status/ETA/POD → alerts → dispute.
Edge: sensitive security details must not leak; multi-tenant privacy; white-label.

### UC-17 — Owner-operator finds load, gets paid fast · Hombre-camión · P2
Flow: browse/accept loads → verify identity → complete trip with phone → POD → quick pay/factoring.
Edge: fraud; low digital literacy; WhatsApp-first; payment disputes; insurance requirements.

### UC-18 — Audit/inspection readiness · Compliance · P1
Flow: authority or customer requests evidence → system exports shipment dossier (Carta Porte, bitácora, POD, GPS, photos, customs refs).
Edge: customs expediente electrónico; retention rules; chain of custody; user permission to export.

### UC-19 — Master data hygiene · Admin · P0
Flow: validate RFCs, postal codes, vehicle/driver docs; renewal reminders; duplicates merge.
Edge: customers with multiple fiscal addresses; delivery addresses with no formal postal code; RFC changes.

### UC-20 — Analytics & margin per lane · Management · P1
Flow: aggregate planned vs actual revenue/cost/time by lane, customer, vehicle, driver.
Edge: allocating shared costs; deadhead attribution; partial-month data; trust in numbers → data lineage.
