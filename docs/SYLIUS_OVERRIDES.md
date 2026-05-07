# Sylius 2.x — Mapa override'ów WATRA

> **Wersja Sylius:** v2.2.0 (zainstalowana przez `composer create-project`)
> **Przeznaczenie:** referencja do Faz 2 i 3 — implementacja lokalizacji PL/EN i ukrycia zbędnych sekcji.
> **Źródła:** bezpośredni grep `vendor/` po `composer install` — dane zweryfikowane.

---

## 1. Translation domains i klucze

### 1.1 Domain: `sylius_ui`

Główny domain używany przez AdminBundle i ShopBundle do wszystkich etykiet UI. Pliki źródłowe Sylius'a:
- `src/Sylius/Bundle/UiBundle/Resources/translations/messages.en.yaml` (domain: `messages` → aliasowane jako `sylius_ui`)
- `src/Sylius/Bundle/AdminBundle/Resources/translations/messages.en.yaml`
- `src/Sylius/Bundle/ShopBundle/Resources/translations/messages.en.yaml`

> **Uwaga techniczna:** W Sylius 2.x translation domain dla UI to `sylius_ui`. W szablonach Twig wywołania mają formę `{{ 'sylius.ui.products'|trans({}, 'sylius_ui') }}` lub w YAML `sylius.ui.products`.

### 1.2 Klucze dla kluczowych terminów WATRY

Pełna lista kluczy do override'u w `translations/messages.pl.yaml` (domain: `sylius_ui`):

```yaml
# translations/messages.pl.yaml
sylius:
    ui:
        # Catalog (Product = Wydarzenie)
        product: "Wydarzenie"
        products: "Wydarzenia"
        product_variant: "Termin"
        product_variants: "Terminy"
        add_product: "Dodaj wydarzenie"
        edit_product: "Edytuj wydarzenie"
        create_product: "Utwórz wydarzenie"
        new_product: "Nowe wydarzenie"
        product_catalog: "Katalog wydarzeń"

        # Orders (Order = Rezerwacja)
        order: "Rezerwacja"
        orders: "Rezerwacje"
        add_order: "Dodaj rezerwację"
        edit_order: "Edytuj rezerwację"
        create_order: "Utwórz rezerwację"
        new_order: "Nowa rezerwacja"

        # Customers (Customer = Uczestnik)
        customer: "Uczestnik"
        customers: "Uczestnicy"
        add_customer: "Dodaj uczestnika"
        edit_customer: "Edytuj uczestnika"
        create_customer: "Utwórz uczestnika"
        new_customer: "Nowy uczestnik"

        # Taxonomy (Taxon = Tag)
        taxon: "Tag"
        taxons: "Tagi"
        taxonomy: "Taksonomia"
        add_taxon: "Dodaj tag"
        edit_taxon: "Edytuj tag"
        create_taxon: "Utwórz tag"

        # Generic UI labels
        dashboard: "Panel"
        catalog: "Katalog"
        sales: "Sprzedaż"
        customers_section: "Uczestnicy"
        configuration: "Konfiguracja"
        marketing: "Marketing"
        save_changes: "Zapisz zmiany"
        create: "Utwórz"
        edit: "Edytuj"
        delete: "Usuń"
        cancel: "Anuluj"
        back: "Wróć"
        filter: "Filtruj"
        search: "Szukaj"
        no_results: "Brak wyników"
        generate: "Generuj"
        name: "Nazwa"
        code: "Kod"
        enabled: "Aktywny"
        disabled: "Nieaktywny"
        yes: "Tak"
        no: "Nie"
        actions: "Akcje"
        id: "ID"
        state: "Status"
        date: "Data"
        total: "Suma"
        status: "Status"

        # Shipment (ukryte w WATRA, ale używane w Sylius)
        shipment: "Wysyłka"
        shipments: "Wysyłki"
        shipping_method: "Metoda wysyłki"
        shipping_methods: "Metody wysyłki"
        shipping_category: "Kategoria wysyłki"
        shipping_categories: "Kategorie wysyłki"

        # Tax (ukryte w WATRA, ale używane w Sylius)
        tax_category: "Kategoria podatkowa"
        tax_categories: "Kategorie podatkowe"
        tax_rate: "Stawka podatkowa"
        tax_rates: "Stawki podatkowe"

        # Zone / Currency (ukryte w WATRA)
        zone: "Strefa"
        zones: "Strefy"
        exchange_rate: "Kurs wymiany"
        exchange_rates: "Kursy wymiany"

        # Promotion / Catalog promotion
        promotion: "Promocja"
        promotions: "Promocje"
        catalog_promotion: "Promocja katalogowa"
        catalog_promotions: "Promocje katalogowe"
```

