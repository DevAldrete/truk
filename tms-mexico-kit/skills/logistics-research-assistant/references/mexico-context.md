# Mexico Context — What Makes a Mexican TMS Different

Research date: **6 Oct 2026**. Tags: **[R]** researched this session, **[K]** general knowledge — verify. Trade-press pages often show re-publication dates, so treat statistics as "approximately current" and re-check before quoting in a business case.

## 1. Market snapshot
- Road freight is the backbone: road moved **~58% of cargo (≈572 Mt in 2024)** per ANTP-cited figures; other sources cite **565–577 Mt/year** and ~3.8% of GDP [R]. Figures differ by source and year — always cite source + year.
- Fleet: **>1.5 million federal freight units** [R].
- **Fragmentation:** ~79.9% of carrier permit-holders are independent or small fleets, but they hold only ~22% of the fleet (large carriers own most trucks) [R]. Other sources describe ~140,000 transport companies, mostly *hombre-camión* [R]. → Your user base is two worlds: **large fleets** (want integrations, control, analytics) and **owner-operators** (want WhatsApp, cheap, simple, get loads, get paid).
- Digitization gap: a 2025 survey found ~66% of large companies have ERP/TMS, while ~75% of small companies remain at basic digital levels [R]. Load boards / marketplaces (e.g., Flete, Freight99-type platforms) are entering the market [R]; interoperability is described as the next stage [R].
- Pain from cost side: diesel up >10% in two years, rising security and maintenance costs, labor reform debates squeezing small carriers [R].
- Driver shortage reported at **>90,000 operators** [R].
- Cross-border: >80% of Mexican exports to the US move by road; Laredo handles ~37% of US–Mexico land trade [R].

## 2. The regulators and what each one controls
| Authority | Controls | TMS impact |
|---|---|---|
| **SAT** | CFDI, Carta Porte, tax | Must stamp CFDI via PAC; validate catalogs; keep XML/PDF |
| **SICT** | Carrier permits, federal highways rules, NOMs (weights, fatigue, vehicle) | Permit numbers, vehicle config, weight limits, driver hours |
| **ANAM / SAT-Aduanas** | Customs | Pedimento/DODA references, traceability, electronic file |
| **Guardia Nacional / state police** | Roadside inspection | QR verification of Carta Porte; weight checks |
| **Insurers (AMIS members)** | Coverage conditions | Telematics, route and security requirements |
| **STPS / IMSS** | Labor, social security | Driver contracts, pay, working time |
| **State/municipal authorities** | Urban restrictions, permits | City delivery windows, truck bans in zones [K] |

## 3. Core regulations a TMS must know (with TMS implications)

### 3.1 Carta Porte 3.1 [R]
- Version 3.1 mandatory since **17 July 2024**; no coexistence period with 3.0 [R]. More detail → `03-carta-porte-primer.md`.
- Two document types: **CFDI de Ingreso** (carrier invoices a service) and **CFDI de Traslado** (owner or intermediary moves goods; value 0) [R].
- Even when the complement is not required, a revenue CFDI is still needed for paid transport services [R].
- 2024 FAQ update: for the customs "Mecanismo de Selección Automatizado", a printed Carta Porte is not required [R].
- Fines exist for not issuing or not presenting it — amounts are set in tax law and change; **do not hardcode, look up** [R/K].
- **Implication:** the Carta Porte is not a report; it is a legal prerequisite to dispatch. The TMS must generate, validate, stamp, store, cancel/replace, and deliver it (PDF + XML + SAT verification QR).

### 3.2 Weights and dimensions — NOM-012-SCT-2-2017 [R]
- Published DOF 26 Dec 2017, effective 26 Feb 2018; replaced the 2014 version [R].
- Sets max weight per axle and per combination, max dimensions, by **vehicle configuration** and **road type**; includes rules on holiday-period circulation and safety devices for double-trailer combinations [R].
- Configuration codes like **T3-S2-R4** describe tractor axles / semi-trailer / trailer [R].
- Enforcement increasingly uses electronic weigh and dimension systems with electronic fines; for consolidated cargo, reports indicate the carrier absorbs responsibility for overweight [R, from 2015-era reporting — verify in current text].
- **Implication:** store a *lookup table* of configurations → limits sourced from the NOM text. Validate weight at planning time (gross vehicle weight = tare + payload). **Never hardcode limits from memory.**

### 3.3 Driver fatigue — NOM-087-SCT-2-2017 [R]
- A **30-minute pause after 5 hours of continuous driving** (or distributed within 5.5 hours depending on route) [R].
- For cargo trips with up to **14 hours of driving**, a rest of **at least 8 continuous hours** follows; daily rest cannot be accumulated [R].
- A **trip log (bitácora)** is mandatory, physical or electronic, and must be shown on demand [R].
- Press explanations vary slightly (some describe two 7-hour blocks). **Read the DOF text before encoding rules.**
- A Feb 2026 legislative initiative (Movimiento Ciudadano) proposes Federal Labor Law changes on drivers' rights, referencing these limits — a **watch item**, not law [R].
- **Implication:** the planner must compute feasible ETAs *with legally required breaks*; telematics should feed an hours-of-service clock; dispatch rules must stop impossible delivery times.

