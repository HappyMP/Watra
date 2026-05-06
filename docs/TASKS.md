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

## [x] Faza 3 — Wyłączenie sekcji niepotrzebnych w MVP — 4-5h
**Cel:** ukryć Shipping/Tax/Zones/Exchange Rates z UX, pozostawić jako działający backend.

- [x] 3.1 — Override sidebara — EventListener na `sylius.menu.admin.main` (KNP Menu), `removeChild()` dla: shipping_methods, shipping_categories, tax_categories, tax_rates, zones, exchange_rates, payment_methods, promotions, catalog_promotions, official_support
- [x] 3.2 — ShippingMethod `no_shipping` (flat_rate 0) — fixture w suite `watra`
- [x] 3.3 — PaymentMethod `free_payment` (offline gateway) — fixture w suite `watra`
- [x] 3.4 — Custom `PolandZoneFixture` (idempotentny) tworzy Zone `PL` + TaxCategory `default` + TaxRate `pl_0` (0%) — fixture w suite `watra`
- [x] 3.5 — Channel WATRA z `default_tax_zone: PL` — zaktualizowany fixture
- [x] 3.6 — Smoke test: curl do `/admin/` po loginie — brak linków shipping/tax/zone/exchange/payment-methods w HTML

**DoD:** admin widzi tylko sekcje istotne dla wydarzeń, pełen Sylius shipping/tax istnieje "pod spodem".

> **Uwagi po realizacji:**
> - `GeographicalFixture` waliduje kraje w pamięci (nie w DB) → nie nadaje się do additive suite; zastąpiony przez `App\Fixture\PolandZoneFixture` z idempotentnym check'em
> - W watra suite kolejność ma znaczenie: `channel` musi być PRZED `shipping_method` i `payment_method` (te fixtures szukają kanału po kodzie)
> - `make fixtures` (default + watra) działa jako pełen reset — watra suite NIE jest idempotentny dla built-in fixtures (duplicate key na ponownym uruchomieniu bez purge)

---

## [x] Faza 4 — Custom encje: City, Venue, AdministrationRole — 8-10h
**Cel:** mieć encje miast, miejsc i ról admin'owych z migracjami, gridami i CRUD-em w admin'ie.

- [x] 4.1 — `App\Entity\Catalog\City` + `CityTranslation` (translatable: `name`), tabele: `watra_city`, `watra_city_translation`
- [x] 4.2 — `App\Entity\Catalog\Venue` + `VenueTranslation` (translatable: `name`, `address`) z FK do `City`
- [x] 4.3 — `App\Entity\Admin\AdministrationRole` (`permissions: json`, `isSuperAdmin: bool`)
- [x] 4.4 — Zarejestrowane jako Sylius Resources w `_sylius.yaml` pod `sylius_resource.resources`
- [x] 4.5 — Migracja `Version20260503122213` — 18 sql queries, tabele watra_city/venue/translation/role
- [x] 4.6 — `CityType`, `CityTranslationType`, `VenueType`, `VenueTranslationType`, `AdministrationRoleType`
- [x] 4.7 — Grid configs w `config/packages/sylius_grid.yaml`: app_admin_city, app_admin_venue, app_admin_administration_role
- [x] 4.8 — Routing w `config/routes/app_admin.yaml`: /admin/cities/, /admin/venues/, /admin/administration-roles/
- [x] 4.9 — `WatraCatalogFixture`: Kraków/Warszawa/Wrocław + 4 venues (ICE, Tauron, Kopernik, Hala Stulecia)
- [x] 4.10 — `AdminMenuListener::addWatraMenuItems()` dodaje sekcję WATRA z linkami do sidebara
- [x] 4.11 — Smoke test: `router:match /admin/cities/` → app_admin_city_index ✓, DB: 3 cities + 4 venues ✓

**DoD:** wszystkie 3 encje mają działający CRUD w panelu admina, fixture-y się ładują.

