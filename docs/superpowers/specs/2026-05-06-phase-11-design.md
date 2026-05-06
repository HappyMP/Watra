# Phase 11 — Admin Dashboard + Attendees List Design

**Scope:** Replace Sylius dashboard statistics with WATRA-specific metrics (4 cards + occupancy chart). Add attendee list page per event with CSV export.

---

## Goals

1. Admin dashboard shows 4 WATRA metrics: published events, bookings (7d), new customers (7d), average occupancy %.
2. Below the cards: horizontal occupancy bars for upcoming published events (top 8 by nearest `startsAt`).
3. Each event in admin has a "Lista uczestników" button → `/admin/products/{id}/attendees`.
4. Attendees page shows table: name, email, variant date, order number, state. Guarded by `Permission::ATTENDEE_INDEX`.
5. CSV export button on attendees page, guarded by `Permission::BOOKING_EXPORT`.

---

## Architecture

### Dashboard Metrics

**Service:** `App\Service\DashboardMetricsService`  
Injected into a Twig Component. Computes all 5 metrics via DQL queries.

**Twig Component:** `App\Twig\Component\Admin\DashboardMetricsComponent`  
`#[AsTwigComponent('watra_admin:dashboard:metrics')]`  
Registered via Twig Hook at `sylius_admin.dashboard.index.content` — disables `statistics` and `latest_statistics`, injects the WATRA component.

**Metrics:**
| Key | Query |
|-----|-------|
| `publishedEvents` | `COUNT(p) WHERE p.eventStatus = 'published' AND p.enabled = true` |
| `bookings7d` | `COUNT(o) WHERE o.state != 'cart' AND o.createdAt > NOW-7d` |
| `newCustomers7d` | `COUNT(c) WHERE c.createdAt > NOW-7d` |
| `avgOccupancy` | Average of per-product occupancy (see below) |
| `upcomingOccupancy` | Array of `{name, occupancyPct}` for top 8 upcoming events |

**Occupancy formula per product:**  
`occupancy% = fulfilledOrders / totalOnHand * 100`  
Where:
- `fulfilledOrders` = `COUNT(DISTINCT o.id)` for orders with items from this product's variants, `state IN ('fulfilled', 'new', 'paid')`
- `totalOnHand` = `SUM(v.onHand)` across all enabled variants of the product

Products where `totalOnHand = 0` or `totalOnHand IS NULL` are excluded from occupancy calculation.

### Attendees List

**Controller:** `App\Controller\Admin\AttendeeController`  
Route: `GET /admin/products/{id}/attendees` → `app_admin_attendees`  
Route: `GET /admin/products/{id}/attendees.csv` → `app_admin_attendees_csv`

Both routes added to `AdminRoutePermissionMap`:
- `app_admin_attendees` → `Permission::ATTENDEE_INDEX`  
- `app_admin_attendees_csv` → `Permission::BOOKING_EXPORT`

**Query:** All `Order` entities where:
- At least one `OrderItem` has a `variant` belonging to a variant of `product` with given `id`
- `order.state NOT IN ('cart', 'cancelled')`
- Ordered by `order.createdAt DESC`

**Twig Hook link:** Hook `sylius_admin.product.show.content.header.title_block.actions` — add `watra_attendees` hookable (template with link button).

---

## File Map

| File | Action |
|------|--------|
| `src/Service/DashboardMetricsService.php` | Create |
| `src/Twig/Component/Admin/DashboardMetricsComponent.php` | Create |
| `templates/admin/dashboard/metrics.html.twig` | Create — 4 cards + occupancy bars |
| `src/Controller/Admin/AttendeeController.php` | Create |
| `templates/admin/attendees/index.html.twig` | Create — table + CSV button |
| `templates/admin/product/show/attendees_link.html.twig` | Create — "Lista uczestników" button |
| `config/packages/sylius_twig_hooks.yaml` | Modify — dashboard + product show hooks |
| `config/routes/app_admin.yaml` | Modify — add attendees routes |
| `src/Security/AdminRoutePermissionMap.php` | Modify — add attendees routes |
| `translations/messages.pl.yaml` | Modify |
| `translations/messages.en.yaml` | Modify |

---

## Dashboard Template

4 cards (grid 4-column) with colored left border:
- Opublikowane wydarzenia — purple `#7c3aed`
- Rezerwacje 7d — green `#059669`
- Nowi uczestnicy 7d — blue `#0ea5e9`
- Śr. obłożenie — amber `#f59e0b`

Below cards: section "Obłożenie nadchodzących wydarzeń" — horizontal progress bars, one row per upcoming event. Bar color: `#7c3aed`, background: `#f0eefc`. Shows event name + percentage.

---

## Attendees Table

Columns: Uczestnik (first + last name), Email, Termin (`variant.startsAt`), Rezerwacja # (order number), Status (badge).

"Podgląd →" link to `sylius_admin_order_show`.

CSV export columns: Imię, Nazwisko, Email, Termin, Numer rezerwacji, Status.

---

## Out of Scope

- Pagination on attendees list (MVP: max 200 rows)
- Filtering/sorting on attendees list
- Dashboard channel selector (uses default WATRA channel)
- Chart.js (replaced by pure CSS progress bars — simpler, no JS dependency)
