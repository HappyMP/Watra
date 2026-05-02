# Projekt WATRA — plan architektury i bootstrapu MVP (Sylius 2.x)

## Context

WATRA to platforma do organizacji wydarzeń (na start w Krakowie, docelowo wiele miast). Portal listuje wydarzenia, użytkownicy je przeglądają, zapisują się, oznaczają jako "zainteresowany". Administracja ma pełny CRUD nad katalogiem.

**Kluczowa decyzja architektoniczna:** używamy **pełnego Sylius'a 2.x**, nie samego Sylius Stack. Powód: trajektoria projektu (płatności, listingi z kartami, faktury, prawdopodobnie promocje i marketplace organizatorów) to klasyczna domena e-commerce. Sylius mapuje się na nią 1:1 — `Product` = wydarzenie, `ProductVariant` = termin, `Order` = booking, `Customer` = uczestnik, `Taxon` = tag, `Channel` = przyszłe miasto/marketplace. Naginanie nazewnictwa to jednorazowy koszt, w zamian dostajemy dojrzały Order/Payment/Promotion/Invoice flow.

**Założenia techniczne (potwierdzone):**
- Backend: **Sylius 2.x** (Symfony 7.x, PHP 8.3+) — pełna dystrybucja, nie tylko Stack.
- API: **API Platform 4** (natywne w Sylius 2.x).
- Frontend: **Bootstrap 5 + Twig Hooks + Symfony UX** (Turbo, Live Components, Stimulus). Server-rendered Twig — Sylius 2.x default.
- Wielojęzyczność: **PL + EN od dnia 1** (Sylius natywny multi-locale).
- Auth: **Email + hasło** (Sylius natywny `Customer` + `AdminUser`, dwa firewalle z pudełka).
- Multi-city: encja **City** + relacja na `Product` (wydarzenie). `Channel` zostawiamy w rezerwie pod marketplace / białe etykiety.
- Daty wydarzeń: **hybryda** — `Product` ma jeden lub wiele `ProductVariant`-ów, każdy = jeden termin.
- Capacity: `ProductVariant.onHand` (Sylius `inventory_tracked`) — z natury blokuje overbooking.
- Płatności: **MVP bez płatności** (free events, total = 0, `PaymentMethod` typu `Free`/Cash/Manual). Faza 2 — dorzucenie P24 lub Stripe pluginu.
- RBAC: granularny system uprawnień **inspirowany Sylius+ Plus RBAC** — własna implementacja na bazie `AdminUser` (Sylius Plus jest płatny, robimy custom).
- Środowisko: dev w Dockerze, kodzenie zaczynamy na świeżym Linuksie — Faza 0 weryfikuje toolchain.

---

## 1. Tech stack — wersje

| Komponent | Wersja | Źródło |
|---|---|---|
| PHP | 8.3+ | Wymóg Sylius 2.x |
| Symfony | 7.2+ | Z Sylius'a |
| **Sylius** | **2.x (sylius/sylius-standard)** | Pełna dystrybucja |
| API Platform | 4.x | W zestawie z Sylius 2.x |
| Doctrine ORM | 3.x | Z Sylius'a |
| PostgreSQL | 16 | Wybór nasz |
| Node | 20 LTS | encore (Sylius 2.x default) |
| Bootstrap | 5.3 | Sylius 2.x default theme |
| Symfony UX | latest | W zestawie |
| Composer | 2.7+ | |
| Docker / Compose | latest | dev env |
| Mailpit | latest | dev SMTP catcher |

**Główne dependencies poza Sylius'em:**
- `symfony/messenger` (async maile, reminders w przyszłości)
- `liip/imagine-bundle` (już w Sylius)
- `vich/uploader-bundle` (już w Sylius)
- `nelmio/cors-bundle` (API)
- Dev: `phpunit/phpunit`, `phpstan/phpstan`, `friendsofphp/php-cs-fixer`, `behat/behat`

**Pluginy / community do rozważenia (poza MVP, pod backlog):**
- `bitbag/wishlist-plugin` (gdyby Interest miał dorastać)
- `sylius/refund-plugin` (gdy płatności)
- `sylius/invoicing-plugin` (faktury)

---

## 2. Mapowanie domeny WATRA → Sylius