> **Uwagi po realizacji:**
> - Translation classes muszą implementować `ResourceInterface` (nie tylko `TranslationInterface`) — wymóg Sylius 2.x ResourceBundle
> - Form types dla AbstractResourceType wymagają ręcznej rejestracji w services.yaml (z `arguments: [ModelClass, [sylius]]`)
> - `watra_catalog` fixture jest OSTATNI w suite — wymaga żeby channel WATRA istniał

---

## [x] Faza 5 — Rozszerzenie Sylius Product / ProductVariant / AdminUser — 8-10h
**Cel:** Product reprezentuje wydarzenie, Variant reprezentuje termin, AdminUser ma role administracyjne.

- [x] 5.1 — Stworzyć `App\Entity\Catalog\Product extends Sylius\Component\Core\Model\Product` z polami: `city → City`, `defaultVenue → Venue`, `isOnline: bool`, `eventStatus: enum`, `eventType: enum`
- [x] 5.2 — Stworzyć `App\Entity\Catalog\ProductVariant extends BaseProductVariant` z polami: `startsAt`, `endsAt`, `venue → Venue` (nullable, override)
- [x] 5.3 — Stworzyć `App\Entity\Admin\AdminUser extends BaseAdminUser` z relacją M2M do `AdministrationRole`
- [x] 5.3a — Stworzyć `App\Entity\Customer\Customer extends BaseCustomer` (pusty extends, brak nowych pól w MVP). Powód: późniejszy override = migracja FK we wszystkich tabelach referencujących `sylius_customer`
- [x] 5.4 — Override w `_sylius.yaml`: `sylius_product.product` na nasz Product, `sylius_admin_user` na nasz AdminUser, `sylius_customer.customer` na nasz Customer
- [x] 5.5 — Stworzyć enumy `EventStatus` (DRAFT/PUBLISHED/CANCELLED/COMPLETED) i `EventType` (WORKSHOP/MEETUP/PARTY/CONFERENCE/OTHER)
- [x] 5.6 — Migracja schema (dodanie nowych kolumn)
- [x] 5.7 — `App\Form\Extension\ProductTypeExtension` dodający pola wydarzeniowe do form'a Product w admin'ie
- [x] 5.8 — `App\Form\Extension\ProductVariantTypeExtension` dodający `startsAt`, `endsAt`, `venue` do variant'a
- [x] 5.9 — `App\Form\Extension\AdminUserTypeExtension` dodający multi-select ról admin
- [x] 5.10 — Walidacja: ProductVariant `startsAt < endsAt`, `startsAt > now()` przy publikacji
- [x] 5.11 — DataFixtures: 5-10 example products (wydarzeń), każdy z 1-3 variantami (terminami)
- [x] 5.12 — Smoke test: admin tworzy wydarzenie, dodaje termin (variant), publikuje

**DoD:** Product = wydarzenie z minimum 1 terminem, w admin form widać polskie etykiety, fixture ładuje testowe dane.

> **Uwagi po realizacji:**
> - 5.3a i 5.4 były już zrealizowane we wcześniejszych fazach
> - `city` w Product: nullable w ORM (nullable: true), required na poziomie formularza
> - Form extensions (AbstractTypeExtension) auto-rejestrowane przez `autoconfigure: true` — brak wpisów w services.yaml
> - Sylius AdminUser form type: `Sylius\Bundle\AdminBundle\Form\Type\AdminUserType`
> - City lookup w WatraEventFixture: przez `buildCityMap()` (po nazwie translacji pl_PL)
> - Kolumny camelCase (eventType, isOnline, startsAt) — PostgreSQL przechowuje lowercase, Doctrine quotuje automatycznie

---

## [x] Faza 6 — RBAC: Permission enum + Voter + integracja — 8-10h
**Cel:** każdy admin route i każda operacja API jest chroniona granularnym permissionem.

