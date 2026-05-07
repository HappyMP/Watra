# WATRA Fala 1 — Design Spec: Homepage

**Data:** 2026-05-07
**Zakres:** Strona główna (`/pl_PL/`)
**Status:** Zatwierdzone — gotowe do implementacji

---

## Struktura strony — 5 sekcji (od góry)

```
[Navbar — Fala 0]
  1. Hero
  2. Wyróżnione (HomepageSpotlight — refaktoryzacja)
  3. Manifesto — Iskra / Płomień / Żar  ← NOWA
  4. Miasta (HomepageCity — refaktoryzacja)
  5. CTA Przystań  ← NOWA
[Footer — Fala 0]
```

---

## Sekcja 1 — Hero

**Zmiany względem obecnego:** nowy nagłówek, nowy podtytuł, dwa CTA, tło z radial gradient glow.

### Nagłówek (gradient text)
```
Małe grupy.
Prawdziwy kontakt.
Żar który zostaje.
```
Styl: `font-size: 2.4rem`, `font-weight: 800`, gradient `#f1f5f9 → #c4b5fd` przez `-webkit-background-clip: text`.

### Podtytuł
```
Watra to kameralne spotkania w różnych miejscach Polski — i online.
Miejsca, w których iskra zamienia się w coś prawdziwego.
```

### CTA
- Główne: „Znajdź swoje wydarzenie →" → `sylius_shop_product_index` — button `$watra-purple-500`
- Dodatkowe: „Czym jest Watra?" → anchor `#watra-manifesto` — ghost button z `$watra-border-accent`

### Tło
```scss
background:
  radial-gradient(ellipse 80% 60% at 50% -10%, rgba(124,58,237,0.35), transparent 70%),
  linear-gradient(160deg, #1a1030 0%, #0d0b1a 60%);
```
Subtelny dolny glow: `radial-gradient(ellipse, rgba(124,58,237,0.12), transparent 70%)`.

### Klasa body
Dodać `watra-has-transparent-nav` — navbar będzie transparent nad hero.

### Klucze tłumaczeń (nowe/zmienione)
```yaml
app.ui.homepage_hero_title: "Małe grupy. Prawdziwy kontakt. Żar który zostaje."
app.ui.homepage_hero_subtitle: "Watra to kameralne spotkania w różnych miejscach Polski — i online. Miejsca, w których iskra zamienia się w coś prawdziwego."
app.ui.homepage_hero_cta_primary: "Znajdź swoje wydarzenie"
app.ui.homepage_hero_cta_secondary: "Czym jest Watra?"
```

---

## Sekcja 2 — Wyróżnione (HomepageSpotlight — refaktoryzacja)

Obecny komponent (`HomepageSpotlight`) zachowuje logikę PHP/data. Zmieniamy tylko markup i style:

- Duża karta (featured): `border-radius: 12px`, gradient tło fioletowe, tytuł i CTA na dole
- Małe karty (upcoming): `background: $watra-bg-surface`, border `$watra-border-base`
- Section label: `✨ WYRÓŻNIONE` (uppercase, `$watra-text-subtle`, letter-spacing)

Obecne klasy Bootstrap (`row`, `col-*`) zastępujemy CSS grid w `_homepage.scss`.

---

## Sekcja 3 — Manifesto (NOWA)

**Nowy komponent:** `HomepageManifesto` (statyczny, bez PHP — czysty Twig + SCSS).

```
id="watra-manifesto"  ← anchor dla CTA "Czym jest Watra?"
background: $watra-bg-nav (#13111f)
```

### Nagłówek sekcji
```
🌱 NASZE KORZENIE
Ognisko, wokół którego ludzie gromadzą się od zawsze.
```
Podtytuł: *„Watra to słowiańskie słowo na ognisko. Wracamy do korzeni — do obecności, do prawdziwej rozmowy, do grup gdzie każdy głos ma znaczenie."*

### 3 kolumny (Iskra / Płomień / Żar)

