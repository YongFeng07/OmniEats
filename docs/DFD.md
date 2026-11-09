# OmniEats — Data Flow Diagrams

TAR UMT AMCS2194 · Chapter 2 material · matches the implemented system
Four roles, six modules, one shared tenant-isolated database.

## 1. Context Diagram (Level 0)

```mermaid
flowchart LR
    CU[Customer] -->|register, browse, order, voucher, pay, track, review| OE
    RO[Restaurant Owner / Staff] -->|manage restaurant, menu, vouchers, orders, replies| OE
    SA[Super Admin] -->|approve restaurants, vouchers, riders, manual assignment, moderation| OE
    RI[Rider] -->|accept/reject, pickup, delivery progress| OE
    OE[OmniEats Platform] -->|simulated result| GW[Simulated Payment Gateway]

    OE -->|menus, status timeline, rider card, receipts, reviews| CU
    OE -->|incoming orders, reviews, voucher usage| RO
    OE -->|activity, stats, assignment history| SA
    OE -->|assigned deliveries| RI
```

External entities: **Customer**, **Restaurant Owner/Staff**, **Super Admin**,
**Rider**, and the **Simulated Payment Gateway** (internal component — no real
third party).

## 2. DFD Level 0 (system processes)

```mermaid
flowchart LR
    subgraph PLATFORM[OmniEats — shared tenant-isolated database]
        D1[(D1 users / roles / user_roles)]
        D2[(D2 restaurants)]
        D3[(D3 categories / food_items)]
        D4[(D4 vouchers / voucher_usages)]
        D5[(D5 user_addresses / user_favourites)]
        D6[(D6 carts / cart_items)]
        D7[(D7 orders / order_items / status_history)]
        D8[(D8 payments)]
        D9[(D9 fulfilments / rider_assignment_history)]
        D10[(D10 rider_profiles)]
        D11[(D11 reviews)]
        D12[(D12 activity_logs)]
    end

    P1[1.0 User & Auth] --> D1
    P1 --> D12
    P2[2.0 Restaurant Menu] --> D2
    P2 --> D3
    P3[3.0 Promotion & Voucher] --> D4
    P3 --> D7
    P4[4.0 Customer Profile] --> D5
    P5[5.0 Cart & Checkout] --> D6
    P5 --> D7
    P5 --> D8
    P5 --> D9
    P6[6.0 Payment & Review] --> D8
    P6 --> D11
    P7[7.0 Delivery & Rider] --> D9
    P7 --> D10
    P7 --> D7

    CU[Customer] --> P1 & P2 & P3 & P4 & P5 & P6
    RO[Owner / Staff] --> P2 & P3 & P6
    SA[Super Admin] --> P1 & P2 & P3 & P6 & P7
    RI[Rider] --> P7
```

Note: processes 1–7 are the shared system functions implementing the SIX
modules (auth/cart are the shared plumbing of the six CRUD modules).

## 3. DFD Level 1 — Delivery & Rider Management (7.0)

```mermaid
flowchart TB
    RO[Owner marks order READY] --> F[7.1 Fulfilment waiting for assignment]
    F --> SA1[Admin views awaiting deliveries + eligible riders]
    SA1 --> AS[7.2 Manual assignment transaction]
    AS -->|eligible rider| H[(rider_assignment_history)]
    AS --> G[(fulfilments: assigned)]
    G --> RP[Rider Portal: task appears]
    RP -->|accept| ACC[7.3 Accept]
    RP -->|reject + reason| REJ[7.4 Reject]
    REJ -->|records reason| H
    REJ --> F
    ACC --> PK[7.5 Pickup confirmation]
    PK --> OD[7.6 Out for delivery]
    OD --> DV[7.7 Delivered]
    DV -->|auto-completes| O[(orders: completed)]
    DV --> G
    RI[Rider availability toggle] --> EL[Eligibility checks: active + available + one active delivery]
    EL --> AS
```

The assignment is ALWAYS admin-initiated (manual) — riders never claim tasks,
and every attempt is preserved in the assignment history.
