# Possible Problems & Risks (Register)

Severity: 🔴 can kill the product/business · 🟠 serious · 🟡 manageable. Each row: **what can go wrong → why → mitigation**.

## A. Regulatory & fiscal
| ID | Risk | Sev | Why / Mitigation |
|---|---|---|---|
| R-01 | Wrong or rejected Carta Porte → fines, retained cargo | 🔴 | 180+ field standard, catalog-driven; **validate pre-stamp**, test with sandbox PAC, expert review, regression tests per XSD version |
| R-02 | SAT schema/catalog version changes (3.0→3.1 had no overlap period) [R] | 🔴 | Version-aware schema layer; automated catalog sync; feature flags; monitoring SAT announcements |
| R-03 | Misunderstanding of Ingreso vs Traslado in subcontracting | 🟠 | Accountant-reviewed decision matrix; configurable per tenant |
| R-04 | Customs traceability duties (2026 reform) not covered | 🟠 | Keep pedimento/DODA/seal/photo refs linked to shipments; exportable dossier |
| R-05 | Driver-hours rules encoded wrong | 🟠 | Read DOF text; make rules configurable with citation; legal review |
| R-06 | Overweight fines from wrong configuration limits | 🟠 | Lookup tables from NOM-012; tare weights; scale-ticket capture |
| R-07 | Subcontracting a carrier flagged by SAT (69-B) or without valid permit/insurance | 🟠 | Onboarding checks; periodic re-checks; block assignment |
| R-08 | Data-protection compliance (drivers' biometrics/location) | 🟠 | Privacy notice, consent, minimization, retention policy, role-based access |
| R-09 | Labor reform for drivers (initiative Feb 2026 [R]) changes pay/hour models | 🟡 | Configurable pay schemes; track legislation |
| R-10 | T-MEC review outcome changes cross-border rules [R] | 🟡 | Keep cross-border module modular; monitor |

## B. Operational & security
| ID | Risk | Sev | Why / Mitigation |
|---|---|---|---|
| R-11 | Cargo theft (≈ one every 50 min nationally [R]); shifting corridors | 🔴 | Risk scoring by route/time/stop; approved-stop catalog; geofences; escalation SOP; insurer integration; evidence retention |
| R-12 | GPS signal loss / jamming | 🟠 | Multi-source (device + driver app); anomaly rules; protocol for gaps |
| R-13 | Driver shortage & turnover [R] | 🟠 | Driver UX, fast settlements, transparent pay, fatigue management |
| R-14 | Insider fraud (fuel, cargo, falsified POD, kickbacks) | 🟠 | Audit trail, photo/seal evidence, fuel analytics, approval limits, segregation of duties |
| R-15 | Customer dock delays → unbilled detention | 🟡 | Geofence timestamps → automatic *estadías* evidence |
| R-16 | Rural connectivity gaps | 🟡 | Offline-first app; store-and-forward |

## C. Market & product
| ID | Risk | Sev | Why / Mitigation |
|---|---|---|---|
| R-17 | Building for "everyone" (carrier + shipper + broker + courier) | 🔴 | Pick one wedge; separate roadmaps |
| R-18 | Low willingness to pay among small carriers (~80% of permit holders are small/independent [R]) | 🟠 | Freemium/transaction pricing; sell to large fleets first; embed finance (factoring) later |
| R-19 | Incumbent TMS/ERP and telematics vendors; local accounting packages | 🟠 | Integrate rather than replace; differentiate on Mexico-native compliance + UX |
| R-20 | WhatsApp-first culture bypasses the system | 🟠 | Bridge WhatsApp ↔ TMS; don't force app-only |
| R-21 | Over-engineering optimization before basics | 🟠 | Ship "legal, visible, paid trip" first |
| R-22 | Customers want ERP/EDI integration for each account | 🟡 | Integration kit, standard API, adapters |
| R-23 | Pricing complexity (tolls, diesel, extras, FX) | 🟡 | Rate engine with versioned rules; fuel surcharge tables |

## D. Technical
| ID | Risk | Sev | Why / Mitigation |
|---|---|---|---|
| R-24 | PAC vendor outage or lock-in | 🟠 | Adapter pattern, 2+ PACs, queue/retry |
| R-25 | Heterogeneous GPS vendors, varied data quality | 🟠 | Normalizing ingestion layer, device health scoring |
| R-26 | Address/geocoding quality in Mexico (colonias, rural) | 🟠 | Postal-code catalog, geofences, manual pin, driver confirmation |
| R-27 | Time zone/DST regional differences | 🟡 | Store UTC + zone; test cases |
| R-28 | Data migration from Excel/legacy | 🟡 | Import tooling with validation reports |
| R-29 | Scalability of tracking data | 🟡 | Time-series store, partitioning, retention tiers |
| R-30 | Security breaches (fiscal/driver/location data are sensitive) | 🔴 | Encryption, least privilege, audit logs, pen tests, incident plan |

## E. Financial/commercial
| ID | Risk | Sev | Why / Mitigation |
|---|---|---|---|
| R-31 | Slow customer payments (30–90 days) hurts carriers' cash [K] | 🟠 | AR dashboards, reminders, factoring partners |
| R-32 | Driver cash advances & settlement disputes | 🟠 | Digital advances, receipt capture, transparent statement |
| R-33 | Unprofitable lanes unnoticed | 🟠 | Margin per lane; planned vs actual costing |
| R-34 | Insurance claim rejections from missing evidence | 🟠 | Evidence checklist per trip; automatic dossier |

## F. Team/project
| ID | Risk | Sev | Why / Mitigation |
|---|---|---|---|
| R-35 | Founder/team new to logistics (current situation) | 🟠 | Domain assignments, expert advisors, shadowing dispatchers, early customer discovery |
| R-36 | Relying on AI-generated legal/fiscal facts without verification | 🔴 | **Tag every fact [R]/[K]; verify against DOF/SAT; expert sign-off before shipping compliance logic** |
| R-37 | Scope creep from customers | 🟡 | Roadmap gates, discovery cadence |

## Top 5 to address first
1. R-17 (wedge choice) 2. R-01/R-02 (Carta Porte engine & versioning) 3. R-11 (security as first-class) 4. R-36 (verification discipline) 5. R-20 (WhatsApp bridge / UX for small carriers)
