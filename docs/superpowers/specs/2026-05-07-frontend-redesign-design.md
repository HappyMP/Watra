# WATRA Frontend Redesign — Design Spec

**Data:** 2026-05-07
**Zakres:** Shop frontend (admin poza zakresem)
**Status:** Zatwierdzone — gotowe do implementacji falami

---

## Decyzje projektowe

| Decyzja | Wybór | Uzasadnienie |
|---------|-------|--------------|
| Kierunek designu | **Dark Premium** | Ewolucja obecnej fioletowej palety, nie rewolucja |
| Dokumentacja designu | **Robocza specyfikacja w kodzie** | Tokeny SCSS + CSS custom properties w repozytorium |
| CSS framework | **Bootstrap 5 + własna warstwa CSS** | Sylius 2.x jest głęboko zintegrowany z Bootstrapem — migracja na Tailwind byłaby zbyt ryzykowna |
| Typografia | **DM Sans** (Google Fonts) | Ciepły, ludzki krój — dobry dla platformy eventowej |
| Admin panel | **Poza zakresem** | Admin jest funkcjonalny, redesign dotyczy wyłącznie widoku klienta |

---

## Paleta i tokeny (do ustalenia w Fali 0)

Kierunek Dark Premium — wartości do sfinalizowania podczas brainstormingu Fali 0:

```scss
// Tło
$watra-bg-base:       #0d0b1a;   // najciemniejsze tło
$watra-bg-surface:    #1c1c2e;   // karty, panele
$watra-bg-elevated:   #252540;   // hover states, modały

// Fiolet (brand)
$watra-purple-deep:   #1e1b4b;
$watra-purple-mid:    #312e81;
$watra-purple-accent: #7c3aed;
$watra-purple-light:  #a78bfa;

// Akcenty
$watra-gold:          #f59e0b;   // CTA, ceny, wyróżnienia (do potwierdzenia)

// Tekst
$watra-text-primary:  #f1f5f9;
$watra-text-muted:    #94a3b8;
$watra-text-subtle:   #475569;
```

> Konkretne wartości HEX zostaną dobrane i zatwierdzone w sesji Fali 0.

---

## Typografia

```scss
// Font
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap');
// Produkcja: self-host przez fontsource lub plik lokalny

$font-family-base: 'DM Sans', system-ui, -apple-system, sans-serif;

// Skala (Bootstrap override)
$font-size-base:   1rem;       // 16px
$h1-font-size:     2.25rem;
$h2-font-size:     1.75rem;
$h3-font-size:     1.375rem;
$font-weight-bold: 700;
```

---

## Architektura CSS

```
assets/shop/
├── styles/
│   ├── tokens.scss        ← design tokeny (paleta, typografia, spacing)
│   ├── bootstrap.scss     ← import Bootstrap z nadpisanymi zmiennymi
│   ├── components/
│   │   ├── _navbar.scss
│   │   ├── _footer.scss
│   │   ├── _event-card.scss
│   │   ├── _hero.scss
│   │   ├── _filters.scss
│   │   └── ...
│   └── app.scss           ← główny plik (importuje wszystko)
└── entrypoint.js          ← importuje app.scss
```

**Zasada:** `tokens.scss` jest source of truth. Żaden komponent nie używa hardkodowanych wartości HEX — tylko zmienne.

---

## Inwentarz stron — 15 ekranów w 6 falach

### Fala 0 — Fundament (odblokuje wszystkie pozostałe)

| # | Ekran | Opis |
|---|-------|------|
| 0.1 | Design tokens | `tokens.scss` + `bootstrap.scss` z pełną paletą i typografią |
| 0.2 | Navbar + Footer | Global shell — layout bazowy, nawigacja, stopka |

**Dlaczego najpierw:** Zmiana jednej zmiennej w `tokens.scss` propaguje się na cały serwis. Każda kolejna fala korzysta z gotowych tokenów.

---

### Fala 1 — Core (największy ruch)

| # | Ekran | Template | Komponent |
|---|-------|----------|-----------|
| 1.1 | Homepage | `templates/shop/homepage/hero.html.twig` + hook `sylius_shop.homepage.index` | `HomepageSpotlight`, `HomepageCity` |
| 1.2 | Lista wydarzeń | `templates/shop/event_list/main.html.twig` | `EventListFilters`, `EventCard` |
| 1.3 | Strona szczegółów wydarzenia | `templates/shop/product/show/*.html.twig` | `watra_hero`, `watra_description`, `watra_terms`, `InterestButton` |

---

### Fala 2 — Flow rezerwacji

| # | Ekran | Template |
|---|-------|----------|
| 2.1 | Checkout — potwierdzenie | `SyliusShopBundle/Checkout/complete.html.twig` |
| 2.2 | Thank you / Rezerwacja potwierdzona | `templates/shop/order/thank_you/*.html.twig` |

---

### Fala 3 — Konto użytkownika

| # | Ekran | Template |
|---|-------|----------|
| 3.1 | Moje rezerwacje | `templates/shop/account/orders/main.html.twig` |
| 3.2 | Moje zainteresowania | `templates/shop/account/interests/main.html.twig` |
| 3.3 | Profil / dane konta | Sylius default (minimal override) |

---

### Fala 4 — Auth

| # | Ekran | Uwagi |
|---|-------|-------|
| 4.1 | Logowanie | Sylius default — styled override |
| 4.2 | Rejestracja | Sylius default — styled override |
| 4.3 | Reset hasła | Sylius default — styled override |

---

### Fala 5 — Email + Statyczne

| # | Ekran | Template |
|---|-------|----------|
| 5.1 | Email: rezerwacja potwierdzona | `templates/bundles/SyliusCoreBundle/Email/` |
| 5.2 | Email: anulowanie / rejestracja / reset | j.w. |
| 5.3 | Regulamin | `templates/shop/static/regulamin.html.twig` |
| 5.4 | Polityka prywatności | `templates/shop/static/privacy.html.twig` |

---

## Proces każdej fali

```
Brainstorm (makiety, opcje) → Spec (design doc) → Plan (implementation plan) → Kod (Twig + SCSS) → Review (przeglądarka)
```

Każda fala to osobna sesja. Zaczynamy od Fali 0 — resztę można realizować w dowolnej kolejności po jej ukończeniu.

---

## Następny krok

**Fala 0 — sesja brainstorming:** ustalenie finalnej palety kolorów, spacing scale, komponentów bazowych (navbar, footer), i wygenerowanie `tokens.scss`.
