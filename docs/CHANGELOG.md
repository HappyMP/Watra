# WATRA — Changelog MVP (fazy 0-14)

Wszystkie fazy ukończone: **maj 2026**. Tag: `mvp-1.0`.

| Faza | Co zostało zbudowane |
|------|----------------------|
| **0** | Weryfikacja środowiska (Linux), `bin/check-env.sh`, Docker bez sudo |
| **1** | Bootstrap Sylius 2.2 + Docker Compose (php/nginx/postgres/mailpit/redis/nodejs), Makefile |
| **2** | Lokalizacja PL/EN, channel WATRA, translations override ("Wydarzenia", "Rezerwacje", …) |
| **3** | Ukrycie zbędnych sekcji Sylius w admin (Shipping/Tax/Zones), fixtures: no_shipping + free_payment + PL zone 0% |
| **4** | Custom encje: `City`, `Venue`, `AdministrationRole` z migracjami, CRUD w adminie, KNP menu "WATRA" |
| **5** | Extend `Product` (city, venue, eventStatus, eventType), `ProductVariant` (startsAt, endsAt), `AdminUser` (roles M2M) |
| **6** | RBAC: `Permission` enum, `PermissionVoter`, `AdminRoutePermissionMap`, role fixtures (Super Admin, Editor, Marketer, Front Desk) |
| **7** | Checkout darmowy (total=0, skip shipping/payment), `FreeOrderPaymentListener`, strona potwierdzenia via Twig Hook |
| **8** | `Interest` entity, toggle "Zainteresowany" (Live Component), `/account/interests` |
| **9** | Frontend shop: homepage (hero, spotlight, city tabs), EventCard, product show (hero+opis+terminy+mapa Leaflet), `/wydarzen ia` z Live Filters, `/account/orders` |
| **10** | Emaile transakcyjne: WATRA layout (gradient, SVG logo), booking potwierdzenie/anulowanie, async Messenger (doctrine), Mailpit |
| **11** | Admin dashboard (4 karty metryk + CSS paski obłożenia), `/admin/products/{id}/attendees` + CSV export |
| **12** | `GET /api/v2/shop/events` (headless frontend, filtry: city/eventType/search/dateFrom), `CorsSubscriber` |
| **13** | Behat (5 scenariuszy, BrowserKit), PHPUnit API tests, `bin/ci.sh` (PHPStan+ECS+PHPUnit+Behat) |
| **14** | 10 fixtures events (Kraków/Warszawa/Wrocław/online), PHPStan lvl 7, docs (RBAC.md, SYLIUS_OVERRIDES.md), GitHub Actions CI |
