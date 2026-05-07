# WATRA — Architecture

## Domain mapping (Sylius → WATRA)

| Sylius | WATRA | Notes |
|--------|-------|-------|
| `Product` | Wydarzenie | + city, venue, isOnline, eventStatus, eventType |
| `ProductVariant` | Termin | + startsAt, endsAt; capacity = onHand |
| `Order` | Booking/Rezerwacja | OrderItem = single ticket |
| `Customer` | Uczestnik | extended (empty, future-proof) |
| `Taxon` | Tag | native, translatable |
| `Channel` | WATRA (one channel) | reserved for future marketplace |

Custom entities: `City`, `Venue`, `Interest`, `AdministrationRole`.

## Tech stack

PHP 8.3 · Sylius 2.2 · Symfony 7 · PostgreSQL 16 · API Platform 4 · Bootstrap 5 + Twig Hooks + Symfony UX (Live Components, Stimulus) · Messenger (doctrine transport, async email) · Mailpit (dev SMTP) · Docker Compose

## Key decisions

- **Payments:** free-only MVP (total = 0, `no_shipping` + `free_payment` fixtures, steps skipped)
- **RBAC:** custom `Permission` enum + `PermissionVoter` + `AdministrationRole` entity (Sylius+ RBAC is paid)
- **Admin menu:** KNP Menu via `sylius.menu.admin.main` event — NOT Twig Hooks
- **Async email:** `Symfony\Mailer\Messenger\SendEmailMessage` → `doctrine://default` transport
- **API:** custom `GET /api/v2/shop/events` controller (not API Platform DTO) + `CorsSubscriber`
- **Twig Hooks overrides:** see `docs/SYLIUS_OVERRIDES.md`

## CI

GitHub Actions: `.github/workflows/app-ci.yml`
- **ci** job: PHP 8.3, PostgreSQL 16, `composer install`, `npm run build`, migrations, `watra` fixtures, `bin/ci.sh` (PHPStan lvl 7 + ECS + PHPUnit + Behat)
- **lint** job: `lint:twig`, `lint:yaml`, `lint:container`, `schema:validate`
