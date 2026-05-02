# WATRA — glossary (mapa Sylius ↔ domena WATRY)

Sylius to e-commerce platform. WATRA to portal wydarzeniowy zbudowany na Sylius'ie. Pojęcia Sylius'a są używane w kodzie, ale UX i dokumentacja mówią językiem domeny WATRY.

## Najważniejsze mapowania

| W kodzie (Sylius) | W UI / domenie (WATRA) | Komentarz |
|---|---|---|
| `Product` | **Wydarzenie** | Jeden Product = jedno wydarzenie (tytuł, opis, miasto, tagi, zdjęcia) |
| `ProductVariant` | **Termin** | Jeden Variant = konkretna data wydarzenia. Pojedyncze wydarzenie = 1 variant; cykl = N variantów |
| `ProductVariant.onHand` | **Wolne miejsca** | Sylius natywnie pilnuje, że nie sprzedasz więcej niż jest w "stocku" |
| `Order` | **Rezerwacja** / **Booking** | Order ze statusem `cart` → `new` → `fulfilled` |
| `OrderItem` | **Bilet** | Pojedyncza pozycja w rezerwacji |
| `Customer` | **Uczestnik** / **Użytkownik** | Frontowy user portalu |
| `AdminUser` | **Administrator** | Backendowy user, zarządza katalogiem |
| `Taxon` | **Tag** / **Kategoria** | Translatable, hierarchiczny |
| `Channel` | **Sklep** | MVP: jeden channel `watra`. Później: per miasto / per organizator / per brand |
| `Payment` | **Płatność** | MVP: zawsze `free` (offline gateway, total = 0) |
| `PaymentMethod` `free` | "Wydarzenie darmowe" | Sztuczny gateway dla orderów z totalem 0 |
| `ProductImage` | **Zdjęcie wydarzenia** | Galeria + main image |

## Encje custom WATRY (nie z Sylius'a)

| Encja | Rola |
|---|---|
| `City` | Miasto (Kraków, Warszawa, ...) — translatable |
| `Venue` | Konkretne miejsce/lokal w mieście |
| `Interest` | "Zainteresowany" — bookmark customer'a na wydarzenie (M2M Customer ↔ Product) |
| `AdministrationRole` | Rola admin'a z listą permissionów (RBAC) |
| `Permission` (enum) | Lista granularnych uprawnień: `event:create`, `booking:cancel`, etc. |

## Translations w UI

W panelu admina i shop frontendu nadpisujemy klucze tłumaczeń w `translations/messages.pl.yaml`:

```yaml
sylius_ui:
  products: "Wydarzenia"
  product: "Wydarzenie"
  product_variants: "Terminy"
  product_variant: "Termin"
  orders: "Rezerwacje"
  order: "Rezerwacja"
  customers: "Uczestnicy"
  customer: "Uczestnik"
  taxons: "Tagi"
  taxon: "Tag"
```

Analogicznie w `messages.en.yaml`: Events / Sessions / Bookings / Attendees / Tags.

## Czego NIE używamy z Sylius'a (ale "siedzi pod spodem")

Te moduły są wymagane przez Sylius core, ale ukrywamy je z UX:
- Shipping Methods, Shipping Categories — wydarzenia się "nie wysyłają", ale Sylius wymaga 1 method (mamy `no_shipping`)
- Tax Categories, Tax Rates, Zones — mamy default zerowy
- Exchange Rates — jedna waluta `PLN`
- Inventory tracking — używamy go, ale jako "capacity" wydarzenia, nie magazynu

## Dla nowych developerów

Najczęstsze pytanie: **"Dlaczego encja nazywa się `Product` skoro to wydarzenie?"**

Odpowiedź: Sylius to dojrzały framework e-commerce. Nazewnictwo encji jest jego konwencją. Zmiana nazw klas to byłaby wojna z całym ekosystemem Sylius'a (ResourceBundle, GridBundle, API Platform integration, plugins) — niewarta świeczki. Zamiast tego mapujemy tylko **prezentację** (UI/UX) na język WATRY przez translations + custom labels.

W kodzie biznesowym używaj `Product` / `ProductVariant` / `Order`. Ewentualnie dodaj cienki wrapper domenowy (`App\Service\EventService` wywołuje wewnątrz `ProductRepository`) jeśli chcesz nadać kodowi językowi domeny.
