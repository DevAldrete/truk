# Carta Porte (CFDI + Complemento Carta Porte 3.1) — Primer for TMS Builders

**Status:** version 3.1, mandatory since 17 Jul 2024 [R]. This is the single most Mexico-specific, highest-risk piece of a TMS. Primary sources: SAT's Carta Porte page (standard PDF, XSD schema, XSLT original-string sequence, catalogs, FAQs).

## 1. What it is
A CFDI (electronic invoice XML, stamped by a PAC) enriched with a *complement* that describes the physical movement: where from/to, what goods, which vehicle, who drives, which permits/insurance. SAT uses it to prove legal possession and route of goods and fight contraband [R]. Authorities verify it by **QR/folio on SAT's verifier** during inspections [R].

## 2. Who issues what [R]
| Situation | Document |
|---|---|
| Carrier sells transport service | **CFDI de Ingreso** + Carta Porte (the invoice itself carries it) |
| Goods owner moves own goods / intermediary moves for customer | **CFDI de Traslado** (value 0) + Carta Porte |
| Subcontracting (carrier A hires carrier B) | Both layers matter: each party's invoicing duty and the document accompanying goods must be consistent. *Get this explained by a Mexican tax accountant before designing flows.* [K] |
| Not required cases | Some exceptions exist (e.g., certain short/local or specific goods situations) — **verify in SAT FAQs** [K] |
| Even if complement not required | A revenue CFDI is still required for paid transport services [R] |

## 3. Data groups (autotransporte) [K — field names from memory; confirm vs XSD]
The SAT's filling guide counts 180+ fields in the whole standard but identifies a subset (34 mandatory in autotransporte per SAT communication) [R].
1. **Header/Base CFDI:** issuer (RFC, regime), receiver, use, payment fields, `Version` of complement, `IdCCP` (36 chars) [R].
2. **Ubicaciones:** origin, intermediate stops, destination — each with ID, RFC, address (postal code from SAT catalog), date/time of departure/arrival, distance traveled.
3. **Mercancías:** total gross weight, weight unit, number of items; per item: **SAT product code for transport (`c_ClaveProdServCP`)**, description, quantity, unit key, weight in kg, hazardous material flag/key, packaging, value.
4. **Autotransporte:** SICT permit type & number, vehicle config key (e.g., T3S2), plates, model year, **civil-liability insurer and policy number**, trailers.
5. **FiguraTransporte:** driver (RFC, license number), owner/lessee/notified party.
6. **Customs-related (international):** since 3.1, a section for **up to 10 customs regimes** replaced the old single field; tariff fraction removed from minimum data; the IdCCP's prefix must not be transmitted in DODA [R].
7. **Hazmat:** catalog reference updated to NOM-002-SCT-SEMAR-ARTF/2023 [R].

## 4. Lifecycle a TMS must handle
`Draft → Validate (against XSD + SAT catalogs + business rules) → Stamp via PAC → Deliver (XML+PDF+QR) → Use during trip → Update/replace if route/vehicle/driver changes → Cancel (with reason/substitution rules) → Archive (retention period)`
Hard cases: **vehicle breakdown mid-route** (change truck), **driver swap**, **extra delivery stop**, **customer refuses delivery**, **partial delivery**, **late stamping**, **PAC outage**, **wrong weight**, **multi-leg with transfer**, **return trip**.

## 5. Design rules for the TMS
1. **Catalogs are data, not code.** Load SAT catalogs (units, product codes, postal codes, vehicle configs, permit types, hazmat, packaging) from official files; version them; never invent values.
2. **Validate early, validate often**: when a user selects a truck, check permit/insurance/year; when picking goods, require valid SAT product code; when picking route, require postal codes.
3. **Master data first:** vehicle, driver, location and customer records must hold fiscal-grade fields so a Carta Porte is a *click*, not a data-entry session.
4. **Idempotent PAC calls** and a retry/queue strategy; store raw PAC responses; handle duplicates safely.
5. **Immutable audit trail** of every Carta Porte version, who changed what, and why.
6. **Mobile-friendly retrieval:** driver must show PDF/QR at inspection, offline if possible.
7. **Alerts:** expired permit/insurance/license, missing fields, mismatched weights, unrealistic distances/times (cross-check with fatigue rules).
8. **Cancellation/substitution workflow** with approvals; link replacements to originals.
9. **Link to billing:** CFDI de Ingreso is the invoice; ensure credit/payment complements (PPD → REP) flow properly.
10. **Don't give legal advice in UI text;** surface SAT rule references and let compliance owners configure policy.

## 6. Questions to take to an accountant/compliance expert
- When do we need Ingreso vs Traslado with Carta Porte in subcontracting chains?
- How are multi-stop, consolidated (LTL) and returned goods handled?
- What are current cancellation limits and substitution rules?
- What retention period and storage format do auditors expect?
- Which fines apply and how are they assessed (per document? per trip?) — current figures.
- Interaction with customs documents for import/export.
