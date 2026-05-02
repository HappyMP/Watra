# WATRA

Platforma do organizacji wydarzeń (na start Kraków, docelowo wiele miast). Portal do listowania wydarzeń, zapisów, zarządzania katalogiem przez administrację. Zbudowana na **Sylius 2.x** (Symfony 7 + API Platform 4 + Bootstrap + Symfony UX).

> **Status:** w fazie planowania. Kodzenie jeszcze nie rozpoczęte. Pełen plan: [docs/PLAN.md](docs/PLAN.md). Roadmapa MVP w taskach: [docs/TASKS.md](docs/TASKS.md).

## Dokumentacja

- [docs/PLAN.md](docs/PLAN.md) — pełen plan architektury i bootstrapu MVP
- [docs/TASKS.md](docs/TASKS.md) — szczegółowy rozpis na ~85 atomowych tasków, 14 faz
- [docs/BACKLOG.md](docs/BACKLOG.md) — funkcje poza MVP (płatności, faktury, marketplace, etc.)
- [docs/GLOSSARY.md](docs/GLOSSARY.md) — mapa nazw Sylius ↔ domena WATRY (`Product` = Wydarzenie, `Order` = Booking, ...)
- [docs/RBAC.md](docs/RBAC.md) — system uprawnień (placeholder, pełna dokumentacja po implementacji)

## Quickstart (po Fazie 1 bootstrapu)

```bash
make up        # postaw kontenery (php, nginx, postgres, mailpit, redis)
make install   # composer install
make migrate   # migracje DB
make fixtures  # załaduj testowe dane
```

Otwórz `http://localhost` (shop) lub `http://localhost/admin` (admin).

## Wymagania

Zostaną sprawdzone przez `bin/check-env.sh` (Faza 0). Spodziewane:
- PHP 8.3+ (extensions: pdo_pgsql, intl, gd, zip, opcache, mbstring, xml)
- Composer 2.7+
- Symfony CLI
- Node 20 LTS
- Docker + Docker Compose
- PostgreSQL 16 (w Dockerze)

## Stack

- **Backend:** Sylius 2.x (Symfony 7.x, PHP 8.3+, Doctrine 3, API Platform 4)
- **Frontend:** Bootstrap 5.3 + Twig Hooks + Symfony UX (Turbo, Live Components, Stimulus)
- **DB:** PostgreSQL 16
- **Mail (dev):** Mailpit
- **Multi-locale:** PL (default) + EN

## Słowniczek (skrót)

W kodzie używamy nazewnictwa Sylius'a. W UI i dokumentacji — domeny WATRY:

| Sylius (kod) | WATRA (UI) |
|---|---|
| `Product` | Wydarzenie |
| `ProductVariant` | Termin |
| `Order` | Rezerwacja / Booking |
| `Customer` | Uczestnik |
| `Taxon` | Tag |

Pełna mapa: [docs/GLOSSARY.md](docs/GLOSSARY.md).
