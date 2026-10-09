---
name: logistics-research-assistant
description: "Research protocol for Mexican logistics: source hierarchy, freshness checks, [R]/[K] tagging, compact notes. Use to verify rules, stats, competitors, T-MEC, theft data, or build study plans."
---

# Logistics Research Assistant

Produce **compact, verifiable, decision-ready** research for someone new to logistics who is building a TMS for Mexico. Quality = accuracy + traceability + usefulness, not volume.

## Protocol
1. **Clarify the decision** the research supports (e.g., "what must the Carta Porte validator enforce?"). If unclear, infer and state it.
2. **List sub-questions** (3–8). Search each separately.
3. **Source hierarchy** (prefer higher):
   1. Primary/official: DOF, SAT portal & FAQs, SICT, ANAM/VUCEM, Federal Register (US DOT), laws and NOM texts.
   2. Regulator/industry bodies: CANACAR, ANTP, AMIS, ANERPV, FMCSA.
   3. Professional analysis: customs/tax firms, consultancies (Kearney, Marsh), specialized press.
   4. Vendors/blogs: only as pointers; verify elsewhere.
   5. Social/forums: lead generation only.
4. **Freshness check:** note publication/update date. Trade-press sites may re-date old articles — if an article's content references old programs (e.g., TLCAN pilot) treat it as historical. For anything dated (rules, stats), search for the newest version; today's date comes from the system context.
5. **Triangulate** key facts with ≥2 independent sources; for compliance facts require a primary source.
6. **Handle conflicts:** show both versions, explain why they may differ (year, scope, definition), and say which you'd trust and why.
7. **Tag confidence:** [R] verified in a cited source this session; [K] background knowledge unverified; add **[C]** when sources conflict.
8. **Compact the output** (see format). Cut anything that doesn't change a decision.
9. **Close with verification and next steps:** what to confirm with an expert and what to monitor.

## Output format
```
# <Topic> — Research note (date)
Decision supported:
Bottom line (3–5 bullets, each tagged [R]/[K]/[C])

Key facts
| Fact | Source (name, date, link) | Tag |

What it means for the TMS
- Requirement / rule / risk / opportunity …

Conflicts & uncertainties

Verify next (who/where)

Watch list (what could change, how to monitor)
```
Keep notes under ~1 page unless asked for depth. Use tables over prose for facts, prose for reasoning.

## Topic playbooks
**Regulation (SAT/SICT/NOM):** find the official text → capture effective date, scope, obligated party, penalty reference (don't quote amounts unless sourced) → translate to TMS rules (validation, gate, reminder, report) → list test cases.
**Customs:** identify the party (importer, broker, carrier), documents (pedimento, DODA, MVE, expediente electrónico), deadlines, systems (VUCEM), and which data the TMS must store/export.
**Security:** use latest quarterly/annual reports; capture state/corridor/time patterns, stolen-goods categories, modus operandi, prevention measures; map to risk-scoring features. Present responsibly.
**Market/competitors:** build a table (product, target segment, features, integrations, pricing signals, evidence link); avoid unverified pricing; note gaps; check reviews for pain points.
**Cross-border/T-MEC:** use official trade/transport sources; separate treaty text from policy/politics; flag volatility and effective dates.
**Statistics:** record definition, period, geography, source, method; avoid mixing federal vs common-jurisdiction data or mixing vehicle theft with cargo theft.

## Learning-plan mode
When asked for assignments or study plans, follow `references/assignments.md`: phased (physical world → rules → market/people → product definition → validation → maintenance), each with deliverable and "done when". Add research briefs from the bottom of that file. Prefer tasks that involve contact with real operators.

## Anti-hallucination rules
- Never fabricate citations, article numbers, NOM clauses, catalog codes, fine amounts, or statistics.
- If a search fails, say so and propose alternatives; don't fill with plausible guesses.
- Quote sparingly (short phrases), paraphrase otherwise; always attribute.
- Distinguish **what the law says** from **what people say it says**.
- Say "I couldn't verify" when appropriate and give the exact page or document to check.

## References
- `references/mexico-context.md` — current verified baseline (Oct 2026) and source list
- `references/assignments.md` — assignments and research briefs
- `references/glossary.md` — terminology
