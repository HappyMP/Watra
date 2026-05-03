# WATRA — project instructions for Claude

## Code language

All code must be written in English: variable names, function names, method names, class names, constants, interfaces, code comments.

Polish is allowed only in:
- `translations/messages.pl.yaml` and other translation files
- UI strings in Twig templates (`{% trans %}`, `{{ 'key'|trans }}`)
- Fixture data (proper nouns: city names, event names, etc.)

Examples:
- ✅ `$eventName`, `findByCity()`, `BookingRepository`, `getStartDate()`
- ❌ `$nazwaWydarzenia`, `znajdzPoMiescie()`, `Repozytorium`

## Git commits

Do not add `Co-Authored-By` lines to commit messages.

## Tech stack

- Sylius 2.2.x (Symfony 7, PHP 8.3, Doctrine ORM 3)
- PostgreSQL 16 (not MySQL)
- Mailpit for dev SMTP (not Mailhog)
- Docker Compose via `compose.yaml` (not `compose.yml`)

## Key architectural decisions

- `Product` = event, `ProductVariant` = date/occurrence, `Order` = booking, `Customer` = attendee
- Admin menu uses KNP Menu via event `sylius.menu.admin.main` — NOT Twig Hooks
- Hiding menu items: EventListener on `sylius.menu.admin.main`, call `removeChild()`
- See `docs/SYLIUS_OVERRIDES.md` for verified Sylius 2.x conventions

## Task workflow

After completing each task:
1. Mark the task as `[x]` in `docs/TASKS.md` (with notes if needed)
2. Push changes to remote: `git push`
3. Continue to the next task

This applies to all remaining phases (3–14).

## Dev commands

```bash
sudo docker compose -f compose.yaml up -d --build   # start stack
sudo docker compose -f compose.yaml exec php bash    # PHP shell
sudo docker compose -f compose.yaml exec php bin/console <cmd>
```

Or via Makefile: `make up`, `make bash`, `make migrate`, `make fixtures`
