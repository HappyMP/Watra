# WATRA — taski MVP

Szczegółowy rozpis prac z [PLAN.md](PLAN.md) sekcja 8. Każda faza ma listę atomowych tasków (1-4h każdy), oszacowanie i Definition of Done. Numeracja `X.Y` = task `Y` w fazie `X`. Łącznie ~90 tasków, **80-100h pracy** (~2-3 tygodnie full-time). Dla developera bez wcześniejszego doświadczenia z Sylius'em: dodać +20-30% na naukę konwencji.

Odhaczamy taski sukcesywnie w kolejnych sesjach. Status fazy: `[ ]` w toku / `[x]` zakończona.

---

## [x] Faza 0 — Weryfikacja środowiska (świeży Linux) — 2-4h
**Cel:** wykryć co już zainstalowane na targetowym komputerze, doinstalować brakujące.

- [x] 0.1 — Stworzyć skrypt `bin/check-env.sh` sprawdzający wersje: PHP 8.3+, Composer 2.7+, Symfony CLI, Node 20+, Docker, Docker Compose
- [x] 0.2 — Udokumentować w `README.md` (sekcja `## Wymagania`) jak doinstalować każde z narzędzi na Ubuntu/Debian (oficjalne repos)
- [x] 0.3 — Uruchomić skrypt na targetowym Linuksie, doinstalować brakujące paczki
- [x] 0.4 — Zweryfikować Docker działa: `docker run hello-world` + `docker compose version`
- [x] 0.5 — Zweryfikować PHP ma rozszerzenia: `pdo_pgsql`, `intl`, `gd`, `zip`, `opcache`, `mbstring`, `xml`

**DoD:** wszystkie wersje przechodzą check-env.sh, dev może uruchomić Docker bez sudo.

> **Uwagi po realizacji:**
> - `pdo_pgsql` na hoście: PPA ondrej/php tymczasowo niedostępne — rozszerzenie zainstalowane zostanie przy kolejnym dostępie do PPA lub bezpośrednio przez Dockerfile (w Docker-first workflow host PHP nie potrzebuje pdo_pgsql)
> - Docker bez sudo: wymaga wylogowania i zalogowania po `usermod -aG docker $USER` — daemon działa poprawnie (sudo docker run hello-world ✓)

---

## [x] Faza 1 — Bootstrap Sylius 2.x + Docker — 7-10h
**Cel:** działający szkielet Sylius 2.x w kontenerze, panel admina i shop frontend dostępne lokalnie.

- [x] 1.0 — **Plugin compat gate:** Sylius Standard 2.2.0 dostępny, MVP nie używa zewnętrznych pluginów → PASS
- [x] 1.1 — `composer create-project sylius/sylius-standard:^2.0` → v2.2.0
- [x] 1.2 — Branch `feat/bootstrap-sylius` → zmergowany na `main`
- [x] 1.3 — `.docker/php/Dockerfile` (PHP 8.3-fpm-alpine + pdo_pgsql, intl, gd, zip, opcache, mbstring, xml, bcmath, sockets, apcu)
- [x] 1.4 — `.docker/nginx/default.conf` (proxy do php-fpm, root `public/`)
- [x] 1.5 — `compose.yaml` z usługami: `php`, `nginx`, `postgres:16`, `mailpit`, `redis`, `nodejs`
- [x] 1.6 — `.env` (postgres DSN, mailpit MAILER_DSN) + `.env.local.dist`
- [x] 1.7 — `Makefile` z taskami: `up`, `down`, `bash`, `install`, `migrate`, `fixtures`, `test`, `cs-fix`, `phpstan`
- [x] 1.8 — `make up` + composer install → wszystkie 5 kontenerów UP
- [x] 1.9 — `make migrate && make fixtures` (Sylius default fixtures) → OK
- [x] 1.10 — Smoke test: `/admin/login` 200, `/` 302→200, `/api/v2/docs` 200 ✓
- [x] 1.11 — Discovery konwencji 2.x → udokumentowane w `docs/SYLIUS_OVERRIDES.md`
  - Menu admina: KNP Menu via event `sylius.menu.admin.main` (nie Twig Hooks!)
  - Sidebar hook: `sylius_admin.common.component.sidebar`
  - Ukrywanie menu: EventListener na `sylius.menu.admin.main`
