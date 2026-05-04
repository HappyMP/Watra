# Phase 9A — Public Frontend: Homepage, EventCard, Event Detail

**Scope:** Core browsing experience. Homepage with 3 sections, reusable EventCard component, event detail page (product show override). No filters, no map, no Stimulus JS — those are Phase 9B.

---

## Goals

1. Visitor lands on homepage and sees upcoming/featured events immediately.
2. EventCard is reusable across homepage sections, event list (9B), and wherever else needed.
3. Event detail page shows description, variants (terms) with prices and "Zapisz się" per term.
4. Footer links to static pages `/regulamin` and `/polityka-prywatnosci`.

---

## Homepage Layout

**Route:** existing `sylius_shop_homepage` (`/{_locale}/`) — no custom controller.  
**Mechanism:** Twig Hooks on `sylius_shop.homepage.index` disable Sylius defaults and inject 3 custom hookables.

### Section 1 — Hero
Static full-width dark banner. Text: "Odkryj wydarzenia w Twoim mieście" + CTA button "Zobacz wszystkie wydarzenia →" linking to `sylius_shop_product_index` (or Phase 9B event list).

### Section 2 — Spotlight (featured + upcoming)
Two-column row:
- **Left (large):** one featured event — the earliest-starting `published` product tagged with the Sylius taxon `code=featured`. Rendered as a large EventCard.
- **Right (small list):** 4 upcoming `published` events (ordered by earliest `startsAt` across variants, excluding the featured one).

Data fetched by a dedicated Twig Component `HomepageSpotlightComponent` (regular, non-live).  
If no `featured` taxon product exists: show 5 upcoming events in a plain list (no large card).

### Section 3 — By City (tabs)
Three hardcoded tabs: **Kraków · Warszawa · Online**.  
Each tab shows up to 4 events filtered by `product.city.name` (Kraków/Warszawa) or `product.isOnline = true`.  
Tab switching is pure CSS (radio + label trick) — no JavaScript required in 9A.  
Data fetched by `HomepageCityComponent` (regular Twig Component).

---

## EventCard Component

**Type:** Regular Twig Component (not Live).  
**Template:** `templates/components/EventCard.html.twig`  
**PHP class:** `src/Twig/Component/EventCard.php`

### Props
| Prop | Type | Description |
|------|------|-------------|
| `product` | `Product` | The event |
| `size` | `string` | `'normal'` (default) or `'large'` |

### Visual
- **Background:** `product.images.first()` as CSS `background-image` with dark overlay gradient. Fallback: indigo gradient (`#1e1b4b → #4c1d95`) if no image.
- **Content (bottom overlay):** event type badge · title · nearest upcoming `startsAt` date · city or "Online" · price (`min(channelPricing.price)` across enabled variants) · InterestButton (♡ + count from Phase 8).
- **CTA:** "Zobacz →" link to product show page.
- **Large size:** taller card (used in Spotlight left column).

### Nearest term helper
`EventCard` computes `nextVariant`: the `ProductVariant` with smallest `startsAt > now()`. Exposed as a computed method. If all terms are past: shows "Brak nadchodzących terminów".

---

## Product Show Page Override

**Mechanism:** Twig Hooks override existing `sylius_shop.product.show.*` hookables.

### Hero (full width, dark)
Hook: `sylius_shop.product.show.content.info.summary` — disable default `header` hookable, add custom `watra_hero` hookable.  
Template: `templates/shop/product/show/watra_hero.html.twig`  
Content: gradient background (same as EventCard) · event type badge · title · city · InterestButton with count.

### Two-column body
Hook: `sylius_shop.product.show.content.info.summary` — keep Sylius SummaryComponent (needed for cart session), but override its inner hookables:
- Disable `header` (replaced by watra_hero above)
- Keep `prices` disabled, add custom `watra_terms` hookable
- Keep `add_to_cart` from Sylius as the actual cart form (used by each term button)

Template: `templates/shop/product/show/watra_terms.html.twig`

**Left side (via `sylius_shop.product.show.content.info.overview`):** description only — disable `accordion` tabs, show description as plain HTML block.  
**Right side (summary column):** list of upcoming variants ordered by `startsAt`.

### Terms sidebar
Each variant card shows:
- Day name + date (e.g. "Sob, 15 czerwca")
- Time range (`startsAt` – `endsAt`)
- Price (from `ChannelPricing`)
- "Zapisz się →" button — uses existing Sylius add-to-cart form with variant pre-selected

Nearest upcoming variant: highlighted in indigo. Past variants: hidden. No variants: "Brak nadchodzących terminów."

**Add-to-cart implementation:** Each variant has its own Sylius `sylius_shop_add_to_cart` form with `quantity=1` and the variant code pre-filled. This reuses the existing cart mechanism from Phase 7.

---

## Footer + Static Pages

### Footer
Template added as hookable on `sylius_shop.shared.layout.footer` (or equivalent).  
Content: copyright · links to Regulamin and Polityka prywatności.

### Static pages
Two routes via `StaticPageController`:
- `GET /{_locale}/regulamin` → `app_shop_regulamin`
- `GET /{_locale}/polityka-prywatnosci` → `app_shop_privacy`

Templates: simple `templates/shop/static/regulamin.html.twig` and `privacy.html.twig` (placeholder text, extending shop layout).

---

## Data Queries

New repository methods needed:

| Method | Location | Notes |
|--------|----------|-------|
| `findFeaturedByChannel(channel, locale)` | `ProductRepository` extension or custom query | Products with taxon `code=featured`, `status=published`, ordered by nearest `startsAt` |
| `findUpcomingByChannel(channel, locale, limit, excludeIds[])` | same | Products where at least one variant has `startsAt > now()`, ordered by min `startsAt` |
| `findByCity(cityName, channel, locale, limit)` | same | Filter by `product.city.name` |
| `findOnline(channel, locale, limit)` | same | Filter by `product.isOnline = true` |

All queries use `LEFT JOIN` on `sylius_product_variant` to access `startsAt`, filter `product.status = 'published'`, and filter by the WATRA channel.

---

## File Map

| File | Action |
|------|--------|
| `src/Twig/Component/HomepageSpotlightComponent.php` | Create |
| `src/Twig/Component/HomepageCityComponent.php` | Create |
| `src/Twig/Component/EventCard.php` | Create |
| `templates/components/HomepageSpotlight.html.twig` | Create |
| `templates/components/HomepageCity.html.twig` | Create |
| `templates/components/EventCard.html.twig` | Create |
| `templates/shop/homepage/hero.html.twig` | Create |
| `templates/shop/product/show/watra_hero.html.twig` | Create |
| `templates/shop/product/show/watra_body.html.twig` | Create |
| `templates/shop/static/regulamin.html.twig` | Create |
| `templates/shop/static/privacy.html.twig` | Create |
| `src/Controller/Shop/StaticPageController.php` | Create |
| `config/packages/sylius_twig_hooks.yaml` | Modify |
| `translations/messages.pl.yaml` | Modify |
| `translations/messages.en.yaml` | Modify |

---

## Out of Scope (Phase 9B)

- Leaflet map on product show
- `/wydarzenia` event list with `EventListFilters` Live Component
- Stimulus controllers (datepicker, map, tags)
- Account orders view
- Responsive smoke test