Analogiczne klucze dla `translations/messages.en.yaml`:

```yaml
# translations/messages.en.yaml
sylius:
    ui:
        product: "Event"
        products: "Events"
        product_variant: "Session"
        product_variants: "Sessions"
        add_product: "Add event"
        edit_product: "Edit event"
        create_product: "Create event"
        new_product: "New event"

        order: "Booking"
        orders: "Bookings"
        add_order: "Add booking"
        edit_order: "Edit booking"

        customer: "Attendee"
        customers: "Attendees"
        add_customer: "Add attendee"
        edit_customer: "Edit attendee"

        taxon: "Tag"
        taxons: "Tags"
        taxonomy: "Taxonomy"
```

### 1.3 Domain: `sylius_admin` — klucze specyficzne dla admina

Dodatkowy domain używany głównie przez AdminBundle dla komunikatów flash, tytułów stron i breadcrumbs:

```yaml
# Klucze w domain sylius_admin (override w translations/messages.pl.yaml przy dopisaniu domain)
sylius:
    admin:
        dashboard:
            title: "Panel WATRA"
        product:
            title: "Katalog wydarzeń"
            subtitle: "Zarządzaj wydarzeniami"
        order:
            title: "Rezerwacje"
            subtitle: "Zarządzaj rezerwacjami"
        customer:
            title: "Uczestnicy"
```

### 1.4 Domain: `sylius_shop` — klucze dla frontendu

Używany przez ShopBundle. Dla projektu WATRA nadpisujemy klucze shop-side:

```yaml
# translations/messages.pl.yaml (domain: sylius_shop lub messages)
sylius:
    shop:
        product:
            add_to_cart: "Zapisz się"
            out_of_stock: "Brak miejsc"
            available_on_demand: "Zapisz się"
        order:
            summary: "Podsumowanie rezerwacji"
            confirmation: "Twoja rezerwacja została potwierdzona"
        account:
            order_history:
                title: "Moje rezerwacje"
```

---

## 2. System menu admina — ZWERYFIKOWANE z vendor/

### 2.1 WAŻNE: Menu to KNP Menu, NIE Twig Hooks

**Odkrycie z task 1.11 (grep vendor/):** Sidebar admin w Sylius 2.x używa **KNP Menu** poprzez event dispatcher, a NIE Twig Hooks dla pozycji menu.

Plik: `vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/Menu/MainMenuBuilder.php`
- Event: `sylius.menu.admin.main`
- Sidebar template renderuje: `{{ knp_menu_render('sylius_admin.main', ...) }}`

### 2.2 Rzeczywiste Twig Hooks sidebara (z vendor/)

Z pliku `vendor/.../AdminBundle/Resources/config/app/twig_hooks/common/component/sidebar.yaml`:

```yaml
# Hook całego sidebara
'sylius_admin.common.component.sidebar':
    logo:      template: '@SyliusAdmin/shared/crud/common/sidebar/logo.html.twig'
    toggle_button: ...
    menu:      template: '@SyliusAdmin/shared/crud/common/sidebar/menu.html.twig'

# Hook elementów menu (tu renderuje KNP Menu)
'sylius_admin.common.component.sidebar.menu':
    search:    template: '@SyliusAdmin/shared/crud/common/sidebar/search.html.twig'
    items:     template: '@SyliusAdmin/shared/crud/common/sidebar/menu/items.html.twig'
```

