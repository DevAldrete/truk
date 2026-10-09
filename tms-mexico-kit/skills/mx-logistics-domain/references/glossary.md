# Glossary (EN ↔ ES) — Logistics & TMS in Mexico

Confidence tags used across this kit:
- **[R]** = researched during this session from web sources (still verify before building compliance logic)
- **[K]** = general/domain knowledge, NOT re-verified this session — verify against official source before relying on it

## People and companies
| Term (ES) | Meaning (EN) |
|---|---|
| Generador de carga / Embarcador | **Shipper** — owns the goods and needs them moved |
| Consignatario / Destinatario | **Consignee** — receives the goods |
| Transportista / Permisionario | **Carrier** — holds the SICT permit and moves the goods [K] |
| Hombre-camión | **Owner-operator** — 1–5 trucks, often owner drives; ~80% of carrier permit holders are independents/small fleets [R] |
| Operador | **Truck driver** (legally "conductor"); needs a *licencia federal* [K] |
| Intermediario / Agente de transporte | **Broker / freight forwarder** — arranges transport without necessarily owning trucks |
| Operador logístico / 3PL | **Third-party logistics provider** |
| Agente aduanal | **Customs broker** — licensed, jointly liable for customs declarations |
| PAC | **Proveedor Autorizado de Certificación** — SAT-authorized company that stamps (timbra) CFDIs [K] |

## Government and rules
| Term | Meaning |
|---|---|
| SAT | Tax authority. Owns CFDI and Carta Porte |
| SICT (formerly SCT) | Ministry of Infrastructure, Communications and Transport. Issues carrier permits and NOMs for road transport [K] |
| ANAM | National Customs Agency [R] |
| VUCEM | Single window for foreign trade (digital customs paperwork) [R] |
| Guardia Nacional | Federal road police on federal highways [K] |
| CANACAR | National chamber of freight carriers (industry voice) |
| ANTP | National Private Transport Association (publishes theft data) [R] |
| AMIS | Insurers' association (publishes theft/claims data) [R] |
| NOM | *Norma Oficial Mexicana* — mandatory technical standard |
| DOF | *Diario Oficial de la Federación* — where laws/NOMs are published; the **source of truth** |

## Documents
| Term | Meaning |
|---|---|
| CFDI | Mexican electronic invoice (XML stamped by a PAC). Version 4.0 current [K] |
| Complemento Carta Porte | CFDI add-on describing a goods movement on the road. Current version **3.1** (mandatory since 17 Jul 2024) [R] |
| CFDI de Ingreso | Revenue invoice — carrier bills for transport service [R] |
| CFDI de Traslado | Zero-value transfer CFDI — goods owner/intermediary covers own movement [R] |
| IdCCP | 36-char UUID-style identifier of a Carta Porte complement [R] |
| Complemento de Pago (REP) | Payment receipt CFDI required when an invoice is paid later/in installments (PPD) [K] |
| Pedimento | Customs declaration (import/export) |
| DODA | Customs operation document used at the border/port [R] |
| MVE | *Manifestación de Valor Electrónica* — electronic customs-value declaration [R] |
| Expediente electrónico | Mandatory electronic file backing each pedimento, expanded by the 2026 reform [R] |
| Bitácora de viaje | Driver log of driving/rest time required by NOM-087 [R] |
| POD / Acuse / Remisión | Proof of delivery (signed delivery note) |
| Póliza RC | Civil-liability insurance policy (required in Carta Porte for autotransporte) [R] |

## Operations
| Term | Meaning |
|---|---|
| FTL / Carga completa | Full truckload — one shipper uses the whole truck |
| LTL / Carga consolidada | Less-than-truckload — several shippers share a truck |
| Paquetería | Parcel/courier shipments |
| Última milla | Last mile — final delivery to the end customer |
| Cross-dock | Transfer goods between trucks without storing |
| Tractocamión | Tractor unit (cab) |
| Semirremolque / Remolque | Semi-trailer / full trailer |
| Full (T3-S2-R4) | Double-trailer combination; configuration codes are in NOM-012 [R] |
| Caseta | Toll booth. Tolls are a major route cost [K] |
| Viáticos | Driver travel allowance (food/lodging) |
| Anticipo | Cash advance to driver for fuel/tolls |
| Liquidación (de operador) | End-of-trip driver settlement: advances vs. actual expenses vs. pay |
| Estadías | Detention/demurrage — waiting time charges |
| Maniobras | Loading/unloading handling services |
| Falso flete | Charge for a trip that was booked but cancelled/not loaded [K] |
| Viaje en vacío / Retorno | Empty (deadhead) leg / backhaul |
| Cabotaje | Domestic transport by a foreign carrier — restricted (e.g., in the US for Mexican carriers) [R] |
| Drayage / Transfer | Short-haul moves around ports/borders |
| Monitoreo / Centro de monitoreo | 24/7 tracking and security operations room |
| Rastreo / GPS / Telemática | Vehicle tracking and sensor data |
| OTIF | On-Time-In-Full — the headline delivery KPI |
| TMS / WMS / ERP / YMS | Transport / Warehouse / Enterprise / Yard management systems |
| Load board / Bolsa de carga | Marketplace matching loads with available trucks [R] |
| IMMEX | Maquila/export manufacturing program with special customs rules |
| T-MEC (USMCA) | North American trade agreement; joint review scheduled for 2026 [R] |
