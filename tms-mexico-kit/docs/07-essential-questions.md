# Essential Questions & Situations to Consider

Use as a discovery checklist. **Bold** = blocks design if unanswered. "Who answers" shows where to find the answer.

## 1. Business model and users
1. **Who is the paying customer in v1: carrier, shipper, broker, courier?** — founder + 10 customer interviews
2. **What size: owner-operators, 10–200 trucks, enterprise?** — interviews, CANACAR data
3. What is the revenue model: SaaS per truck, per trip, per stamped document, marketplace fee, financial services? — pricing interviews
4. Who decides to buy (owner, operations director, finance, IT)? Who is blocked by this system (dispatcher fear of change)?
5. What do they use today (Excel, WhatsApp, local ERP, GPS portal)? What would they never give up?
6. What are the top 3 pains that cost real money today? (quantify in pesos/month)
7. What would make them switch? Switching costs: data, training, integrations, contracts?

## 2. Operations
8. **Which freight types** (FTL, LTL, last mile, dedicated, intermodal, cross-border, cold chain, hazmat)?
9. Typical trip: distance, stops, duration, weight, goods, special equipment?
10. How do dispatchers choose trucks/drivers today? What constraints matter (customer preferences, driver seniority, geography)?
11. Fleet ownership: own, leased, subcontracted? Percentage of each?
12. How are drivers paid (per km/trip/percent/day)? Advances? Settlement cadence?
13. How is delivery proven (paper remisión, photo, e-signature, customer portal)? How are POD originals returned?
14. How are delays, detention (*estadías*) and extra services (*maniobras*) billed and disputed?
15. What happens when a truck breaks down mid-route? When a customer rejects goods?
16. How is empty return handled? Is backhaul sought systematically?

## 3. Compliance & legal
17. **Who owns compliance (CFDI/Carta Porte) today: accountant, dispatcher, external agency?**
18. **Which PAC(s) do customers use? Are they willing to switch? What are stamping costs?**
19. How are Ingreso vs Traslado documents decided in their real flows (own goods, subcontracting, broker)?
20. Which permits and insurance policies are in place; who tracks expiries?
21. Hours-of-service/bitácora: paper, app, nothing?
22. Overweight enforcement experiences; scale usage?
23. Cross-border: who handles pedimentos? What documents does the carrier need to hold?
24. Which regulations or fines have hit them in the last 3 years?
25. What data retention and audit obligations apply?

## 4. Security & risk
26. **Which routes/hours/stops have caused incidents? Who monitors, 24/7?**
27. What do insurers require (GPS, no-stop rules, escort, locks, cargo value limits)?
28. Existing monitoring center: in-house or outsourced? Which GPS vendors?
29. What is the protocol after an incident, and how fast can evidence be assembled?
30. How do they vet drivers and subcontracted carriers?

## 5. Technology & data
31. **Which GPS/telematics and ERP/accounting systems must integrate (top 5)?**
32. Do drivers have smartphones and data plans? Android vs iOS? Literacy/UX constraints?
33. Is WhatsApp acceptable as a channel? Policies about personal phones?
34. What master data exists and what quality (RFCs, addresses, vehicle docs)?
35. Hosting/data location requirements from customers? Uptime expectations?
36. Authentication/roles: how many users per company, outsourced staff, shared accounts?
37. What reporting formats do customers demand (Excel, PDF, API)?

## 6. Money
38. What are the real cost components per trip and who captures them?
39. Payment terms, collection problems, DSO? Factoring usage?
40. How is pricing set (per km, per trip, per ton, per pallet; fuel surcharge)? Who approves exceptions?
41. How are tolls reconciled (tags, cash, cards)? Fuel cards?
42. What does a "profitable lane" mean to them? Do they know their margin?

## 7. Product & delivery
43. **What is the smallest slice that makes a dispatcher's day better within 2 weeks of use?**
44. What are the non-negotiable integrations at launch?
45. How will data be migrated, and by whom?
46. What training/support model is needed (WhatsApp support? onsite)?
47. What success metrics will we publish to customers (Carta Porte error rate, time per trip, DSO)?
48. What does failure look like? (e.g., a stamped document wrong → who pays fines?)
49. Liability and SLAs: what do we promise, what's our insurance?
50. Which parts are we building vs buying (PAC, maps, GPS, messaging, payments)?

## 8. Situations to walk through ("What if…")
- The PAC is down at 6 AM while 30 trucks are waiting.
- Driver is detained at an inspection and QR won't load — no signal.
- Customer changes delivery address mid-trip.
- Truck breaks down; replacement has different configuration/permit.
- Two customers claim the same pallet in an LTL hub.
- A subcontracted carrier's driver stops at an unapproved place in a red zone.
- Customer pays 70% and disputes the rest; REP complexity.
- A NOM or SAT catalog changes next month.
- GPS device is removed by a driver.
- A fuel-theft pattern appears across several trips.
- A key dispatcher resigns; how fast can a replacement operate?
- Customer demands integration within 2 weeks or cancels.