Plik `items.html.twig` zawiera: `{{ knp_menu_render('sylius_admin.main', ...) }}`

### 2.3 Struktura KNP Menu — sekcje i klucze (z MainMenuBuilder.php)

Rzeczywiste klucze menu (do użycia przy event listener):

```
menu/
├── dashboard                          # sylius.ui.dashboard
├── catalog                            # sylius.menu.admin.main.catalog.header
│   ├── taxons                         # sylius.menu.admin.main.catalog.taxons
│   ├── products                       # sylius.menu.admin.main.catalog.products
│   ├── inventory                      # sylius.menu.admin.main.catalog.inventory
│   ├── attributes                     # sylius.menu.admin.main.catalog.attributes
│   ├── options                        # sylius.menu.admin.main.catalog.options
│   └── association_types              # sylius.menu.admin.main.catalog.association_types
├── sales                              # sylius.menu.admin.main.sales.header
│   ├── orders                         # sylius.menu.admin.main.sales.orders
│   ├── payments                       # sylius.ui.payments
│   └── shipments                      # sylius.ui.shipments
├── customers                          # sylius.menu.admin.main.customers.header
│   ├── customers                      # sylius.menu.admin.main.customers.customers
│   └── groups                         # sylius.menu.admin.main.customers.groups
├── marketing                          # sylius.menu.admin.main.marketing.header
│   ├── promotions
│   ├── catalog_promotions
│   └── product_reviews
├── configuration                      # sylius.menu.admin.main.configuration.header
│   ├── channels, countries, zones, currencies, exchange_rates
│   ├── locales, payment_methods
│   ├── shipping_methods               # ← do ukrycia w Fazie 3
│   ├── shipping_categories            # ← do ukrycia w Fazie 3
│   ├── tax_categories                 # ← do ukrycia w Fazie 3
│   ├── tax_rates                      # ← do ukrycia w Fazie 3
│   └── admin_users
├── official_support                   # linki do sylius.com (do ukrycia)
└── administration                     # sylius.ui.administration (placeholder Plus RBAC)
```

### 2.4 Sposób ukrycia pozycji menu (Faza 3)

**Metoda: Event Listener na `sylius.menu.admin.main`**

```php
// src/EventListener/AdminMenuListener.php
class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        // Ukryj z sekcji configuration
        if ($configuration = $menu->getChild('configuration')) {
            $configuration->removeChild('shipping_methods');
            $configuration->removeChild('shipping_categories');
            $configuration->removeChild('tax_categories');
            $configuration->removeChild('tax_rates');
            $configuration->removeChild('zones');
            $configuration->removeChild('exchange_rates');
        }

        // Ukryj sekcję official_support i administration (Sylius Plus placeholder)
        $menu->removeChild('official_support');
        $menu->removeChild('sylius.ui.administration');
    }
}
```

```yaml
# config/services.yaml
App\EventListener\AdminMenuListener:
    tags:
        - { name: kernel.event_listener, event: 'sylius.menu.admin.main', method: addAdminMenuItems }
```

### 2.5 Twig Hooks dla layoutu i stron admina

Z `vendor/.../AdminBundle/Resources/config/app/twig_hooks/common/layout.yaml`:
```yaml
'sylius_admin.base#metatags'
'sylius_admin.base#stylesheets'
'sylius_admin.base#javascripts'
```

Hooki stron produktów (z pliku `twig_hooks/product/create.yaml` etc.):
```yaml
'sylius_admin.product.create.content.form.side_navigation'   # boczna nawigacja formularza
'sylius_admin.product.update.content.form.side_navigation'
```

### 2.5 Hooki dla shop frontendu