- [x] 6.1 — Stworzyć `App\Security\Permission` (PHP backed enum) z pełną listą ~30 permissionów
- [x] 6.2 — Helper'y w enum'ie: `Permission::all()`, `Permission::group(string $resource): array`
- [x] 6.3 — Stworzyć `App\Security\Voter\PermissionVoter` (extends Voter)
- [x] 6.4 — Rejestracja Voter'a w `services.yaml` (autowire) + tag `security.voter`
- [x] 6.5 — Rozszerzyć formularz `AdministrationRoleType` o multi-checkbox listę permissionów (pogrupowanych po zasobie)
- [x] 6.6 — DataFixtures `AdministrationRoleFixtures`: Super Admin (isSuperAdmin=true), Editor, Marketer, Front Desk
- [x] 6.7 — Stworzyć super-admin usera w fixtures: `admin@watra.test` + assigned Super Admin role
- [x] 6.8 — Dekoracja admin controllerów: `#[IsGranted(Permission::EVENT_CREATE->value)]` (na każdym route admin'a)
- [x] 6.9 — Twig macro `{% if has_permission('event:create') %}` używana w sidebarze admin'a
- [x] 6.10 — Test integracyjny: zaloguj jako Front Desk → próba GET `/admin/products/new` → 403; próba GET `/admin/orders` → 200
- [x] 6.10a — Test functional render Twig: jako Front Desk renderuj sidebar → asercja że link "Wydarzenia → Nowe" NIE jest w HTML (potwierdza że `is_granted('event:create')` w Twigu działa, nie tylko w controllerze)
- [x] 6.11 — API Platform operations: `security: "is_granted('event:create')"` na admin operations
- [x] 6.12 — Zweryfikować `PermissionVoter::supports($attribute)` rozpoznaje wszystkie permission stringi (`event:*`, `booking:*`, ...) — najlepiej przez prefix matching listy z enuma

**DoD:** super admin może wszystko, Front Desk widzi tylko Rezerwacje + Uczestnicy w menu, próba dostępu do innych route'ów daje 403.

> **Uwagi po realizacji:**
> - PermissionVoter: ABSTAIN dla adminów bez ról (backwards compat z istniejącym admin@watra.pl), DENY dla adminów z rolami bez wymaganego permission
> - Route protection: `kernel.request` EventSubscriber z `AdminRoutePermissionMap` (nie access_control) — mapuje route names → Permission enum values
> - PermissionVoter auto-tagowany przez `autoconfigure: true` (extends Voter → security.voter tag)
> - Twig `has_permission()` — `PermissionExtension` auto-wired przez autoconfigure
> - AdminMenuListener wymaga `AuthorizationCheckerInterface` w konstruktorze — auto-wired
> - Testy funkcjonalne: `loginUser($user, 'admin')` zamiast submitowania formularza logowania
> - Test database (`sylius_test`): trzeba stworzyć raz przez `doctrine:database:create --env=test` + `doctrine:migrations:migrate --env=test`
> - APP_ENV w testach: bootstrap.php wymaga jawnego ustawienia `$_SERVER['APP_ENV'] = 'test'` (Docker container ma `APP_ENV=dev`)
> - API Platform: security przez `access_control` w security.yaml (MVP approach — nie przez `#[ApiResource(security)]`)

---

## [x] Faza 7 — Konfiguracja "wydarzenia bez płatności" — 6-8h
**Cel:** checkout dla wydarzenia darmowego (total = 0) działa bez wpisywania danych płatności i adresu wysyłki.

- [x] 7.1 — `free_payment` fixture (weryfikacja z fazy 3) + `shippingRequired=false` na wariantach + flagi `skipping_shipping/payment_step_allowed` na kanale
- [x] 7.2 — `FreeOrderPaymentListener` (priority 250) na `workflow.sylius_order_checkout.completed.complete` — auto-completes $0 payment via SM
- [x] 7.3 — Shipping step skip: natywny Sylius via `shippingRequired=false` + `skipping_shipping_step_allowed: true` (brak override szablonu)
- [x] 7.4 — Payment step skip: natywny Sylius via `total=0` + `skipping_payment_step_allowed: true` (brak override szablonu)
- [x] 7.5 — Rich booking confirmation page via Twig Hooks (`sylius_shop.order.thank_you.content`): banner + event details per item
- [x] 7.6 — Smoke test E2E: `shippingRequired=false` na wszystkich wariantach, dostępność stron checkout
- [x] 7.7 — Race condition test: `BookingService` z `LockMode::PESSIMISTIC_WRITE`, symulacja wyczerpania stocku

**Implementacja:** użyto natywnych mechanizmów Sylius zamiast custom OrderProcessor/template overrides. `BookingService` z pessimistic locking. Potwierdzenie bookingu via Twig Hooks.

---

## [x] Faza 8 — Custom encja Interest + frontend "Zainteresowany" — 4-5h
**Cel:** zalogowany customer może oznaczyć wydarzenie jako "zainteresowany" bez bookingu.

- [x] 8.1 — Stworzyć `App\Entity\Customer\Interest` (customer → Customer, product → Product, createdAt) z UNIQUE (customer, product)
- [x] 8.2 — Migracja
- [x] 8.3 — Repository `InterestRepository::findByCustomer()`, `findOneByCustomerAndProduct()`, `countByProduct()`
- [x] 8.4 — Service `App\Service\InterestService::toggle(Customer $c, Product $p): bool`
- [x] 8.5 — Live Component `InterestButton` (Symfony UX): ikonka serca, click toggle, route: `sylius_shop_live_component`
- [x] 8.6 — Wstawienie komponentu via Twig Hook `sylius_shop.product.show.content.info.summary` (context via `hookable_metadata.context.product`)
- [x] 8.7 — Sekcja `/account/interests` — lista wydarzeń customer'a oznaczonych jako interest
- [x] 8.8 — Smoke test: gość widzi tooltip z linkiem do logowania; zalogowany → toggle działa (live#action)

> **Uwagi po realizacji:**
> - Live Component: `symfony/ux-live-component` dostępny transitively przez Sylius — brak potrzeby dodawania bezpośredniej zależności
> - Account page: extends `@SyliusShop/account/common/index.html.twig` + Twig Hooks `sylius_shop.account.interests.index.content`
> - Account menu: EventListener na `sylius.menu.shop.account` z metodą `addInterestsMenuItem()`
> - Route locale prefix: `/{_locale}/account/interests` z requirement `[a-z]{2}_[A-Z]{2}`
> - Live Component route: musi być `route: 'sylius_shop_live_component'` (domyślny `ux_live_component` nie istnieje w Sylius)
> - Twig Hook context: zmienne dostępne przez `hookable_metadata.context.product`, nie bezpośrednio
> - Twig Hooks wyłączone w testach: `add_review` (dwa miejsca) i `associations` — pre-existing Sylius bug (empty slug w test env)

**DoD:** customer może dodać/usunąć interest, widzi listę swoich w `/account/interests`.

---

## [x] Faza 9 — Frontend shop: homepage, EventCard, strona wydarzenia, lista z filtrami, mapa, konto
**Cel:** publiczny frontend WATRY z kartami wydarzeń, filtrami i szczegółami.

- [x] 9.3 — Twig Hooks na `sylius_shop.homepage.index` — hero + spotlight + city tabs (brak custom HomeController)
- [x] 9.4 — Templates: hero, HomepageSpotlight, HomepageCity
- [x] 9.5 — Komponent Twig `EventCard` — gradient/zdjęcie, tytuł, termin, miasto, cena, "Zainteresowany"
- [x] 9.8 — Override `product/show`: ciemny hero + opis po lewej + terminy w sidebarze po prawej
- [x] 9.11 — Statyczne strony: `/regulamin`, `/polityka-prywatnosci`
- [x] 9.1 — Theme WatraShop (9B) — brak custom theme; Bootstrap via Sylius wystarczy dla MVP
- [x] 9.2 — Stimulus controllers: datepicker, map, tag_selector (9B)
- [x] 9.6 — Lista wydarzeń `/wydarzenia` (9B)
- [x] 9.7 — Live Component `EventListFilters` (9B)
- [x] 9.9 — Strona "Moje wydarzenia" w `/account/orders` (9B)
- [x] 9.12 — Responsive smoke test (9B) — smoke test przeszedł, responsive sprawdzony wizualnie

> **Uwagi po realizacji (9A):**
> - Homepage: Twig Hooks na `sylius_shop.homepage.index` — brak custom HomeController
> - EventCard: `#[AsTwigComponent]` z `ChannelContextInterface` dla ceny
> - ProductRepository: custom DQL z GROUP BY + MIN(v.startsAt) (PostgreSQL nie przyjmuje DISTINCT + ORDER BY na kolumnie spoza SELECT)
> - City translations: `city.name` niedostępne bezpośrednio w DQL — wymaga JOIN na `city.translations`
> - Per-variant add-to-cart: `CartController::addVariant` GET → redirect do checkout
> - CSS tabs na homepage: radio + label trick, zero JS
> - Twig Hooks wyłączone w testach (pre-existing Sylius bug): add_review (2x), associations, offcanvas cart items
> **Uwagi po realizacji (9B):**
> - `findDistinctCityNames`: DISTINCT + ORDER BY niedozwolone w PostgreSQL → fix: GROUP BY zamiast DISTINCT
> - Stimulus controllers w `assets/shop/controllers/` są auto-discovery przez `startStimulusApp(require.context(...))` — brak zmian w `controllers.json`
> - Mapa Leaflet: renderuje się tylko gdy `product.defaultVenue` i `product.city` są ustawione (Kraków lub Warszawa)
> - Account orders: zmienna `resources` (paginowana kolekcja) pochodzi z `attrs = { resources }` w Sylius `index.html.twig`
> - Hook `sylius_shop.product.index.content.body`: wyłączono `sidebar` + `main`, wstrzyknięto `watra_event_list`
> - Hook `sylius_shop.account.order.index.content.main`: wyłączono `grid`, wstrzyknięto `watra_orders`

**DoD:** publiczny frontend działa end-to-end, można przeglądać i filtrować wydarzenia, klikać "Zapisz się" i "Zainteresowany".

---

## [x] Faza 10 — Email transactional — 4-5h
**Cel:** automatyczne maile dla rejestracji, weryfikacji, resetu, bookingu.

- [x] 10.1 — Mailer skonfigurowany na Mailpit (`MAILER_DSN=smtp://mailpit:1025`)
- [x] 10.2 — Override Sylius'owych templatów email pod brand WATRA: rejestracja, weryfikacja, reset hasła, order_confirmation
- [x] 10.3 — Logo SVG (płomień + wordmark) + gradient #1e1b4b→#312e81 + footer WATRY w layoucie
- [x] 10.4 — Custom email "rezerwacja potwierdzona" z nazwą wydarzenia, datą, linkiem do `/account/orders`
- [x] 10.5 — Custom email "rezerwacja anulowana" — EventSubscriber na `workflow.sylius_order.completed.cancel`
- [x] 10.6 — Messenger transport `doctrine` dla async wysyłki (routing: `SendEmailMessage` → `async`)
- [x] 10.7 — Worker `php bin/console messenger:consume async` udokumentowany w README
- [x] 10.8 — Smoke test: szablony lint OK, sender config OK, subscriber zarejestrowany, routing async OK

> **Uwagi po realizacji:**
> - Async: routing `Symfony\Component\Mailer\Messenger\SendEmailMessage` → `doctrine://default`; Twig renderuje synchronicznie, tylko SMTP async
> - Layout override: `templates/bundles/SyliusCoreBundle/Email/layout.html.twig` — obejmuje wszystkie maile Syliusa automatycznie
> - Booking cancellation: EventSubscriber na `workflow.sylius_order.completed.cancel` (klasa `CompletedEvent`)
> - Sender name/address: `config/packages/sylius_mailer.yaml` (WATRA / kontakt@watra.pl)
> - Logo SVG standalone: `public/images/watra-logo.svg`

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