| Pojęcie WATRA | Encja Sylius | Customizacja |
|---|---|---|
| Wydarzenie | `Product` | + pola: `city`, `venue`, `isOnline`, `eventStatus`, `eventType` |
| Termin wydarzenia | `ProductVariant` | + pola: `startsAt`, `endsAt`, `venue`; `capacity` = `onHand` |
| Tag/kategoria | `Taxon` | natywne, translatable |
| Booking | `Order` | natywne; `OrderItem` = pojedynczy bilet |
| Uczestnik | `Customer` | natywne |
| Zainteresowany | `Interest` (custom encja) | proste M2M Customer ↔ Product |
| Miasto | `City` (custom encja) | translatable (PL/EN) |
| Miejsce / lokal | `Venue` (custom encja) | translatable, FK do `City` |
| Sklep / marketplace | `Channel` | MVP: jeden channel "watra"; rezerwa na przyszłość |
| Rola admina | `AdministrationRole` (custom) | własny RBAC z `Permission` enum |
| Admin | `AdminUser` | + relacja M2M do `AdministrationRole` |
| Obraz wydarzenia | `ProductImage` | natywne |

**Naming w UI/UX:** w panelu admina i frontendzie nadpisujemy translations (`sylius.ui.products` → "Wydarzenia", `sylius.ui.product_variants` → "Terminy", `sylius.ui.orders` → "Rezerwacje" itd.). Kod mówi językiem Sylius'a, użytkownik widzi język WATRY. Pełna mapa: zobacz [GLOSSARY.md](GLOSSARY.md).

---

## 3. Custom encje (poza Sylius'em)

```
City (translatable: name)
  - id, slug, code (np. "krakow"), country
  - name [PL, EN]

Venue (translatable: name, address)
  - id, slug
  - name [PL, EN], address [PL, EN]
  - city → City
  - lat, lng

Product (Sylius extended)
  - + city → City (ManyToOne)
  - + defaultVenue → Venue (ManyToOne, nullable jeśli online)
  - + isOnline (bool)
  - + eventStatus (enum)
  - + eventType (enum: WORKSHOP, MEETUP, PARTY, CONFERENCE, OTHER)
  - + maxOccurrencesShown (int) — UI hint

ProductVariant (Sylius extended)
  - + startsAt (datetime)
  - + endsAt (datetime)
  - + venue → Venue (nullable, override z Product)
  - capacity = używa natywnego `onHand` + `tracked = true`

Interest
  - id, customer → Customer, product → Product, createdAt
  - UNIQUE (customer, product)

Permission (enum w PHP — nie encja)
  - event:list, event:show, event:create, event:edit, event:delete, event:publish
  - occurrence:create, occurrence:edit, occurrence:delete
  - booking:list, booking:show, booking:cancel, booking:confirm, booking:export
  - customer:list, customer:show, customer:edit, customer:delete
  - city:*, venue:*, tag:* (taxon)
  - admin_user:list, admin_user:create, admin_user:edit, admin_user:delete
  - admin_role:list, admin_role:create, admin_role:edit, admin_role:delete
  - channel:manage, settings:manage

AdministrationRole
  - id, name, code (unique), description
  - permissions: json[] (lista stringów)
  - isSuperAdmin (bool — bypass voter)

AdminUser (Sylius extended)
  - + administrationRoles ⇄ AdministrationRole (M2M)
```

**Decyzje detaliczne:**
- **City to osobna encja, nie Channel.** Channel rezerwujemy na przyszły marketplace / B2B / białe etykiety. MVP = jeden channel `watra`.
- **Capacity = `ProductVariant.onHand`.** Sylius z pudełka pilnuje, że nie sprzedasz więcej niż jest w "stocku". To darmowy check overbookingu.
- **Cykl/seria wydarzeń = Product z N variantami.** Pojedyncze wydarzenie = Product z 1 variantem. Brak osobnej encji `EventSeries`.
- **Interest jest custom** (nie wishlist plugin) — chcemy proste UX i kontrolę nad UI Live Component'em.

---

## 4. RBAC — granularny system uprawnień

**Cel:** super-admin ma wszystko, tworzymy role z dostępem tylko do wybranych sekcji (np. "Marketing": tylko publikacja eventów; "Front Desk": tylko zarządzanie bookingami).