```yaml
# Product show page
sylius_shop.product.show#main          # ← tu wstawiamy InterestButton (task 8.6)
sylius_shop.product.show#description
sylius_shop.product.show#variants
sylius_shop.product.show#attributes

# Homepage
sylius_shop.homepage#content
sylius_shop.homepage#banners

# Layout
sylius_shop.base#header
sylius_shop.base#navigation
sylius_shop.base#footer
sylius_shop.base#stylesheets          # ← już używane w _sylius.yaml
sylius_shop.base#javascripts          # ← już używane w _sylius.yaml
```

---

## 3. Template override paths

### 3.1 Ścieżki override'ów (Symfony standard)

Szablony Sylius'a nadpisujemy przez umieszczenie pliku w:
```
templates/bundles/{BundleName}/{relative_path_in_bundle}
```

### 3.2 AdminBundle — najważniejsze szablony do override'u

| Plik override | Oryginał (po `composer install`) | Cel |
|---|---|---|
| `templates/bundles/SyliusAdminBundle/_navigation.html.twig` | `vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/Resources/views/_navigation.html.twig` | Sidebar admina — ukrycie sekcji (Faza 3) |
| `templates/bundles/SyliusAdminBundle/layout.html.twig` | `...AdminBundle/Resources/views/layout.html.twig` | Główny layout admina |
| `templates/bundles/SyliusAdminBundle/Dashboard/index.html.twig` | `...AdminBundle/.../Dashboard/index.html.twig` | Dashboard (Faza 11) |
| `templates/bundles/SyliusAdminBundle/Product/index.html.twig` | `...AdminBundle/.../Product/index.html.twig` | Lista wydarzeń |
| `templates/bundles/SyliusAdminBundle/Product/_form.html.twig` | `...AdminBundle/.../Product/_form.html.twig` | Formularz wydarzenia |
| `templates/bundles/SyliusAdminBundle/Order/show.html.twig` | `...AdminBundle/.../Order/show.html.twig` | Szczegóły rezerwacji |

> **Ważne:** Sylius 2.x stopniowo przenosi szablony z `Resources/views/` do `templates/` w bundlu. Po `composer install` sprawdź rzeczywistą ścieżkę przez:
> ```bash
> find vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle -name "_navigation*" -o -name "layout*"
> ```

### 3.3 ShopBundle — szablony do override'u

| Plik override | Cel |
|---|---|
| `templates/bundles/SyliusShopBundle/Product/show.html.twig` | Strona szczegółów wydarzenia (Faza 9) |
| `templates/bundles/SyliusShopBundle/Checkout/selectShipping.html.twig` | Auto-skip shipping (Faza 7) |
| `templates/bundles/SyliusShopBundle/Checkout/selectPayment.html.twig` | Auto-skip payment (Faza 7) |
| `templates/bundles/SyliusShopBundle/Checkout/complete.html.twig` | "Rezerwacja potwierdzona" (Faza 7) |
| `templates/bundles/SyliusShopBundle/Account/Order/index.html.twig` | "Moje rezerwacje" (Faza 9) |

### 3.4 Email szablony do override'u

| Plik override | Cel |
|---|---|
| `templates/bundles/SyliusShopBundle/Email/orderConfirmation.html.twig` | Mail "Rezerwacja potwierdzona" (Faza 10) |
| `templates/bundles/SyliusShopBundle/Email/register.html.twig` | Mail rejestracyjny (Faza 10) |
| `templates/bundles/SyliusShopBundle/Email/resetPassword.html.twig` | Mail reset hasła (Faza 10) |

---

## 4. Sekcje do ukrycia z admina (Faza 3)

Lista sekcji w nawigacji admina Sylius'a, które mają być **niewidoczne w WATRA** (ale muszą istnieć jako Sylius fixtures):