- [x] 1.12 — Commit "feat(phase-1): bootstrap Sylius 2.x with PostgreSQL Docker stack"

> **Uwagi po realizacji:**
> - Dockerfile wymaga `linux-headers`, `autoconf`, `g++`, `make` dla rozszerzenia `sockets` i APCu
> - Assety npm: przy `make up` nodejs container wymaga `npm install` przed `npm run build` — Makefile ma target `build-assets`

**DoD:** `make up` od zera startuje pełen stack, panel admina działa pod `http://localhost/admin`.

---

## [x] Faza 2 — Lokalizacja PL/EN i naming WATRY — 3-4h
**Cel:** PL jako default, EN jako drugi locale, panel admina i shop pokazują "Wydarzenia" zamiast "Products".

- [x] 2.1 — `config/packages/_sylius.yaml`: `default_locale: pl`, `available_locales: [pl, en]`, `currency: PLN`
- [x] 2.2 — Skonfigurować channel `watra` (hostname `localhost`, locale `pl_PL`, currency `PLN`) — fixture suite `watra` w `config/packages/sylius_fixtures.yaml`
- [x] 2.3 — Stworzyć `translations/messages.pl.yaml` z override translation keys → "Wydarzenia", "Terminy", "Rezerwacje", "Uczestnicy", "Tagi" + singular i compound keys
- [x] 2.4 — Stworzyć `translations/messages.en.yaml` z analogicznymi override (Events, Sessions, Bookings, Attendees, Tags)
- [x] 2.5 — Twig templates breadcrumbs / page titles — sprawdzone, brak hardkodów (tylko asset loadery)
- [x] 2.6 — Smoke test: admin panel `lang="pl"`, `sylius.ui.products`→"Wydarzenia" potwierdzone przez `debug:translation`

**DoD:** menu admina i shop są w PL (lub EN po przełączeniu), kluczowe terminy zmapowane.

> **Uwagi po realizacji:**
> - Sylius używa pełnych kodów BCP 47 (`pl_PL`), nie krótkich (`pl`) — channel fixture musi używać `pl_PL`
> - `GeographicalFixture` ma `defaultValue(Countries::getNames())` — zawsze tworzy wszystkie kraje jeśli `countries:` nie ustawione na `[]`; PL zone zostaje do Fazy 3 (task 3.4)
> - `make fixtures` uruchamia teraz dwa suite'y: `default` (Sylius dev data) + `watra` (channel WATRA)
> - Translation keys dla Sylius admin menu i UI są w domenie `messages` (nie `sylius`)

---

## [ ] Faza 3 — Wyłączenie sekcji niepotrzebnych w MVP — 4-5h
**Cel:** ukryć Shipping/Tax/Zones/Exchange Rates z UX, pozostawić jako działający backend.

- [ ] 3.1 — Override sidebara — użyj rzeczywistych nazw hooków zinwentaryzowanych w task 1.11 (Sylius 2.x preferuje Twig Hooks, ale fallback to override `templates/bundles/SyliusAdminBundle/_menu.html.twig`). Usunąć linki: Shipping Methods, Shipping Categories, Tax Categories, Tax Rates, Zones, Exchange Rates
- [ ] 3.2 — Sylius'owe `_sylius.yaml`: stworzyć domyślny ShippingMethod `no_shipping` (calculator: flat_rate 0)
- [ ] 3.3 — Stworzyć domyślny PaymentMethod `free` (gateway: offline) — fixture
- [ ] 3.4 — Stworzyć domyślną Zone (Polska) i TaxCategory `default` z 0% — fixture
- [ ] 3.5 — Skonfigurować channel: domyślny shipping `no_shipping`, payment `free`, tax_zone `pl`
- [ ] 3.6 — Smoke test: w admin sidebar nie ma Shipping/Tax linków, ale checkout działa

**DoD:** admin widzi tylko sekcje istotne dla wydarzeń, pełen Sylius shipping/tax istnieje "pod spodem".

---

## [ ] Faza 4 — Custom encje: City, Venue, AdministrationRole — 8-10h
**Cel:** mieć encje miast, miejsc i ról admin'owych z migracjami, gridami i CRUD-em w admin'ie.

