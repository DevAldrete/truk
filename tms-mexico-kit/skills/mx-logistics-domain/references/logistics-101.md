# Logistics 101 — The Compact Primer (for a total beginner)

## 1. The one-sentence model
**Logistics = getting the right thing to the right place at the right time at an acceptable cost, while the paperwork, money and rules keep up with the physical movement.**
Every TMS feature is one of four things: **move** (physical), **inform** (data/visibility), **pay/get paid** (money), or **comply** (legal). If a feature touches none, question it.

## 2. Who is involved (the cast)
```
Shipper ──orders──▶ Carrier (or Broker ▶ Carrier) ──moves──▶ Consignee
   │                    │ driver + truck + permit + insurance
   │                    ▼
   │              Tracking / Security center
   ▼
 ERP / WMS (their systems)           Authorities: SAT (tax), SICT (road), ANAM (customs), police
```
- **Shipper** wants: on-time, undamaged, visible, cheap, invoiced correctly.
- **Carrier** wants: full trucks, short empty miles, fast payment, no fines, no thefts.
- **Driver** wants: clear instructions, safe stops, fair and quick settlement.
- **Dispatcher / planner** wants: one screen showing orders, trucks, drivers and problems.
- **Finance** wants: every trip invoiced, every cost captured, cash collected.
- **Authorities** want: proof — documents match the physical load.

## 3. Types of freight service
| Type | What | Typical complexity |
|---|---|---|
| FTL (carga completa) | One truck, one shipper, point A→B (maybe a few stops) | Medium |
| LTL (consolidada) | Several shippers' goods on one truck via hubs | High: hubs, cross-dock, pallets/volumes |
| Parcel / courier | Small packages, network of hubs | Very high volume |
| Last mile | Final delivery to homes/stores in cities | Many stops, time windows, failed deliveries |
| Dedicated / contract fleet | Trucks assigned to one customer | Route + schedule driven |
| Intermodal / drayage | Truck + rail/port/container | Container, customs, terminal slots |
| Cross-border | Mexico ↔ US | Customs, drivers' limits, transfers at border |
| Special: refrigerated, hazmat, oversize | Extra rules and equipment | Compliance heavy |

## 4. The shipment life-cycle (backbone of any TMS)
1. **Quote / rate** — price a lane (origin→destination, truck type, weight, extras).
2. **Order** — customer confirms: pickup/delivery points, windows, goods, weight/volume.
3. **Plan** — choose truck + driver (own fleet or subcontracted carrier); group stops; estimate route, tolls, fuel, time.
4. **Tender / assign** — offer to a carrier or assign internally.
5. **Dispatch** — driver gets instructions; **fiscal docs generated (Carta Porte in Mexico)**; advances for fuel/tolls.
6. **Execute / track** — GPS events, check-calls, geofences, delays, incidents.
7. **Deliver** — arrival, unloading, signature/photo = **POD**.
8. **Settle** — driver settlement, carrier payables, extra charges (detention, handling).
9. **Invoice & collect** — CFDI, payment complement, collections.
10. **Analyze** — cost per trip, margin per lane, OTIF, utilization, claims.

## 5. Where the money goes (cost anatomy of one truck trip) [K]
Fuel (diesel) · Tolls (casetas) · Driver pay + viáticos · Truck depreciation/lease · Maintenance + tires · Insurance (RC + cargo) · Security (GPS, escort, monitoring) · Permits/admin · Overhead · **Empty return miles** (often the hidden killer). Margin is usually thin; small percentage errors in cost estimation destroy profit.

## 6. KPIs a TMS should be able to produce
| KPI | Why it matters |
|---|---|
| OTIF (On-time in-full) | Customer satisfaction & penalties |
| Cost per km / per trip / per ton | Pricing and profitability |
| Margin per lane / customer | Where to grow or stop |
| Empty-mile % (deadhead) | Efficiency |
| Asset utilization (days/month moving) | Fleet ROI |
| Dwell / detention time at customers | Billable waiting; bottlenecks |
| Claims & incident rate | Insurance cost, customer trust |
| Days sales outstanding (DSO) | Cash flow |
| Driver turnover / hours compliance | Labor + legal risk |
| Document error rate (Carta Porte rejects) | Fine/inspection risk |

## 7. The software landscape (what a TMS is and is not)
| System | Job | Relation to TMS |
|---|---|---|
| ERP | Finance, purchasing, inventory master | Sends orders; receives costs/invoices |
| WMS | Inside the warehouse | Hands off loads at docks |
| OMS / e-commerce | Customer orders | Source of delivery demand |
| **TMS** | **Planning, executing, tracking, paying for transport** | The system you are building |
| FMS / maintenance | Truck health, service, tires | Feeds availability to TMS |
| Telematics / GPS | Location, sensors, ignition, fuel | Feeds events to TMS |
| YMS | Yard/dock appointments | Optional integration |
| Customs/broker systems | Pedimentos | Link by references |
| Accounting / PAC | Stamping CFDIs | Mandatory integration in Mexico |

Planning vs execution: **planning** decides *what should happen* (optimization, assignments); **execution** records *what actually happens* (events, exceptions). Beginners overbuild optimization; most Mexican operations first need clean execution, compliance and money tracking.

## 8. Principles to remember
1. **Physical, information, and financial flows must reconcile.** A trip with no POD cannot be invoiced; an invoice without a Carta Porte can't move legally.
2. **Exceptions are the product.** Normal trips run themselves; the TMS earns its keep when something is late, stolen, rejected, damaged or disputed.
3. **Data quality at the source** (addresses, weights, SAT product codes) decides everything downstream.
4. **Small carriers dominate** in Mexico; usability on a phone beats feature depth.
5. **Compliance is a feature, not a report.** Block the illegal action at creation time.
