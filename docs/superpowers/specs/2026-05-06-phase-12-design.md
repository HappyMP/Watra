# Phase 12 — API Platform: Custom Events Endpoint Design

**Scope:** Single public `GET /api/v2/shop/events` endpoint for a headless React/Vue frontend. Simple Symfony controller, no API Platform DTO complexity.

---

## Goals

1. Headless frontend can list events with filters: city, eventType, search, dateFrom.
2. Response is consistently shaped JSON (not JSON-LD).
3. CORS headers on all `/api/v2/` routes — no new PHP dependency.
4. Existing Sylius shop API (`/api/v2/shop/products`, `/api/v2/shop/orders`) untouched.

---

## Endpoint

`GET /api/v2/shop/events`

**Security:** Public. Already covered by `access_control: { path: "%sylius.security.api_shop_regex%/.*", role: PUBLIC_ACCESS }`.

**Query parameters:**

| Param | Type | Example | Notes |
|-------|------|---------|-------|
| `city` | string | `Kraków` | Exact city name match (case-insensitive) |
| `eventType` | string | `workshop` | One of EventType enum values |
| `search` | string | `php` | LIKE on product name translation |
| `dateFrom` | string | `2026-06-01` | ISO date, filters variants.startsAt >= date |
| `locale` | string | `pl_PL` | Default: `pl_PL` |

**Response shape:**

```json
{
  "total": 6,
  "items": [
    {
      "id": 571,
      "name": "Warsztaty PHP 8.3",
      "slug": "warsztaty-php-83-nowoczesne-wzorce",
      "eventType": "workshop",
      "eventStatus": "published",
      "isOnline": false,
      "city": { "name": "Kraków" },
      "venue": { "name": "Centrum Kongresowe ICE", "address": "ul. Marii Konopnickiej 17" },
      "description": "Opis wydarzenia...",
      "imageUrl": "/media/image/path/to/image.jpg",
      "nextVariant": {
        "id": 12,
        "startsAt": "2026-06-15T10:00:00+02:00",
        "endsAt": "2026-06-15T18:00:00+02:00",
        "price": 0,
        "onHand": 50
      }
    }
  ]
}
```

`nextVariant` — nearest future enabled variant. `null` if none.
`price` — in grosze (cents). 0 = free.
`imageUrl` — null if no image.

---

## Architecture

**Controller:** `src/Controller/Api/Shop/EventController.php`
- Injects: `ProductRepository`, `ChannelContextInterface`
- Reads query params from `Request`
- Calls `productRepository->findFiltered(channelCode, locale, filters, limit=50)`
- Shapes each `Product` into the response array using private helper methods
- Returns `JsonResponse`

**CORS:** `src/EventSubscriber/CorsSubscriber.php`
- Listens on `kernel.response`
- Adds `Access-Control-Allow-Origin: *` + methods/headers for all `/api/v2/` paths
- Also handles `OPTIONS` preflight: returns 200 immediately

---

## File Map

| File | Action |
|------|--------|
| `src/Controller/Api/Shop/EventController.php` | Create |
| `src/EventSubscriber/CorsSubscriber.php` | Create |

---

## Out of Scope

- Pagination (frontend uses all results for MVP)
- City/Venue/Interest as separate API resources
- Authentication on events endpoint
- Rate limiting
- OpenAPI docs customization
- `nelmio/cors-bundle` (plain subscriber sufficient for MVP)