| Sekcja Sylius | Powód ukrycia | Wymagana konfiguracja |
|---|---|---|
| Shipping Methods | Brak fizycznej wysyłki | Fixture: `no_shipping` (flat_rate 0) |
| Shipping Categories | j.w. | Fixture: `default_shipping_category` |
| Tax Categories | Default 0% VAT | Fixture: `default` (0%) |
| Tax Rates | j.w. | Fixture: `pl_0` (0%, zone: Polska) |
| Zones | Jeden zone "Polska" | Fixture: `pl` (zone) |
| Exchange Rates | Jedna waluta PLN | Fixture: pusty (brak) |
| Payment Methods | W WATRA: tylko Free | Fixture: `free_payment` (offline) |
| Catalog Promotions | Nie używamy | Zostaje w kodzie, ukrywamy z menu |
| Promotions | Nie używamy w MVP | Zostaje w kodzie, ukrywamy z menu |

---

## 5. Workflow po `composer install` — weryfikacja

Po uruchomieniu `make install` / `composer install` w kontenerze wykonaj:

```bash
# Zweryfikuj rzeczywistą strukturę translations
find vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle -name "*.yaml" -path "*/translations/*"

# Zweryfikuj rzeczywiste klucze dla menu/products/orders
grep -r "products\|orders\|customers\|shipment\|tax" \
  vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/Resources/translations/messages.en.yaml \
  2>/dev/null | head -40

# Zweryfikuj hooki sidebara
grep -rn "sylius_twig_hooks\|hook_name\|hookable" \
  vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/ 2>/dev/null | grep -i "nav\|menu\|sidebar" | head -20

# Znajdź pliki szablonów admina
find vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle -name "*.html.twig" | grep -i "navig\|menu\|sidebar\|layout" | head -10

# Sprawdź zarejestrowane hooki
php bin/console debug:twig-hooks 2>/dev/null || \
php bin/console sylius:debug:twig-hooks 2>/dev/null

# Sprawdź zarejestrowane translations
php bin/console debug:translation --locale=en | grep sylius | head -30
```

---

## 6. Aktualizacja po implementacji

> Ta sekcja jest uzupełniana w trakcie realizacji faz.

### Faza 2 (Lokalizacja) — do uzupełnienia

- [ ] Potwierdzone klucze translations po `composer install`
- [ ] Lista kluczy faktycznie nadpisanych w `translations/messages.pl.yaml`
- [ ] Lista kluczy faktycznie nadpisanych w `translations/messages.en.yaml`

### Faza 3 (Ukrycie sekcji) — do uzupełnienia

- [ ] Rzeczywiste nazwy hooków sidebara (po `php bin/console debug:twig-hooks`)
- [ ] Zastosowana metoda: Twig Hook override vs template override
- [ ] Lista wyłączonych hooków / usuniętych linków z sidebara

### Faza 12 (API Platform) — do uzupełnienia

- [ ] Lista domyślnie wystawionych Sylius API operations (`bin/console debug:router | grep api_platform`)
- [ ] Które operations wymagają reconfiguracji security
- [ ] Policy: per-resource override vs global `api_platform.security`

---

## 7. Znane różnice Sylius 2.x vs 1.x

| Aspekt | Sylius 1.x | Sylius 2.x |
|---|---|---|
| Admin theme | Semantic UI | Bootstrap 5 |
| Kompozycja szablonów | Bloki Twig (`{% block %}`) | **Twig Hooks** (`sylius_twig_hooks`) |
| Translation domain UI | `messages` (lub `SyliusUiBundle`) | `sylius_ui` (osobny domain) |
| Grid rendering | `sylius_grid_render` tag | Grid + Twig Hooks integration |
| PHP wymaganie | 8.0+ | **8.3+** |
| Symfony | 6.x | **7.x** |
| Doctrine ORM | 2.x | **3.x** (breaking changes w lifecycle callbacks, embeddables) |
| AdminUser override | `app/config/config.yml` | `_sylius.yaml` `sylius_user.resources.admin.user.classes.model` |

---

*Ostatnia aktualizacja: 2026-05-03 — wersja wstępna (przed `composer install`). Weryfikacja po Fazie 1 task 1.11.*

---

## Overrides dodane w fazach 5-14

### Encje (Phase 5) — `config/packages/_sylius.yaml`

