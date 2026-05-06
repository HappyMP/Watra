# Phase 10 — Email Transactional Design

**Scope:** WATRA-branded transactional emails for registration, verification, password reset, booking confirmation, and booking cancellation. All sent asynchronously via Symfony Messenger + Doctrine transport.

---

## Goals

1. All transactional emails show WATRA branding (not Sylius defaults).
2. Booking confirmation email contains event name, date/time, and link to account orders.
3. Booking cancellation email is sent automatically when admin cancels an order.
4. All emails are dispatched asynchronously via Messenger (Doctrine transport).
5. Mailer sender name/address updated from "Example.com" to "WATRA".

---

## Logo

SVG flame icon + "WATRA" wordmark, inline in the email layout header. Also saved as a standalone file for web UI use.

**Header colours:** gradient `linear-gradient(135deg, #1e1b4b 0%, #312e81 100%)` (same as shop hero).  
**Flame icon:** three `<path>` in `#a78bfa` (outer), `#7c3aed` (inner), `#4c1d95` (tip).  
**Wordmark:** Arial 28px bold, white, letter-spacing 4px + subtitle "PLATFORMA WYDARZEŃ" in `#a78bfa` 9px.

Standalone SVG: `public/images/watra-logo.svg`

---

## Email Layout Override

**File:** `templates/bundles/SyliusCoreBundle/Email/layout.html.twig`

Overrides `@SyliusCore/Email/layout.html.twig`. All existing Sylius email templates extend this, so the WATRA branding is applied everywhere automatically.

**Structure:**
```
┌─────────────────────────────────────────┐
│  gradient header: flame icon + WATRA    │  #1e1b4b → #312e81
├─────────────────────────────────────────┤
│  white content area ({% block content%})│  padding 36px 40px
├─────────────────────────────────────────┤
│  footer: © 2026 WATRA · kontakt@watra.pl│  bg #f5f3ff, text #7c3aed
└─────────────────────────────────────────┘
```

Removes the Sylius logo and "sylius.com" footer link.

---

## Sylius Mailer Config Override

**File:** `config/packages/sylius_mailer.yaml`

Updates sender name/address and registers the `booking_cancelled` email type:

```yaml
sylius_mailer:
    sender:
        name: 'WATRA'
        address: 'kontakt@watra.pl'
    emails:
        booking_cancelled:
            subject: 'app.emails.booking_cancelled.subject'
            template: 'email/booking_cancelled.html.twig'
            enabled: true
```

---

## Booking Confirmation Email (10.4)

**Mechanism:** Override `@SyliusCore/Email/Blocks/OrderConfirmation/_content.html.twig`  
**Override file:** `templates/bundles/SyliusCoreBundle/Email/Blocks/OrderConfirmation/_content.html.twig`

**Content:**
- "Twoja rezerwacja #{{ order.number }} została potwierdzona"
- Per order item: event name, date/time (`variant.startsAt`), city (if set)
- CTA button: "Zobacz rezerwację →" → `sylius_shop_account_order_show` (tokenValue)

Variables available: `order`, `localeCode`, `channel` (same as original Sylius template).

---

## Booking Cancellation Email (10.5)

**Trigger:** `workflow.sylius_order.completed.cancel` event (Symfony Workflow, fired when admin cancels order).

**Subscriber:** `src/EventSubscriber/BookingCancelledEmailSubscriber.php`
- Implements `EventSubscriberInterface`
- `getSubscribedEvents()`: `['workflow.sylius_order.completed.cancel' => 'onOrderCancelled']`
- `onOrderCancelled(CompletedEvent $event)`: gets `Order` from subject → gets customer email → calls `SenderInterface::send('booking_cancelled', [email], $localeCode, ['order' => $order, 'channel' => $channel])`

**Template:** `templates/email/booking_cancelled.html.twig`  
Extends `@SyliusCore/Email/layout.html.twig`.

**Content:**
- Subject (PL): "Twoja rezerwacja na {{ eventName }} została anulowana"
- Body: event name, original date/time, apology message, link to event list

**Translation key** (subject): `app.emails.booking_cancelled.subject` (used in sylius_mailer.yaml)

---

## Messenger Async Transport (10.6)

**File:** `config/packages/messenger.yaml`

Sylius uses `SymfonyMailerAdapter` → `Symfony\Component\Mailer\MailerInterface::send()`. Symfony Mailer internally dispatches `Symfony\Component\Mailer\Messenger\SendEmailMessage` when a Messenger bus is available. Routing this message to `async` makes all Sylius emails asynchronous.

```yaml
framework:
    messenger:
        transports:
            async: 'doctrine://default'
            sync: 'sync://'
        routing:
            'Symfony\Component\Mailer\Messenger\SendEmailMessage': async
```

Worker command: `bin/console messenger:consume async --limit=50`

---

## README Documentation (10.7)

Add section `## Emaile transakcyjne` to `README.md`:

```
### Mailpit (dev)
Maile dostępne pod: http://localhost:8025

### Worker Messenger (async emaile)
sudo docker compose exec php bin/console messenger:consume async --limit=50

### Uruchomienie workera w tle
sudo docker compose exec -d php bin/console messenger:consume async
```

---

## File Map

| File | Action |
|------|--------|
| `templates/bundles/SyliusCoreBundle/Email/layout.html.twig` | Create — WATRA layout |
| `templates/bundles/SyliusCoreBundle/Email/Blocks/OrderConfirmation/_content.html.twig` | Create — booking details |
| `templates/email/booking_cancelled.html.twig` | Create — cancellation email |
| `public/images/watra-logo.svg` | Create — standalone logo |
| `src/EventSubscriber/BookingCancelledEmailSubscriber.php` | Create |
| `config/packages/sylius_mailer.yaml` | Create — sender + booking_cancelled type |
| `config/packages/messenger.yaml` | Modify — add async transport + routing |
| `translations/messages.pl.yaml` | Modify — email subject/body keys |
| `translations/messages.en.yaml` | Modify — same in English |
| `README.md` | Modify — worker docs |

---

## Out of Scope

- Email verification link formatting (uses Sylius default token link)
- Shipment confirmation email (not relevant — no shipping in MVP)
- Email open/click tracking
- HTML → plain text fallback (not required for MVP)
