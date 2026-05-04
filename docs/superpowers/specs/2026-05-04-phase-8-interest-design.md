# Phase 8 — Interest Feature Design

**Date:** 2026-05-04  
**Scope:** Custom Interest entity + "Zainteresowany" toggle button on product page + `/account/interests` list

---

## Summary

Logged-in customers can mark events as "interested" (like a watchlist/heart). The interest state persists in the DB. A public counter shows total interest count per event. Guests see a tooltip prompting login.

---

## Data Layer

### Entity: `App\Entity\Customer\Interest`

Table: `watra_interest`

| Column | Type | Notes |
|---|---|---|
| id | int | PK, auto |
| customer_id | int | FK → sylius_customer, NOT NULL |
| product_id | int | FK → sylius_product, NOT NULL |
| created_at | datetime_immutable | set in constructor |

UNIQUE constraint on `(customer_id, product_id)` via `#[UniqueConstraint]`.

### Repository: `App\Repository\Customer\InterestRepository`

- `findByCustomer(Customer $c): array` — ordered by `createdAt DESC`
- `findOneByCustomerAndProduct(Customer $c, Product $p): ?Interest`
- `countByProduct(Product $p): int`

### Service: `App\Service\InterestService`

Single method: `toggle(Customer $c, Product $p): bool`
- Finds existing interest → removes + flush → returns `false`
- No existing interest → creates + flush → returns `true`

---

## Live Component: `App\Twig\Component\InterestButton`

`#[AsLiveComponent('InterestButton')]`

### State (props + computed)

- `#[LiveProp] int $productId` — passed from Twig when rendering
- Resolves `Product` and current `Customer` from security context in `mount()`
- `isInterested(): bool` — checks repository
- `count(): int` — calls `countByProduct()`

### Action

- `#[LiveAction] toggle()` — calls `InterestService::toggle()`. Throws `UnauthorizedHttpException` (→ 401) if no user; the Twig template handles guest state on initial render.

### Guest handling

The component template checks `app.user`:
- Logged in: renders a clickable heart button (data-action="live#action")
- Guest: renders a disabled heart with a Bootstrap tooltip (`data-bs-toggle="tooltip"`, title="Zaloguj się, aby śledzić wydarzenie") + `<a href="{{ path('sylius_shop_login') }}">` wrapping it

### Template: `templates/components/InterestButton.html.twig`

Shows:
- Filled heart icon (♥) when `isInterested()` is true
- Empty heart (♡) otherwise
- Counter: the number from `count()`
- CSS class `btn-danger` when active, `btn-outline-secondary` when not

---

## Integration: Product Show Page

Added via Sylius Twig Hooks on `sylius_shop.product.show.main` (or equivalent hook). Hook config in `config/packages/sylius_twig_hooks.yaml`:

```yaml
'sylius_shop.product.show.main':
    interest_button:
        template: 'shop/product/show/interest_button.html.twig'
        priority: 100
```

Hook template `templates/shop/product/show/interest_button.html.twig` renders the Live Component:

```twig
{{ component('InterestButton', { productId: product.id }) }}
```

The `product` variable is available in Sylius shop product show hooks via `hookable_metadata`.

---

## Account Interests Page

**Route:** `GET /account/interests`  
**Controller:** `App\Controller\Shop\AccountInterestController::index()`  
**Template:** `templates/shop/account/interests/index.html.twig`

The page extends Sylius account layout. Lists events the customer has marked as interested, ordered by most recently added. Each row shows:
- Event name (linked to product show page)
- Nearest upcoming variant date (first `startsAt > now()` among variants)
- City name
- "Usuń z zainteresowanych" button (POST form → toggle → redirect back)

Route registered under the `shop` firewall (requires `ROLE_USER`), added to `config/routes/app_shop.yaml`.

Navigation link added to the account sidebar via Sylius Twig Hook or template override.

---

## Migration

One new migration: creates `watra_interest` table with FK constraints and UNIQUE index.

---

## Fixtures

No dedicated interest fixtures — the feature is user-driven. Existing shop users from the `watra` fixture suite can manually test the toggle.

---

## Testing

- `tests/Service/InterestServiceTest.php` — unit: toggle add, toggle remove, idempotency via UNIQUE
- `tests/Functional/InterestButtonTest.php` — functional: guest sees disabled button, logged-in user toggles, count increments
