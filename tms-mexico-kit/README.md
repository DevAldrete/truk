# TMS Mexico Knowledge Kit

Built 6 Oct 2026 for someone **new to logistics** who is building a **Transport Management System (TMS) for Mexico**.

## What is inside
```
README.md                      ← you are here
docs/                          ← human-readable knowledge (read in this order)
  00-glossary.md                 EN↔ES terms, confidence tags
  01-logistics-101.md            beginner primer: actors, lifecycle, costs, KPIs, systems
  02-mexico-context.md           researched Mexico facts: market, regulators, NOM-012, NOM-087, customs 2026, theft, T-MEC
  03-carta-porte-primer.md       Carta Porte 3.1 for TMS builders
  04-tms-domain-model.md         entities, state machines, modules, MVP slices, integrations
  05-use-cases.md                20 use cases with Mexican edge cases
  06-problems-and-risks.md       37-item risk register + mitigations
  07-essential-questions.md      discovery checklist + "what if" scenarios
  08-assignments.md              6-phase learning plan + 12 research briefs + go/no-go checklist
skills/                        ← instructions so an AI can work in this domain
  mx-logistics-domain/           domain expert / beginner tutor
  tms-requirements-analyst/      use cases, user stories, rules, discovery
  carta-porte-compliance/        fiscal document engine design & review
  tms-architect/                 architecture, data model, integrations
  logistics-research-assistant/  research & verification protocol
```

## Suggested path
1. Read docs 01 → 02 → 03 (about 90 minutes). 2. Start `08-assignments.md` Phase 1. 3. Install the five skills so the AI can tutor, research, spec and review with the same shared knowledge. 4. Do the offline assignments (shadow a dispatcher, interview an accountant, 10 carrier interviews) — no document replaces them.

## Confidence tags
**[R]** researched this session from a cited source; **[K]** general knowledge, unverified; **[C]** sources conflict. Treat anything compliance-related as *a lead to verify in DOF/SAT*, never as final. Trade-press sites sometimes show re-publication dates on older articles.

## Known gaps (verify first)
- Current T-MEC review outcome (scheduled July 2026) and cross-border trucking rules.
- Exact Carta Porte field list, catalog names, fine amounts and cancellation rules — take from SAT's standard/XSD and a Mexican tax accountant.
- Exact numeric limits in NOM-012 and the precise wording of NOM-087 — take from the DOF text.
- Pricing/competitor landscape (not researched yet — assignment A3.1).
- Last-mile, cold-chain, hazmat specifics (research briefs 10–11).

## Keeping it alive
Update monthly using Assignment A6.1. When a [K] gets verified, change it to [R] with source + date. Copy-edits to docs must also be copied into each skill's `references/` folder (they are duplicated so each skill is self-contained).