| Nasza klasa | Zastępuje | Powód |
|-------------|-----------|-------|
| `App\Entity\Product\Product` | `Sylius\Component\Core\Model\Product` | Dodano: city, defaultVenue, isOnline, eventStatus, eventType |
| `App\Entity\Product\ProductVariant` | `Sylius\Component\Core\Model\ProductVariant` | Dodano: startsAt, endsAt, venue |
| `App\Entity\User\AdminUser` | `Sylius\Component\Core\Model\AdminUser` | Dodano: relacja ManyToMany → AdministrationRole |
| `App\Entity\Customer\Customer` | `Sylius\Component\Core\Model\Customer` | Pusty extends — gotowy na przyszłe zmiany |

### Email templates (Phase 10)

| Plik | Zastępuje | Powód |
|------|-----------|-------|
| `templates/bundles/SyliusCoreBundle/Email/layout.html.twig` | `@SyliusCore/Email/layout.html.twig` | WATRA branding (gradient header, stopka) |
| `templates/bundles/SyliusCoreBundle/Email/Blocks/OrderConfirmation/_content.html.twig` | domyślna treść Sylius | Dodano: nazwa wydarzenia, termin, link do konta |

### Twig Hooks (Phases 3, 7-9, 11) — `config/packages/sylius_twig_hooks.yaml`

| Hook | Co zmieniono | Cel |
|------|-------------|-----|
| `sylius_shop.homepage.index` | wyłączone default Sylius, wstrzyknięte: hero, spotlight, city tabs | WATRA homepage |
| `sylius_shop.product.show.content` | wyłączono `header`, dodano `watra_hero` | ciemny hero z obrazem |
| `sylius_shop.product.show.content.info.summary` | wyłączono prices/add_to_cart, dodano watra_terms + interest_button | lista terminów z akcją |
| `sylius_shop.product.show.content.info.overview` | wyłączono accordion, dodano watra_description | opis + mapa |
| `sylius_shop.product.index.content.body` | wyłączono sidebar + main, dodano watra_event_list | EventListFilters |
| `sylius_shop.order.thank_you.content` | wyłączono header/payment, dodano booking_banner + booking_details | strona potwierdzenia |
| `sylius_admin.dashboard.index.content` | wyłączono statistics + latest_statistics, dodano watra_metrics | WATRA dashboard |
| `sylius_admin.product.show.content.header.title_block.actions` | dodano watra_attendees | link "Lista uczestników" |
| `sylius_shop.account.order.index.content.main` | wyłączono grid, dodano watra_orders | stylowane karty rezerwacji |
| `sylius_shop.base.offcanvas.cart.body.items` | wyłączono item | bugfix Sylius (empty slug) |

### Security (Phase 6) — `config/packages/security.yaml`

- Dodano `access_control` dla admin API (`is_granted('event:edit')` itp.)
- `App\Security\AdminRoutePermissionMap` + `AdminRoutePermissionSubscriber` — custom RBAC na warstwie routingu (zamiast `access_control` per-route)
- `PermissionVoter` — custom voter sprawdzający `AdministrationRole.permissions`

### Messenger (Phase 10) — `config/packages/messenger.yaml`

- Routing: `Symfony\Component\Mailer\Messenger\SendEmailMessage` → transport `async` (`doctrine://default`)
- Renderowanie Twig maili nadal synchroniczne, tylko SMTP send async

### API (Phase 12)

- Custom route `/api/v2/shop/events` — własny Symfony controller zamiast API Platform resource
- `App\EventSubscriber\CorsSubscriber` — CORS headers (`Access-Control-Allow-Origin: *`) dla `/api/v2/`
- Istniejące Sylius API Platform routes **nie zmienione** (brak override'ów)

### Sylius Mailer (Phase 10) — `config/packages/sylius_mailer.yaml`

- Sender name/address: WATRA / kontakt@watra.pl (zamiast "Example.com")
- Dodano email type: `booking_cancelled`
