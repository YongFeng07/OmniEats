# OmniEats — Data Flow Diagrams

TAR UMT AMCS2194 · Chapter 2 material · matches the implemented system

## 1. Context Diagram (Level 0)

```mermaid
flowchart LR
    CU[Customer] -->|register, browse, order, pay, track| OE
    RO[Restaurant Owner / Staff] -->|manage restaurant, menu, orders, payments| OE
    SA[Super Admin] -->|approve tenants, manage users & plans| OE
    OE[OmniEats Platform] -->|simulated result| GW[Simulated Payment Gateway]

    OE -->|menus, status timeline, receipts| CU
    OE -->|incoming orders, subscription status| RO
    OE -->|platform activity & stats| SA
```

External entities: **Customer**, **Restaurant Owner/Staff**, **Super Admin**, and the
**Simulated Payment Gateway** (internal component — no real third party).

## 2. DFD Level 0 (system processes)

```mermaid
flowchart LR
    subgraph PLATFORM[OmniEats — shared tenant-isolated database]
        D1[(D1 users / roles)]
        D2[(D2 restaurants / branches)]
        D3[(D3 categories / food_items)]
        D4[(D4 subscription_plans / subscriptions)]
        D5[(D5 carts / cart_items)]
        D6[(D6 orders / order_items / status_history)]
        D7[(D7 payments)]
        D8[(D8 fulfilments)]
        D9[(D9 activity_logs)]
    end

    P1[1.0 User & Auth] --> D1
    P1 --> D9
    P2[2.0 Restaurant & Branch] --> D2
    P2 --> D4
    P3[3.0 Menu & Catalogue] --> D3
    P3 --> D2
    P4[4.0 Subscription] --> D4
    P4 --> D2
    P5[5.0 Ordering] --> D5
    P5 --> D6
    P5 --> D2
    P5 --> D3
    P6[6.0 Payment & Fulfilment] --> D7
    P6 --> D8
    P6 --> D6

    CU[Customer] -->|browse| P2
    CU -->|browse| P3
    CU -->|cart / checkout| P5
    CU -->|pay| P6
    CU -->|track| P5
    RO[Owner/Staff] -->|manage| P2
    RO -->|manage menu| P3
    RO -->|subscribe| P4
    RO -->|process orders| P5
    RO -->|payments & fulfilment| P6
    SA[Super Admin] -->|users| P1
    SA -->|approve tenants| P2
    SA -->|plans & monitoring| P4
    SA -->|activity| P1
```

Process notes (implemented in code):
- **1.0** `AuthService`, `UserService`, `UserController`, `RegisterController`, `ProfileController`
- **2.0** `RestaurantService`, `BranchService`, owner/admin restaurant controllers
- **3.0** `CategoryService`, `FoodItemService` (+ `UploadService`)
- **4.0** `SubscriptionService` (★ auto-expiry + quota), `PlanService`
- **5.0** `CartService`, `OrderService` (★ validated transitions + status history)
- **6.0** `PaymentService` (simulated gateway), `FulfilmentService`

Every process reads/writes through PDO prepared statements; tenant isolation is
enforced in the application layer using the session-derived `restaurant_id`.
