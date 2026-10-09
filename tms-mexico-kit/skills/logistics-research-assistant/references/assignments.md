# Assignments & Research Work — From Beginner to TMS Builder

**How to use:** do them in order. Each assignment has a *deliverable* you save in a `workbook/` folder, and a *done when* check. Use the AI (with the skills in this kit) as a tutor and reviewer, but **do the thinking and the real-world contact yourself**. Estimated total: 6 weeks at ~6–8 h/week. Difficulty: ★ easy · ★★ medium · ★★★ hard.

Verification rule: any fact you save must carry a tag **[R]** (checked in an official/primary source, with link and date) or **[K]** (unverified). Aim to convert [K] into [R] for everything that touches compliance.

---
## PHASE 1 — Understand the physical world (Week 1)

**A1.1 Trace a product from factory to shelf ★**
Pick a product (e.g., a beverage brand). Draw the chain: factory → distribution center → regional hub → store. List every vehicle, document and handoff.
Deliverable: one-page diagram + list of documents. Done when: you can explain who pays whom at each step.

**A1.2 Cost of one truck trip ★★**
Build a spreadsheet for a Guadalajara → Mexico City trip, 24 t, 1 tractor-trailer. Include fuel, tolls, driver, depreciation, insurance, maintenance, security, overhead, empty return probability. Use real current prices (search diesel price, tolls, typical driver pay).
Deliverable: sheet with assumptions column citing sources. Done when: you can say the break-even price per km and what happens if the truck returns empty.

**A1.3 Vocabulary drill ★**
Learn the glossary (`00-glossary.md`). Write 15 flashcards for terms you confuse (e.g., Ingreso vs Traslado, Order vs Shipment vs Trip). Ask the AI to quiz you until you score 90%.

**A1.4 Shadow a real operation ★★ (offline)**
Visit or interview a dispatcher or a warehouse supervisor (even a small business or a family contact). Observe for 2 hours. Record: tools used, interruptions, WhatsApp messages, what goes wrong.
Deliverable: 1-page field notes with 10 direct observations. Done when: you identified 3 pains they described in their own words.

---
## PHASE 2 — Mexican rules (Weeks 2–3)

**A2.1 Carta Porte by hand ★★★**
Read SAT's Carta Porte standard and "preguntas frecuentes" (latest edition). Then, using SAT's free tools or a PAC sandbox, create a *test* CFDI de Traslado with Carta Porte for a fictional trip (2 stops). Don't stamp a real one unless advised by an accountant.
Research questions: Which fields were hardest? Which catalogs did you need? Which error messages appeared?
Deliverable: screenshots/notes + list of every field you had to fill, grouped by who knows the data (dispatcher, customer, driver, finance). Done when: you can explain Ingreso vs Traslado with 3 examples.

**A2.2 NOM-012 weights and configurations ★★**
Download the DOF text. Extract: configurations (e.g., T3-S2, T3-S2-R4), road types, max weights, dimensions. Build a lookup table prototype.
Deliverable: CSV + 5 validation rules a TMS would apply. Done when: you can tell if a 3-axle tractor + 2-axle semitrailer with 28 t payload is legal on an A-type road (use the official table, not memory).

**A2.3 Driver hours — NOM-087 ★★**
Read the DOF text. Write the rules as a decision table. Then compute a feasible schedule for a 1,100 km trip with one driver and with two. Note where press descriptions disagree with the NOM.
Deliverable: decision table + schedule + "discrepancies found" list.

**A2.4 Customs 2026 reform summary ★★★**
Read two or more summaries plus the DOF decree if possible. Answer: what changes for *carriers* (not just importers)? What data would a TMS need to hold to support an *expediente electrónico*?
Deliverable: 2-page brief with sources + a list of data fields.

**A2.5 Carrier legal setup ★★**
Research (SICT website, Ley de Caminos y Autotransporte Federal, regulation): what does a carrier need to operate federally — permit types, insurance, vehicle registration, driver licenses, inspections?
Deliverable: checklist with link to each requirement + expiry/renewal cycle. Done when: you can list all the expiring documents a TMS must track.

**A2.6 Ask an expert ★★ (offline)**
Interview a Mexican accountant who handles CFDI for a carrier. Use the questions at the end of `03-carta-porte-primer.md`.
Deliverable: interview notes; update the [K]→[R] tags.

---
## PHASE 3 — The market and the people (Week 3–4)

**A3.1 Competitive landscape ★★**
List 10 products: TMS (global & local), telematics suites, load boards, last-mile platforms, ERPs with transport modules, Carta Porte tools. For each: target user, pricing signals, integrations, strengths/weaknesses, reviews.
Deliverable: comparison table + "where is the gap?" paragraph. Use the research skill's source hierarchy.

**A3.2 User personas and journeys ★★**
Create 5 personas (owner of 50-truck carrier, dispatcher, driver, billing clerk, monitoring operator, plus optionally owner-operator). For each: goals, tools, pains, a typical day, quotes from interviews.
Deliverable: persona cards + 2 journey maps (order→delivery, delivery→cash).

**A3.3 Customer discovery interviews ★★★ (offline)**
Run 10 interviews (carriers of different sizes). Use a script: current process, last time something went wrong, costs, tools, buying process. Don't pitch.
Deliverable: transcript notes + synthesized table of pains by frequency and cost. Done when: you have a ranked list of pains with peso estimates and a hypothesis on the wedge.