### Komponenty
1. **`App\Security\Permission`** — backed enum z pełną listą permissionów (~30 pozycji) i metodami helperami (`Permission::all()`, `Permission::group()`).
2. **`AdministrationRole`** entity z `permissions: json` + `isSuperAdmin` flag.
3. **`PermissionVoter`** — sprawdza:
   - `isSuperAdmin` w którejkolwiek roli usera → grant
   - Permission w dowolnej roli usera → grant
   - Inaczej deny
4. **Dekoracja:**
   - Sylius Resource Routes → override przez `sylius_resource` config + custom controller z `#[IsGranted('event:edit')]`
   - API Platform Operations → `security: "is_granted('event:list')"`
   - Twig: `{% if is_granted('event:create') %}` na przyciskach
5. **DataFixtures z domyślnymi rolami:**
   - `Super Admin` (isSuperAdmin = true)
   - `Editor` — pełen CRUD katalogu
   - `Marketer` — tylko event:list/show/edit/publish
   - `Front Desk` — booking:* + customer:list/show

### Krytyczne pliki RBAC
- `src/Security/Permission.php` — enum z listą
- `src/Entity/Admin/AdministrationRole.php`
- `src/Entity/Admin/AdminUser.php` (override Sylius'owego)
- `src/Security/Voter/PermissionVoter.php`
- `src/DataFixtures/AdministrationRoleFixtures.php`
- `config/packages/security.yaml` — voter registracja
- `templates/bundles/SyliusAdminBundle/_navigation.html.twig` (override sidebar z permission_check)

Pełna dokumentacja: [RBAC.md](RBAC.md) (powstanie w Fazie 14).

---

## 5. Frontend / dwa "konteksty" w Sylius'ie

| Ścieżka | Theme | Firewall | Audience |
|---|---|---|---|
| `/admin/*` | `SyliusAdminBundle` + override `WatraAdmin` | `admin` | AdminUser |
| `/*` (publiczne + `/account/*`) | `SyliusShopBundle` + override `WatraShop` | `shop` | Customer + anon |
| `/api/v2/shop/*`, `/api/v2/admin/*` | brak | `api_*` | mieszane |

### Strony publiczne (MVP)
- `/` — homepage (custom): hero + listy "Najbliższe", "Polecane", "W Krakowie"
- `/wydarzenia` — lista z filtrami (Symfony UX Live Component)
- `/wydarzenia/{slug}` — szczegóły, opis, mapa, lista terminów, "Zapisz się" + "Zainteresowany"
- `/login`, `/register`, `/reset-password`, `/verify-email` — natywne Sylius
- `/account` — natywny dashboard Sylius + custom sekcje "Moje wydarzenia" i "Zainteresowany"
- `/regulamin`, `/polityka-prywatnosci` — proste strony Twig

### Symfony UX use-cases
- **Live Component**: filtry listy wydarzeń, przycisk "Zainteresowany", formularz bookingu z licznikiem wolnych miejsc per termin
- **Turbo**: nawigacja całego shop frontendu
- **Stimulus controllers**: data picker, mapa Leaflet, lazy loading obrazków, tag selector

### Strony admina (MVP)
- Dashboard (custom widget: liczba eventów, bookingów, nowych userów / 7 dni)
- Sylius-natywne CRUD-y (przemianowane): Products → Wydarzenia, Variants → Terminy, Orders → Rezerwacje, Customers → Uczestnicy, Taxons → Tagi
- Custom CRUD: Cities, Venues, AdminUsers, AdministrationRoles, Interests (read-only)
- **Ukrywamy z menu** sekcje, których nie używamy w MVP: Shipping Methods, Shipping Categories, Tax Categories, Tax Rates, Zones, Exchange Rates

---

## 6. API Platform — Sylius 2.x natywne API

| Resource | Shop API (publiczne) | Shop API (auth) | Admin API (RBAC) |
|---|---|---|---|
| `Product` | GET collection + item | — | POST, PATCH, DELETE |
| `ProductVariant` | embed w Product | — | CRUD |
| `Taxon` | GET collection | — | CRUD |
| `City` (custom) | GET collection | — | CRUD |
| `Venue` (custom) | GET item (publicznie) | — | CRUD |
| `Order` | — | GET (own), POST, DELETE (cancel own) | GET, PATCH state |
| `Customer` (me) | — | GET /me, PATCH /me | GET, PATCH |
| `Interest` (custom) | — | GET (own), POST, DELETE | — |
| `AdminUser`, `AdministrationRole` | — | — | pełen CRUD (RBAC) |

- Dokumentacja: `/api/v2/docs` (Sylius default).
- Filtry: `SearchFilter` (city, taxon, status), `DateFilter` (variants.startsAt[after/before]), `OrderFilter`.
- Auth: **JSON login** z sesyjną cookie (Sylius default); JWT opcjonalnie później.
- CORS: konfiguracja dev/prod w `nelmio_cors`.

---

## 7. Struktura katalogów (target — Sylius 2.x standard)

```
watra/
├── .docker/                 # Dockerfile, nginx conf
├── assets/
│   ├── admin/               # Stimulus + SCSS dla admina
│   └── shop/                # Stimulus + SCSS dla shopu
├── bin/console
├── compose.yaml             # docker compose dev
├── compose.override.yaml    # local overrides (gitignored)
├── config/
│   ├── packages/
│   │   ├── _sylius.yaml
│   │   ├── api_platform.yaml
│   │   ├── doctrine.yaml
│   │   ├── security.yaml
│   │   ├── sylius_grid.yaml
│   │   ├── sylius_resource.yaml
│   │   └── sylius_theme.yaml
│   └── routes/
├── docs/                    # PLAN.md, TASKS.md, BACKLOG.md, GLOSSARY.md, RBAC.md
├── migrations/
├── public/
├── src/
│   ├── Controller/
│   │   ├── Admin/
│   │   ├── Shop/
│   │   └── Api/
│   ├── Entity/
│   │   ├── Admin/           # AdminUser (override), AdministrationRole
│   │   ├── Catalog/         # Product (override), ProductVariant (override), City, Venue
│   │   ├── Customer/        # Customer (override), Interest
│   │   └── Order/
│   ├── Form/
│   ├── Grid/
│   ├── Repository/
│   ├── Security/
│   │   ├── Voter/
│   │   ├── Permission.php
│   │   └── Authenticator/
│   ├── Service/
│   ├── Twig/
│   │   ├── Components/
│   │   └── Hooks/
│   └── DataFixtures/
├── templates/
│   ├── bundles/
│   │   ├── SyliusAdminBundle/
│   │   └── SyliusShopBundle/
│   ├── shop/
│   ├── admin/
│   ├── emails/
│   └── components/
├── themes/
│   ├── WatraShop/
│   └── WatraAdmin/
└── tests/
```

---

## 8. Fazy implementacji

Pełen rozpis na atomowe taski (~85 sztuk) znajduje się w [TASKS.md](TASKS.md). Poniżej skrót faz:

| # | Faza | Czas | Cel |
|---|---|---|---|
| 0 | Weryfikacja środowiska (Linux) | 2-4h | Toolchain działa |
| 1 | Bootstrap Sylius 2.x + Docker | 6-8h | `make up` startuje stack |
| 2 | Lokalizacja PL/EN i naming WATRY | 3-4h | Polski admin |
| 3 | Wyłączenie sekcji niepotrzebnych | 4-5h | Bez Shipping/Tax w UX |
| 4 | Custom encje: City, Venue, AdministrationRole | 8-10h | CRUD w admin |
| 5 | Rozszerzenie Product / ProductVariant / AdminUser | 8-10h | Wydarzenie + termin |
| 6 | RBAC: Permission enum + Voter | 8-10h | Granularne uprawnienia |
| 7 | Konfiguracja "wydarzenia bez płatności" | 5-7h | Free checkout |
| 8 | Custom encja Interest + UI | 4-5h | "Zainteresowany" |
| 9 | Frontend shop: home + lista + szczegóły | 12-16h | Publiczna strona |
| 10 | Email transactional | 4-5h | Maile w brand WATRA |
| 11 | Admin: dashboard + lista uczestników | 5-6h | Statystyki + export |
| 12 | API Platform: konfiguracja resource'ów | 4-5h | Public + auth API |
| 13 | Testy (PHPUnit + Behat) | 6-8h | `make test` zielony |
| 14 | Polish + dokumentacja + verification | 4-6h | MVP gotowy |

**Łączny szacunek MVP:** 75-95h pracy developera (1 osoba, ~2-3 tygodnie pełnowymiarowo).

---

## 9. Krytyczne pliki konfiguracyjne (referencja)

- `config/packages/_sylius.yaml` — locale, channels, resource overrides
- `config/packages/security.yaml` — firewalle (admin/shop/api), voter
- `config/packages/sylius_grid.yaml` — custom gridy dla City/Venue/Role
- `config/packages/sylius_resource.yaml` — resource registracja
- `config/packages/sylius_theme.yaml` — themes
- `config/packages/api_platform.yaml`
- `config/packages/messenger.yaml` — async transport
- `config/services.yaml` — voter, services
- `compose.yaml` — usługi dev
- `.env.local.dist` — szablon zmiennych
- `Makefile` / `Taskfile.yml` — komendy dev

---

## 10. Verification (jak testujemy że MVP działa end-to-end)

**Lokalnie po `make up && make migrate && make fixtures`:**
1. `http://localhost` — homepage WATRY z listą wydarzeń (nie Sylius'owy default).
2. `/admin` przekierowuje na `/admin/login` (firewall działa).
3. Rejestracja na `/register` → email weryfikacyjny w Mailpit (`http://localhost:8025`) → klik linka → konto aktywne.
4. Login → `/wydarzenia` → filtr po mieście "Kraków" → wybór wydarzenia.
5. Klik "Zainteresowany" → Live Component zmienia stan bez reloadu.
6. Klik "Zapisz się" na konkretnym terminie → checkout → submit (skip payment bo total 0) → status `fulfilled` → email potwierdzający.
7. `/account/orders` pokazuje rezerwację jako "Moje wydarzenia".
8. Login admina (`admin@watra.test` z fixtures) na `/admin/login`.
9. Admin widzi "Wydarzenia", "Terminy", "Rezerwacje" (translations zadziałały). Brak "Shipping" / "Tax" w sidebarze.
10. Admin tworzy nowe wydarzenie z PL+EN tytułem, dodaje termin, publikuje → pojawia się w `/wydarzenia`.
11. Login jako rola "Front Desk" → menu pokazuje tylko "Rezerwacje" + "Uczestnicy" — RBAC działa.
12. Próba GET `/admin/events/new` jako "Front Desk" → 403.
13. `GET http://localhost/api/v2/shop/products?city=krakow` zwraca kolekcję JSON-LD.
14. `GET /api/v2/docs` pokazuje OpenAPI.
15. `php bin/console doctrine:schema:validate` → green.
16. `vendor/bin/phpunit && vendor/bin/behat` → wszystko zielone.

---

## 11. Co świadomie odkładamy poza MVP

Pełna lista w [BACKLOG.md](BACKLOG.md). Najważniejsze:

- Płatności (P24 / Stripe), faktury, refundy, promocje
- Przypomnienia (cron + Messenger schedule)
- Push / SMS notifications, recenzje wydarzeń
- Multi-organizer / marketplace, multi-tenant / białe etykiety
- QR ticket / check-in app
- Rozbudowane SEO

---

## 12. Ryzyka i jak je adresujemy

| Ryzyko | Mitigation |
|---|---|
| Naginanie Product/Order na wydarzenia myli developerów | Słowniczek w README + translations w UI + cienkie wrappery `EventService`/`BookingService` |
| Sylius 2.x jest nowszy, niektóre pluginy nie zaktualizowane | MVP nie używa pluginów spoza `sylius-standard`. Pluginy dorzucamy świadomie w backlogu. |
| Override Product entity → ryzyko upgrade pain | Używamy dokumentowanego Sylius extension pointu (`extends BaseProduct` + resource override w yaml), nie hackujemy klas vendor |
| RBAC własny → bug w voter = security issue | Testy jednostkowe `PermissionVoter`, fixture'a z różnymi rolami, manualne smoke testy |
| Capacity race condition (dwóch userów rezerwuje ostatnie miejsce) | Sylius `inventory_tracker` używa transakcji DB; pessimistic lock w `BookingService` |
| Wyłączone moduły Shipping/Tax mogą wracać przy upgradzie Sylius'a | Override w jednym miejscu (Twig hook + security routes), regression test sprawdza brak linków w sidebarze |