### 3.4 Customs — Ley Aduanera reform 2026 [R]
- Reform in force **1 Jan 2026**; described as the largest overhaul in ~30 years; Reglamento published in DOF **23 Feb 2026** [R].
- Emphasis: **traceability and documentary evidence** — electronic file (*expediente electrónico*) per operation, MVE, tougher agent/agency liability, higher fines, data exchange, video-surveillance/technology modernization [R].
- The reform requires logistic partners (carriers, warehouses) to meet traceability and Carta Porte deadlines; carriers consolidating land cargo have notification duties [R].
- **Implication:** for cross-border or import/export flows, the TMS must keep references (pedimento, DODA, container, seals, timestamps, photos) linked to the shipment and exportable as evidence.

### 3.5 Cross-border / T-MEC [R]
- T-MEC joint review was scheduled for **July 2026**; scenarios discussed: 16-year extension, partial continuity with annual reviews, or sunset in 2036 [R]. **Outcome is unknown to this research — verify current status.**
- Mexican carriers' long-haul presence in the US is minimal (a few dozen firms, ~1,500 units cited) and cabotage within the US is reserved for US carriers [R]. Practical model: **border transfers/drayage** and tractor swaps at the border [K].
- Visa policy for foreign truck drivers is volatile [R].
- **Implication:** model *handoff points* (border yards, transfer carriers), international CCP, and multi-party tracking.

### 3.6 Other items to check before building [K]
- Federal carrier permit types and insurance requirements (Ley de Caminos, Puentes y Autotransporte Federal; Reglamento de Autotransporte Federal y Servicios Auxiliares).
- Federal driver license categories and medical exam validity.
- CFDI 4.0 rules, Complemento de Pago, cancellation rules, SAT **Art. 69-B** supplier risk lists and the *opinión de cumplimiento* — critical for subcontracting carriers.
- Hazmat rules (NOM-002 family; Carta Porte 3.1 updated references to NOM-002-SCT-SEMAR-ARTF/2023) [R for the reference update].
- Cold-chain norms for food/pharma, and pesticide/fuel transport rules.

## 4. Cargo theft — the dominant operational risk
- ANTP recorded **12,462 theft incidents in 2024**; food/grocery was the most-hit category (~21.8% of stolen goods) [R].
- Jan 2026: **777 reports** nationally, down 16.7% from Jan 2025 (933) [R]. Mid-2026 government statements cite a ~19.4% national drop in H1 2026 vs H1 2025 and big reductions on the México–Querétaro corridor [R]. Industry says perception and violent incidents remain high [R].
- Concentration: ~84–94% of incidents occur in **ten states** — Estado de México, Puebla, Guanajuato, San Luis Potosí, Querétaro, Michoacán, Jalisco, Tlaxcala, Veracruz, Hidalgo (list varies by quarter) [R].
- **The map is shifting toward the Bajío** in Q2 2026 (Overhaul report), and **parked-unit theft rose to ~39%** of incidents while ~62% occur on moving trucks in other analyses [R]. Risk is route-, time- and behavior-dependent.
- Incident day pattern (2024): mid-week highest (Wed ~19%, Thu ~18%), weekends lowest [R].
- Known high-risk corridors: México–Puebla–Veracruz, México–Querétaro, Bajío industrial axis, Monterrey routes [R].
- **Implication:** the TMS needs *security as a first-class module*: route risk scoring, approved stopping places, geofenced alerts, route deviation, panic/escalation workflows, evidence retention, and insurer integration. Don't make security an afterthought plug-in.

## 5. Infrastructure and operations realities [K]
- **Tolls** are a major cost and vary by axle count; electronic toll tags have multiple issuers; toll price tables change — fetch from sources/APIs and keep history.
- Connectivity gaps on rural stretches → **offline-capable driver app** and delayed GPS pings.
- Border and port congestion drive dwell time; detention (*estadías*) is a negotiation point.
- Customer docks often demand appointment windows, PPE, seals, and specific paperwork (remisión, orden de compra, factura).
- WhatsApp is the *de facto* operations tool for small carriers and drivers; a TMS that ignores it will be bypassed.
- Payment culture: payment terms of 30–90 days are common; **factoring** and advances (*anticipos*) exist; cash handling for drivers is still significant [K].

## 6. Events and seasonality [K/R]
- Peaks: year-end retail, Hot Sale (May), back-to-school, harvest cycles, holiday traffic (Semana Santa, summer) with restrictions on double trailers in some periods [R for NOM-012 holiday circulation section].
- 2026 FIFA World Cup (Jun–Jul 2026; Mexico City, Guadalajara, Monterrey) raised urban distribution complexity [R]; useful as a case study of event-driven urban restrictions.

## 7. Source list (starting points, verify originals)
- SAT Carta Porte portal and FAQ editions (primary); summaries by Siigo, Alegra, El Contribuyente, BHR [R].
- DOF text for NOM-012-SCT-2-2017 and NOM-087-SCT-2-2017 (primary; PLATIICA/Economía hosts copies) [R].
- Ley Aduanera 2026 summaries (Persona, Gomsa, BHR, Stratego, Control 2000) and The Logistics World coverage [R].
- Theft: ANTP, AMIS, ANERPV, Overhaul Q2-2026 report, Marsh Mexico, Total Protect [R].
- Market: Cluster Industrial (Aug 2026), Pulsómetro Logístico, Kearney report summaries [R].
- T-MEC transport: El Financiero (Oct 2025), The Logistics World [R].