**A3.4 Security landscape ★★**
Using the theft data cited in `02-mexico-context.md` (ANTP, AMIS, Overhaul, Marsh), find the latest report and map the risk by corridor. Then interview someone from a monitoring center about their protocols.
Deliverable: risk map + draft "security rules" a TMS should enforce.

---
## PHASE 4 — Define the product (Week 4–5)

**A4.1 Choose the wedge ★★★**
Using evidence from A3.3, decide the primary user, the first 3 use cases, and what you will NOT build in v1. Compare options A–E in `04-tms-domain-model.md`.
Deliverable: 1-page product thesis + risks + success metrics.

**A4.2 Write the top-10 use cases in detail ★★**
Expand from `05-use-cases.md` with acceptance criteria (Given/When/Then) and Mexican edge cases. Include error handling and who sees what.
Deliverable: use-case spec file. Done when: a developer could estimate effort without asking you questions.

**A4.3 Domain model and state machines ★★★**
Review `04-tms-domain-model.md`, challenge it with 5 real trips from your interviews (one with transfer, one with returned goods, one with breakdown). Modify entities and states.
Deliverable: updated ER diagram + state diagrams + list of open questions.

**A4.4 Build vs buy decisions ★★**
For PAC, maps/routing, GPS, WhatsApp, payments, identity checks, e-signature: list options, costs, risks, lock-in, and a recommendation.
Deliverable: decision table.

**A4.5 Pricing and unit economics ★★**
Estimate CAC, ARPU (per truck per month or per stamped document), support costs, PAC costs, GPS data costs. Model 12 months.
Deliverable: spreadsheet + sensitivity analysis.

---
## PHASE 5 — Validate and prototype (Week 5–6)

**A5.1 Clickable prototype ★★**
Prototype 3 screens: dispatch board, trip detail with compliance checks, driver mobile view. Show to 3 dispatchers.
Deliverable: prototype + feedback log with changes. Done when: dispatcher completes a task in under 3 minutes without help.

**A5.2 Compliance rules catalog ★★★**
Create a table of every validation rule (id, rule, source, severity, who can override, test case). At least 40 rules across Carta Porte, weights, hours, permits/insurance, master data.
Deliverable: rules catalog. Done when: each rule has an authoritative source link ([R]).

**A5.3 Data import test ★★**
Take a real (anonymized) Excel from a carrier. Design the import mapping and list data quality issues found.
Deliverable: mapping spec + issues list.

**A5.4 Failure-mode workshop ★★**
Run "what if" scenarios from `07-essential-questions.md` §8 with a friendly operator. For each: expected behavior and manual fallback.
Deliverable: runbook of fallback procedures.

**A5.5 Pilot plan ★★**
Define a 30-day pilot with one carrier: scope, data to migrate, training, success metrics, rollback, support schedule.
Deliverable: plan + signed expectations.

---
## PHASE 6 — Maintain knowledge (ongoing)

**A6.1 Regulatory watch ★**
Create a monthly checklist: SAT Carta Porte page/announcements, DOF searches (SICT, SAT, ANAM), T-MEC news, theft statistics, labor reform.
Deliverable: watchlist doc + calendar reminders.

**A6.2 Glossary and FAQ growth ★**
Each time you learn a term or rule, add it with [R]/[K] tag.

---
## Research briefs (give these to the AI research assistant)
Use the `logistics-research-assistant` skill. Each brief: ask for sources, dates, contradictions, confidence.

1. **Carta Porte 3.1 current state** — latest SAT FAQ edition, changes in the last 12 months, catalog update cadence, fines schedule, simplification announcements.
2. **Weights/dimensions by configuration** — extract the official tables from NOM-012-SCT-2-2017 and note any amendments or pending revisions.
3. **Hours of service** — exact NOM-087 text, enforcement practice, electronic bitácora options, pending labor reform.
4. **Customs 2026** — what carriers must do, deadlines, fines, systems (VUCEM), RGCE 2026 changes.
5. **T-MEC joint review** — current status, impacts on cross-border trucking, US DOT permits, visa situations.
6. **Cargo theft** — latest quarter data by state/corridor/time, insurer requirements, effective technologies.
7. **Market map** — TMS/telematics/load board vendors in Mexico, pricing, adoption by fleet size.
8. **Toll & fuel economics** — toll price structure, tag providers, diesel price series, typical fuel surcharge practices.
9. **Labor and driver economics** — pay schemes, social-security obligations, shortage numbers, training pipeline.
10. **Last-mile and e-commerce logistics in Mexico** — major players, delivery windows, failed delivery rates, urban restrictions.
11. **Cold-chain & hazmat** — norms, equipment, documents, TMS implications.
12. **PAC landscape** — providers, APIs, pricing, uptime, sandbox availability.

## Self-assessment (go/no-go checklist before writing code)
- [ ] I can explain the shipment life-cycle and the financial flow without notes.
- [ ] I can list the 10 most important compliance rules and their sources.
- [ ] I interviewed ≥10 target users and can quote their top pains.
- [ ] I picked one primary user and a v1 scope with a "not now" list.
- [ ] I have an expert (accountant/compliance) who reviews fiscal logic.
- [ ] I have a list of integrations for v1 and a build/buy decision for each.
- [ ] I have a pilot customer candidate.