| | Ikona | Tytuł | Opis |
|-|-------|-------|------|
| 1 | ✨ | Iskra | Każde wydarzenie zaczyna się od iskry — pierwszego spotkania, nowej rozmowy. |
| 2 | 🔥 | Płomień | Samo wydarzenie — żywe, dynamiczne, które istnieje tylko tu i teraz. |
| 3 | 🌡️ | Żar | To co zostaje — znajomości, pomysły, nić między ludźmi która nie gaśnie. |

Styl kart: `background: rgba(124,58,237,0.08)`, `border: 1px solid $watra-border-accent`, `border-radius: $watra-radius-md`.

### Klucze tłumaczeń (nowe)
```yaml
app.ui.manifesto_label: "Nasze korzenie"
app.ui.manifesto_title: "Ognisko, wokół którego ludzie gromadzą się od zawsze."
app.ui.manifesto_subtitle: "Watra to słowiańskie słowo na ognisko. Wracamy do korzeni — do obecności, do prawdziwej rozmowy, do grup gdzie każdy głos ma znaczenie."
app.ui.manifesto_spark_title: "Iskra"
app.ui.manifesto_spark_desc: "Każde wydarzenie zaczyna się od iskry — pierwszego spotkania, nowej rozmowy."
app.ui.manifesto_flame_title: "Płomień"
app.ui.manifesto_flame_desc: "Samo wydarzenie — żywe, dynamiczne, które istnieje tylko tu i teraz."
app.ui.manifesto_ember_title: "Żar"
app.ui.manifesto_ember_desc: "To co zostaje — znajomości, pomysły, nić między ludźmi która nie gaśnie."
```

---

## Sekcja 4 — Miasta (HomepageCity — refaktoryzacja)

Obecny komponent zachowuje logikę PHP. Zmieniamy tylko markup:

- Zakładki miast: pill-style (`border-radius: $watra-radius-pill`), aktywna `background: $watra-purple-500`
- Nieaktywne: `background: $watra-bg-surface`, `color: $watra-text-subtle`
- Karty wydarzeń: `EventCard` komponent (bez zmian w tej fazie)
- Link „Zobacz wszystkie" na dole

---

## Sekcja 5 — CTA Przystań (NOWA)

**Nowy template:** `templates/shop/homepage/cta.html.twig` (statyczny Twig).

```
Ikona: ⚓
Nagłówek: "Szukasz swojej przystani?"
Podtytuł: "Watra to miejsce dla tych, którzy czują zew — do prawdziwego kontaktu, do rozmów które zostają."
CTA: "Przeglądaj wydarzenia →" → sylius_shop_product_index
```

Tło: `linear-gradient(135deg, rgba(124,58,237,0.15), rgba(30,27,75,0.3))`, border top `$watra-border-accent`.

### Klucze tłumaczeń (nowe)
```yaml
app.ui.cta_harbor_title: "Szukasz swojej przystani?"
app.ui.cta_harbor_subtitle: "Watra to miejsce dla tych, którzy czują zew — do prawdziwego kontaktu, do rozmów które zostają."
app.ui.cta_harbor_cta: "Przeglądaj wydarzenia"
```

---

## Pliki do stworzenia / modyfikacji

| Akcja | Plik |
|-------|------|
| Modify | `templates/shop/homepage/hero.html.twig` |
| Modify | `templates/components/HomepageSpotlight.html.twig` |
| Modify | `templates/components/HomepageCity.html.twig` |
| Create | `templates/components/HomepageManifesto.html.twig` |
| Create | `templates/shop/homepage/cta.html.twig` |
| Create | `assets/shop/styles/_homepage.scss` |
| Modify | `assets/shop/styles/app.scss` |
| Modify | `config/packages/sylius_twig_hooks.yaml` |
| Modify | `translations/messages.pl.yaml` |
| Modify | `translations/messages.en.yaml` |

---

## Hook order (sylius_shop.homepage.index)

```yaml
'sylius_shop.homepage.index':
    hero:      template: 'shop/homepage/hero.html.twig'       priority: 500
    spotlight: component: HomepageSpotlight                    priority: 400
    manifesto: component: HomepageManifesto                    priority: 300
    city:      component: HomepageCity                         priority: 200
    cta:       template: 'shop/homepage/cta.html.twig'         priority: 100
```