- [ ] 4.1 — Stworzyć `App\Entity\Catalog\City` + `CityTranslation` (translatable: `name`)
- [ ] 4.2 — Stworzyć `App\Entity\Catalog\Venue` + `VenueTranslation` (translatable: `name`, `address`) z FK do `City`
- [ ] 4.3 — Stworzyć `App\Entity\Admin\AdministrationRole` (z `permissions: json`, `isSuperAdmin: bool`)
- [ ] 4.4 — Zarejestrować jako Sylius Resources w `_sylius.yaml`
- [ ] 4.5 — Migracja `php bin/console make:migration && doctrine:migrations:migrate`
- [ ] 4.6 — Stworzyć `App\Form\Type\CityType`, `VenueType`, `AdministrationRoleType`
- [ ] 4.7 — Stworzyć Sylius Grid configurations dla City, Venue, AdministrationRole
- [ ] 4.8 — Routing admin CRUD: `/admin/cities`, `/admin/venues`, `/admin/administration-roles`
- [ ] 4.9 — DataFixtures: 3 cities (Kraków, Warszawa, Wrocław), 4 venues (po 1-2 na miasto)
- [ ] 4.10 — Twig hook dodający linki w sidebarze admin: "Miasta", "Lokalizacje", "Role administracji"
- [ ] 4.11 — Smoke test: admin tworzy nowe miasto, edytuje, usuwa

**DoD:** wszystkie 3 encje mają działający CRUD w panelu admina, fixture-y się ładują.

---

## [ ] Faza 5 — Rozszerzenie Sylius Product / ProductVariant / AdminUser — 8-10h
**Cel:** Product reprezentuje wydarzenie, Variant reprezentuje termin, AdminUser ma role administracyjne.

- [ ] 5.1 — Stworzyć `App\Entity\Catalog\Product extends Sylius\Component\Core\Model\Product` z polami: `city → City`, `defaultVenue → Venue`, `isOnline: bool`, `eventStatus: enum`, `eventType: enum`
- [ ] 5.2 — Stworzyć `App\Entity\Catalog\ProductVariant extends BaseProductVariant` z polami: `startsAt`, `endsAt`, `venue → Venue` (nullable, override)
- [ ] 5.3 — Stworzyć `App\Entity\Admin\AdminUser extends BaseAdminUser` z relacją M2M do `AdministrationRole`
- [ ] 5.3a — Stworzyć `App\Entity\Customer\Customer extends BaseCustomer` (pusty extends, brak nowych pól w MVP). Powód: późniejszy override = migracja FK we wszystkich tabelach referencujących `sylius_customer`
- [ ] 5.4 — Override w `_sylius.yaml`: `sylius_product.product` na nasz Product, `sylius_admin_user` na nasz AdminUser, `sylius_customer.customer` na nasz Customer
- [ ] 5.5 — Stworzyć enumy `EventStatus` (DRAFT/PUBLISHED/CANCELLED/COMPLETED) i `EventType` (WORKSHOP/MEETUP/PARTY/CONFERENCE/OTHER)
- [ ] 5.6 — Migracja schema (dodanie nowych kolumn)
- [ ] 5.7 — `App\Form\Extension\ProductTypeExtension` dodający pola wydarzeniowe do form'a Product w admin'ie
- [ ] 5.8 — `App\Form\Extension\ProductVariantTypeExtension` dodający `startsAt`, `endsAt`, `venue` do variant'a
- [ ] 5.9 — `App\Form\Extension\AdminUserTypeExtension` dodający multi-select ról admin
- [ ] 5.10 — Walidacja: ProductVariant `startsAt < endsAt`, `startsAt > now()` przy publikacji
- [ ] 5.11 — DataFixtures: 5-10 example products (wydarzeń), każdy z 1-3 variantami (terminami)
- [ ] 5.12 — Smoke test: admin tworzy wydarzenie, dodaje termin (variant), publikuje

**DoD:** Product = wydarzenie z minimum 1 terminem, w admin form widać polskie etykiety, fixture ładuje testowe dane.

---

## [ ] Faza 6 — RBAC: Permission enum + Voter + integracja — 8-10h
**Cel:** każdy admin route i każda operacja API jest chroniona granularnym permissionem.

