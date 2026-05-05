# Phase 9B — Event List, Filters, Map, Account Orders

**Scope:** Event list page with live filters, Leaflet map on product show, Stimulus controllers, account orders view.

---

## Goals

1. Visitor can browse all events at `/pl_PL/taxons/wydarzenia` with real-time filters (city, type, search, date).
2. Event detail page shows a Leaflet map with venue location.
3. Account "Moje rezerwacje" shows a compact, styled order history instead of the default Sylius table.

---

## Event List Page

**Route:** existing `sylius_shop_product_index` (`/{_locale}/taxons/{slug}`)  
**Taxon slug:** `wydarzenia` — needs to be created as a Sylius taxon (fixture or migration).  
**URL:** `/pl_PL/taxons/wydarzenia`  
**Mechanism:** Twig Hooks on `sylius_shop.product.index` — disable Sylius defaults, inject `EventListFilters` Live Component + event grid.

### Layout
- Full-width page header: "Wszystkie wydarzenia"
- Horizontal filter bar: `[Miasto ▼]` `[Typ ▼]` `[Szukaj... 🔍]` `[Data od 📅]`
- Below filters: responsive grid of `EventCard` components (same as homepage)
- "Brak wyników" empty state when filters return nothing

---

## EventListFilters Live Component

**Class:** `src/Twig/Component/EventListFiltersComponent.php`  
**Template:** `templates/components/EventListFilters.html.twig`  
**Type:** Live Component (`#[AsLiveComponent]`, route: `sylius_shop_live_component`)

### LiveProps
| Prop | Type | Default |
|------|------|---------|
| `city` | `string` | `''` |
| `eventType` | `string` | `''` |
| `search` | `string` | `''` |
| `dateFrom` | `string` | `''` |

### Behaviour
- Any filter change triggers a Live Component re-render (no AJAX calls needed outside LiveComponent).
- `search` field uses `data-action="input->live#update"` with `debounce(300)`.
- `#[ExposeInTemplate] getProducts()` — calls `ProductRepository::findFiltered()` with active filters, returns `Product[]`.

### New repository method
`ProductRepository::findFiltered(string $channelCode, string $locale, array $filters, int $limit = 20): Product[]`

Filters handled: `city` (city translation name), `eventType` (enum value), `search` (LIKE on product name translation), `dateFrom` (variants with `startsAt >= dateFrom`).

---

## Leaflet Map (Product Show Page)

**Mechanism:** Stimulus controller `map_controller.js` activated on the product show `watra_description` template section.  
**Library:** Leaflet via CDN — loaded only on product show page (added to `watra_hero.html.twig` or `watra_description.html.twig`).

### Coordinates strategy
No lat/lng in DB — use hardcoded city-centre fallback:
- Kraków: `50.0647, 19.9450`
- Warszawa: `52.2297, 21.0122`
- Online / fallback: do not show map

Map shows a single marker at city centre with a popup containing venue name + address (from `product.defaultVenue`). Geocoding left for future phases.

### Integration
`watra_description.html.twig` — if `product.defaultVenue` exists and `product.city` is Kraków or Warszawa, render a `<div data-controller="map" data-map-lat-value="..." data-map-lng-value="..." data-map-label-value="...">` block. Stimulus controller initialises Leaflet inside it.

---

## Stimulus Controllers

Three controllers in `assets/shop/controllers/`:

### `map_controller.js`
- Loads Leaflet (CDN `<script>` already in template).
- `connect()` → reads `lat`, `lng`, `label` from controller values → initialises `L.map()` → adds tile layer (OpenStreetMap) → adds marker with popup.

### `datepicker_controller.js`
- Minimal wrapper: sets `min` attribute on `<input type="date">` to today, ensures consistent styling across browsers.
- Used by `dateFrom` filter input in `EventListFilters` template.

### `tag_selector_controller.js`
- Multi-select enhancement for event type filter (if expanded to multi-type in future).
- For Phase 9B: simple `<select>` wrapper that clears selection on "×" click.

All three registered in `assets/shop/controllers.json`.

---

## Account Orders Page

**Route:** existing Sylius account orders route — override via Twig Hook `sylius_shop.account.order.index`.  
**Mechanism:** Disable default content hookable, inject `watra_orders` hookable.  
**Template:** `templates/shop/account/orders/main.html.twig`

### Layout (per mockup)
Each order row contains:
- Left colour bar (3px) — green = fulfilled/confirmed, indigo = paid, grey = completed/past
- Event name (from order item variant product name)
- Date + time · City · Event type — single compact line
- Status badge (pill)
- Price (order total)
- "Szczegóły →" link to `sylius_shop_order_show`

Past orders (all variants have `startsAt < now`) are rendered at 70% opacity.

Empty state: "🎟 Brak rezerwacji" message.

---

## File Map

| File | Action |
|------|--------|
| `src/Twig/Component/EventListFiltersComponent.php` | Create |
| `templates/components/EventListFilters.html.twig` | Create |
| `templates/shop/event_list/main.html.twig` | Create |
| `templates/shop/product/show/watra_description.html.twig` | Modify — add map block |
| `templates/shop/product/show/watra_hero.html.twig` | Modify — add Leaflet CDN |
| `templates/shop/account/orders/main.html.twig` | Create |
| `assets/shop/controllers/map_controller.js` | Create |
| `assets/shop/controllers/datepicker_controller.js` | Create |
| `assets/shop/controllers/tag_selector_controller.js` | Create |
| `assets/shop/controllers.json` | Modify — register 3 controllers |
| `src/Repository/Product/ProductRepository.php` | Modify — add `findFiltered()` |
| `config/packages/sylius_twig_hooks.yaml` | Modify |
| `translations/messages.pl.yaml` | Modify |
| `translations/messages.en.yaml` | Modify |

---

## Out of Scope (future phases)

- Geocoding venues to real lat/lng
- Pagination on event list
- Responsive smoke test (9C if needed)
- Multi-type filter (tag_selector used as simple select for now)
