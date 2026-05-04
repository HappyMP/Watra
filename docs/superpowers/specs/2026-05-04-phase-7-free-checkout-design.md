# Phase 7 — Free Checkout Design

## Goal

Checkout for free events (total = 0) completes in 3 clicks with no shipping address or payment selection. Order ends as `fulfilled`, payment as `paid`.

## Checkout Flow

```
cart → address → shipping_skipped → payment_skipped → complete
```

All steps are skipped natively by Sylius — no custom controllers or template overrides for skip logic.

## Components

### 1. Shipping Skip (native)

- Set `shippingRequired: false` as default on `ProductVariantTypeExtension` (already exists from phase 5)
- `Order::isShippingRequired()` returns `false` → `CheckoutStateResolver` auto-applies `skip_shipping`
- Channel fixture: add `skipping_shipping_step_allowed: true`, `skipping_payment_step_allowed: true`

### 2. Payment Skip (native)

- `OrderPaymentMethodSelectionRequirementChecker::isPaymentMethodSelectionRequired()` returns `false` when `order.total <= 0` — already built into Sylius
- No custom code needed for the skip itself

### 3. FreeOrderPaymentListener

**File:** `src/EventListener/FreeOrderPaymentListener.php`

Listens to winzou state machine `complete` transition on `sylius_order_checkout`, priority `-100` (before `sylius_control_payment_state` at `-200`).

Logic:
1. If `order.total > 0` → return early
2. For each payment in `STATE_CART` with amount 0 → apply `complete` transition on payment state machine
3. `OrderPaymentStateResolver` then sees `completedPaymentTotal (0) >= order.total (0)` → applies `TRANSITION_PAY` → `order.paymentState = paid`

Registered via `config/packages/winzou_state_machine.yaml` callback `watra_complete_free_payment`.

### 4. BookingService

**File:** `src/Service/BookingService.php`

```php
reserveSlot(Order $order): void
releaseSlot(Order $order): void
```

`reserveSlot`:
1. For each OrderItem in order
2. `EntityManager::lock($variant, LockMode::PESSIMISTIC_WRITE)` — SELECT FOR UPDATE
3. Check `variant.onHand - variant.onHold >= item.quantity`
4. If insufficient: throw `InsufficientStockException` (HTTP 409)
5. If ok: `variant.onHold += item.quantity`, persist

Called from `FreeOrderPaymentListener` (same listener, after payment completion), wrapped in a transaction.

### 5. Confirmation Page (Twig Hook)

Override `sylius_shop.checkout.complete.content.form` via `config/packages/sylius_twig_hooks.yaml`.

**Template:** `templates/shop/checkout/complete/booking_confirmation.html.twig`

Content:
- Success banner: "Rezerwacja potwierdzona! 🎉"
- Per-item card: event name, variant date/time, venue name, quantity
- Order number (`#{{ order.number }}`)
- Button: "Moje rezerwacje" → `/account/orders`

### 6. Smoke Test (7.6)

Manual: login as `jan.kowalski@example.pl` → pick event → add to cart → checkout → verify:
- No shipping step shown
- No payment step shown
- Lands on confirmation page with event details
- Admin panel: order `fulfilled`, payment `paid`

### 7. Race Condition Test (7.7)

**File:** `tests/Service/BookingRaceConditionTest.php`

PHPUnit functional test:
1. Set variant `onHand = 1`, `onHold = 0`
2. Create two orders for the same variant (quantity 1 each)
3. Simulate concurrent `reserveSlot()` calls in separate transactions
4. Assert: one succeeds, one throws `InsufficientStockException`
5. Verify lock is `LockMode::PESSIMISTIC_WRITE` (inspect service directly)

## Files to Create/Modify

| File | Action |
|------|--------|
| `src/EventListener/FreeOrderPaymentListener.php` | Create |
| `src/Service/BookingService.php` | Create |
| `src/Exception/InsufficientStockException.php` | Create |
| `templates/shop/checkout/complete/booking_confirmation.html.twig` | Create |
| `config/packages/winzou_state_machine.yaml` | Create |
| `config/packages/sylius_twig_hooks.yaml` | Create or modify |
| `config/packages/sylius_fixtures.yaml` | Modify (channel skipping flags) |
| `src/Form/Extension/ProductVariantTypeExtension.php` | Modify (shippingRequired default) |
| `tests/Service/BookingRaceConditionTest.php` | Create |
| `tests/Smoke/CheckoutSmokeTest.php` | Create |