- [ ] 6.1 — Stworzyć `App\Security\Permission` (PHP backed enum) z pełną listą ~30 permissionów
- [ ] 6.2 — Helper'y w enum'ie: `Permission::all()`, `Permission::group(string $resource): array`
- [ ] 6.3 — Stworzyć `App\Security\Voter\PermissionVoter` (extends Voter)
- [ ] 6.4 — Rejestracja Voter'a w `services.yaml` (autowire) + tag `security.voter`
- [ ] 6.5 — Rozszerzyć formularz `AdministrationRoleType` o multi-checkbox listę permissionów (pogrupowanych po zasobie)
- [ ] 6.6 — DataFixtures `AdministrationRoleFixtures`: Super Admin (isSuperAdmin=true), Editor, Marketer, Front Desk
- [ ] 6.7 — Stworzyć super-admin usera w fixtures: `admin@watra.test` + assigned Super Admin role
- [ ] 6.8 — Dekoracja admin controllerów: `#[IsGranted(Permission::EVENT_CREATE->value)]` (na każdym route admin'a)
- [ ] 6.9 — Twig macro `{% if has_permission('event:create') %}` używana w sidebarze admin'a
- [ ] 6.10 — Test integracyjny: zaloguj jako Front Desk → próba GET `/admin/products/new` → 403; próba GET `/admin/orders` → 200
- [ ] 6.10a — Test functional render Twig: jako Front Desk renderuj sidebar → asercja że link "Wydarzenia → Nowe" NIE jest w HTML (potwierdza że `is_granted('event:create')` w Twigu działa, nie tylko w controllerze)
- [ ] 6.11 — API Platform operations: `security: "is_granted('event:create')"` na admin operations
- [ ] 6.12 — Zweryfikować `PermissionVoter::supports($attribute)` rozpoznaje wszystkie permission stringi (`event:*`, `booking:*`, ...) — najlepiej przez prefix matching listy z enuma

**DoD:** super admin może wszystko, Front Desk widzi tylko Rezerwacje + Uczestnicy w menu, próba dostępu do innych route'ów daje 403.

---

## [ ] Faza 7 — Konfiguracja "wydarzenia bez płatności" — 6-8h
**Cel:** checkout dla wydarzenia darmowego (total = 0) działa bez wpisywania danych płatności i adresu wysyłki.

- [ ] 7.1 — `PaymentMethod` `free_payment` z gateway'em Payum `offline` — fixture (weryfikacja z fazy 3)
- [ ] 7.2 — Custom `OrderProcessor` (Sylius Composite) który auto-marks payment jako `completed` gdy `order.total = 0`
- [ ] 7.3 — Override `checkout/select_shipping.html.twig` — gdy wszystkie `OrderItem` to wydarzenia, ukryj sekcję shipping address i auto-skip
- [ ] 7.4 — Override `checkout/select_payment.html.twig` — gdy total = 0, auto-wybierz `free_payment` i przejdź dalej
- [ ] 7.5 — Override `checkout/complete.html.twig` — komunikat "Twoja rezerwacja została potwierdzona"
- [ ] 7.6 — Smoke test E2E: zaloguj jako customer → wybierz wydarzenie → "Zapisz się" → checkout → potwierdzenie
- [ ] 7.7 — **Race condition test:** integration test (PHPUnit functional) — dwóch customer'ów równolegle book'uje ostatni slot. Oczekiwanie: jeden dostaje `fulfilled`, drugi 409/422 z message "Brak miejsc". Zweryfikować że `BookingService` używa `LockMode::PESSIMISTIC_WRITE` na `ProductVariant.onHand`

**DoD:** customer kończy booking w 3 klikach, Order w admin pokazuje status `fulfilled`, payment `completed`, race condition test zielony.

---

## [ ] Faza 8 — Custom encja Interest + frontend "Zainteresowany" — 4-5h
**Cel:** zalogowany customer może oznaczyć wydarzenie jako "zainteresowany" bez bookingu.

- [ ] 8.1 — Stworzyć `App\Entity\Customer\Interest` (customer → Customer, product → Product, createdAt) z UNIQUE (customer, product)
- [ ] 8.2 — Migracja
- [ ] 8.3 — Repository `InterestRepository::findByCustomer()`, `existsForCustomerAndProduct()`
- [ ] 8.4 — Service `App\Service\InterestService::toggle(Customer $c, Product $p): bool`
- [ ] 8.5 — Live Component `InterestButton` (Symfony UX): ikonka serca, click toggle, optimistic UI
- [ ] 8.6 — Wstawienie komponentu w `product_show.html.twig` (Twig Hook na `sylius_shop.product.show.main`)
- [ ] 8.7 — Sekcja `/account/interests` — lista wydarzeń customer'a oznaczonych jako interest
- [ ] 8.8 — Smoke test: gość klika serce → redirect na login; zalogowany → toggle działa

**DoD:** customer może dodać/usunąć interest, widzi listę swoich w `/account/interests`.

---

## [ ] Faza 9 — Frontend shop: homepage, lista wydarzeń, strona wydarzenia — 12-16h
**Cel:** publiczny frontend WATRY z kartami wydarzeń, filtrami i szczegółami.

- [ ] 9.1 — Theme `WatraShop` w `themes/WatraShop/` z plikami nadpisującymi `SyliusShopBundle`
- [ ] 9.2 — `assets/shop/` — Stimulus controllers: `datepicker_controller.js`, `map_controller.js`, `tag_selector_controller.js`
- [ ] 9.3 — Custom `HomeController` w `src/Controller/Shop/` — pobiera 3 listy: nadchodzące, polecane, w Krakowie
- [ ] 9.4 — Template `templates/shop/home/index.html.twig` z hero + 3 sekcje kart wydarzeń
- [ ] 9.5 — Komponent Twig `EventCard` (re-używalny) — zdjęcie, tytuł, najbliższy termin, miasto, tagi, "Zainteresowany" toggle
- [ ] 9.6 — Lista wydarzeń `/wydarzenia` — Sylius'owy index taxon-product LUB custom controller
- [ ] 9.7 — Live Component `EventListFilters`: filtry city (select), tags (multi-checkbox), date range (from/to), search input z debounce 300ms
- [ ] 9.8 — Override `product/show.html.twig`: nazwa, opis, lista terminów (variantów) z capacity i przyciskiem "Zapisz się" per termin, mapa Leaflet, "Zainteresowany"
- [ ] 9.9 — Strona "Moje wydarzenia" w `/account/orders` — filter na orders gdzie items są wydarzeniami, ładny widok karty
- [ ] 9.10 — Sekcja `/account/interests` (z fazy 8.7 — integracja w nawigacji konta)
- [ ] 9.11 — Footer + nav: linki do `/regulamin`, `/polityka-prywatnosci` (proste statyczne Twig)
- [ ] 9.12 — Responsive smoke test: 375px, 768px, 1280px

**DoD:** publiczny frontend działa end-to-end, można przeglądać i filtrować wydarzenia, klikać "Zapisz się" i "Zainteresowany".

---

## [ ] Faza 10 — Email transactional — 4-5h
**Cel:** automatyczne maile dla rejestracji, weryfikacji, resetu, bookingu.

- [ ] 10.1 — Mailer skonfigurowany na Mailpit (`MAILER_DSN=smtp://mailpit:1025`)
- [ ] 10.2 — Override Sylius'owych templatów email pod brand WATRA: rejestracja, weryfikacja, reset hasła, order_confirmation
- [ ] 10.3 — Logo + kolory + footer WATRY w wszystkich templatach
- [ ] 10.4 — Custom email "rezerwacja potwierdzona" z linkiem do `/account/orders/{id}`
- [ ] 10.5 — Custom email "rezerwacja anulowana" — wysyłany gdy admin zmienia status booking na cancelled
- [ ] 10.6 — Messenger transport `doctrine` dla async wysyłki (transport: `async`)
- [ ] 10.7 — Worker `php bin/console messenger:consume async` udokumentowany w README
- [ ] 10.8 — Smoke test: rejestracja → mail w Mailpit; reset hasła → mail w Mailpit; booking → mail w Mailpit

**DoD:** wszystkie maile w Mailpit, brand WATRY widoczny, async działa.

---

## [ ] Faza 11 — Admin: dashboard widget + lista uczestników wydarzenia — 5-6h
**Cel:** admin widzi statystyki i kto się zapisał na konkretne wydarzenie.

- [ ] 11.1 — Custom `DashboardController` w `src/Controller/Admin/` — 4 metryki: liczba published events, bookings 7d, new customers 7d, occupancy %
- [ ] 11.2 — Repository methods do ww. metryk (QueryBuilder, group by date)
- [ ] 11.3 — Override `admin/dashboard/index.html.twig` z 4 kart-widget'ami i wykresem prostym (Chart.js przez Stimulus)
- [ ] 11.4 — Custom action `/admin/products/{id}/attendees` — lista wszystkich Customer'ów którzy mają booking na dany Product
- [ ] 11.5 — Eksport CSV listy uczestników (Permission `booking:export`)
- [ ] 11.6 — Twig hook na `product_show` w admin'ie dodający link "Lista uczestników"
- [ ] 11.7 — Smoke test: admin widzi metryki, klika wydarzenie, widzi listę osób, eksportuje CSV

**DoD:** dashboard pokazuje 4 metryki, każde wydarzenie ma podstronę z listą uczestników.

---

## [ ] Faza 12 — API Platform: konfiguracja resource'ów i security — 4-5h
**Cel:** publiczne czytanie wydarzeń przez API + uwierzytelnione operacje na bookingach.

- [ ] 12.1 — Sprawdzić Sylius 2.x default API exposure: `bin/console debug:router | grep api_platform` — udokumentować w `docs/SYLIUS_OVERRIDES.md` listę preconfigured operations dla Product/Order/Customer (decyzja: per-resource override vs global `api_platform.security` policy)
- [ ] 12.2 — Skonfigurować nasze custom resources: City, Venue, Interest jako ApiResource z odpowiednimi operacjami
- [ ] 12.3 — Filtry na `Product`: `SearchFilter` po city.code, taxon.code, eventStatus; `DateFilter` po variants.startsAt
- [ ] 12.4 — Security expressions: shop API publiczne dla GET produktów, autoryzowane dla bookings; admin API z `is_granted('event:edit')` itp.
- [ ] 12.5 — CORS w `nelmio_cors.yaml`: dev `*`, prod whitelist
- [ ] 12.6 — Smoke test: `curl /api/v2/shop/products?city=krakow` zwraca JSON-LD, `/api/v2/docs` pokazuje OpenAPI

**DoD:** publiczny GET działa bez auth, wszystkie write'y wymagają auth, RBAC checks działają.

---

## [ ] Faza 13 — Testy — 6-8h
**Cel:** kluczowe ścieżki pokryte automatami.

- [ ] 13.1 — PHPUnit unit: `PermissionVoterTest` (super-admin grant, role-based grant, deny)
- [ ] 13.2 — PHPUnit unit: `InterestServiceTest::testToggle` (add, remove, idempotency)
- [ ] 13.3 — PHPUnit functional: `BookingFlowTest` (customer dodaje wydarzenie do koszyka, checkout, status fulfilled)
- [ ] 13.4 — PHPUnit functional: `RbacTest` (Front Desk próbuje admin/products/new → 403)
- [ ] 13.5 — Behat scenariusz: rejestracja → weryfikacja email → login → booking
- [ ] 13.6 — Behat scenariusz: admin tworzy wydarzenie + termin + publikuje → widoczne w shop
- [ ] 13.7 — `make test` uruchamia wszystko, CI script `bin/ci.sh` (phpstan + cs-fix --dry-run + phpunit + behat)

**DoD:** `make test` zielony, smoke test ścieżek z PLAN.md sekcja 10 zautomatyzowane.

---

## [ ] Faza 14 — Polish + dokumentacja + verification — 4-6h
**Cel:** projekt gotowy do prezentacji i continued development.

- [ ] 14.1 — Realistyczne fixture-y: 10+ wydarzeń (różne miasta, tagi, daty), 3-5 testowych userów
- [ ] 14.2 — `README.md`: opis projektu, wymagania, `make up` quickstart, link do GLOSSARY.md
- [ ] 14.3 — Aktualizacja `BACKLOG.md`
- [ ] 14.4 — `docs/RBAC.md` — dokumentacja systemu uprawnień (jak dodać permission, jak stworzyć rolę)
- [ ] 14.5 — `docs/SYLIUS_OVERRIDES.md` — lista co i gdzie nadpisaliśmy w Sylius'ie (przyda się przy upgrade'ach)
- [ ] 14.6 — `composer audit` zielony, `phpstan analyze` lvl 7 zielony, `php-cs-fixer fix --dry-run` zielony
- [ ] 14.7 — Manual smoke test wszystkich 16 punktów z PLAN.md sekcja 10 (Verification)
- [ ] 14.8 — Tag git `mvp-1.0`

**DoD:** projekt można sklonować, `make up`, mieć działające MVP w 5 minut. README odpowiada na wszystkie pytania nowego developera.
