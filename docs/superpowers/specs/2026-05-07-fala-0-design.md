# WATRA Fala 0 — Design Spec: Tokeny + Global Shell

**Data:** 2026-05-07
**Zakres:** Design tokens (SCSS) · Navbar · Footer
**Status:** Zatwierdzone — gotowe do implementacji

---

## Decyzje Fali 0

| Element | Decyzja |
|---------|---------|
| Kolor akcentu | **Violet/Lilac** — `#7c3aed` (primary), `#c4b5fd` (light) |
| Navbar | **Transparent → solid on scroll** (JS Stimulus controller) |
| Footer | **Standard 3-kolumnowy** — logo+opis, Odkryj, Konto |
| Font | **DM Sans** (Google Fonts → docelowo self-hosted) |

---

## Design Tokens — `assets/shop/styles/_tokens.scss`

### Paleta bazowa

```scss
// === Tła ===
$watra-bg-base:      #0d0b1a;   // tło strony
$watra-bg-surface:   #1c1c2e;   // karty, panele
$watra-bg-elevated:  #252540;   // hover, dropdown
$watra-bg-nav:       #13111f;   // navbar (solid state)
$watra-bg-footer:    #080615;   // footer

// === Fiolet — brand ===
$watra-purple-900:   #1a1030;   // najgłębszy (gradient tło)
$watra-purple-800:   #1e1b4b;   // gradient mid
$watra-purple-700:   #2d1f6e;   // gradient light
$watra-purple-600:   #4c1d95;   // mocny akcent tła
$watra-purple-500:   #7c3aed;   // primary CTA, przyciski
$watra-purple-400:   #a78bfa;   // etykiety, kategorie, subheadingi
$watra-purple-300:   #c4b5fd;   // akcent light — ceny, aktywne linki
$watra-purple-200:   #ddd6fe;   // subtle highlight

// === Tekst ===
$watra-text-primary: #f1f5f9;   // główny tekst
$watra-text-muted:   #94a3b8;   // pomocniczy tekst
$watra-text-subtle:  #64748b;   // bardzo cichy (daty, metadane)
$watra-text-ghost:   #334155;   // footer linki, copyright

// === Obramowania ===
$watra-border-base:  rgba(255, 255, 255, 0.06);
$watra-border-accent: rgba(167, 139, 250, 0.15);

// === Cienie i glowy ===
$watra-glow-purple:  0 0 60px rgba(124, 58, 237, 0.15);
```

### Bootstrap CSS variable overrides (`:root`)

```scss
// Nadpisujemy Bootstrap 5 CSS custom properties po załadowaniu Sylius CSS
:root {
  --bs-primary:       #{$watra-purple-500};
  --bs-primary-rgb:   124, 58, 237;
  --bs-body-bg:       #{$watra-bg-base};
  --bs-body-color:    #{$watra-text-primary};
  --bs-border-color:  #{$watra-border-base};
  --bs-link-color:    #{$watra-purple-300};
  --bs-link-hover-color: #{$watra-purple-200};
}
```

### Typografia

```scss
// Import (dev — produkcja: @fontsource/dm-sans lub self-host)
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap');

:root {
  --watra-font-base: 'DM Sans', system-ui, -apple-system, sans-serif;
}

body {
  font-family: var(--watra-font-base);
}
```

### Spacing i border-radius

```scss
:root {
  --watra-radius-sm:  6px;
  --watra-radius-md:  10px;
  --watra-radius-lg:  16px;
  --watra-radius-pill: 9999px;
}
```

---

## Navbar — `assets/shop/styles/_navbar.scss`

### Zachowanie

- **Stan domyślny** (góra strony): `background: transparent`, linki białe/półprzezroczyste
- **Stan scrolled** (po 60px): `background: #13111f`, `border-bottom: 1px solid rgba(167,139,250,0.1)`
- Przejście: `transition: background 0.3s ease, backdrop-filter 0.3s ease`
- Sticky (`position: sticky; top: 0; z-index: 100`)

### Struktura HTML (szkic)

```
<nav class="watra-navbar">
  <div class="container">
    <a class="watra-navbar__logo">WATRA</a>
    <div class="watra-navbar__links">
      <a>Wydarzenia</a>
      <a>Miasta</a>
      [jeśli zalogowany: Moje konto | jeśli nie: Zaloguj się (btn)]
    </div>
    <button class="watra-navbar__burger"><!-- mobile --></button>
  </div>
</nav>
```

### Stimulus controller — `navbar_controller.js`

```js
// assets/shop/controllers/navbar_controller.js
// Nasłuchuje scroll, toggleuje klasę .watra-navbar--scrolled po 60px
```

### Warianty

| Klasa | Opis |
|-------|------|
| `.watra-navbar` | bazowy (transparent) |
| `.watra-navbar--scrolled` | solid dark (dodawana przez JS) |

---

## Footer — `assets/shop/styles/_footer.scss`

### Struktura (3 kolumny + pasek dolny)

```
<footer class="watra-footer">
  <div class="container">
    <div class="watra-footer__grid">
      <div class="watra-footer__brand">   <!-- logo + opis -->
      <div class="watra-footer__col">    <!-- Odkryj: All events, Workshops, Conferences, Online -->
      <div class="watra-footer__col">    <!-- Konto: My bookings, Interests, Login -->
    </div>
    <div class="watra-footer__bottom">   <!-- copyright + Regulamin + Prywatność -->
  </div>
</footer>
```

### Kolory

- Tło: `$watra-bg-footer` (`#080615`)
- Border top: `$watra-border-base`
- Logo: `$watra-text-primary`
- Nagłówki kolumn: `$watra-text-subtle` (uppercase, letter-spacing)
- Linki: `$watra-text-ghost` → hover: `$watra-text-muted`
- Copyright: `$watra-text-ghost`

---

## Architektura plików

```
assets/shop/
├── styles/
│   ├── _tokens.scss        ← design tokens (ten dokument)
│   ├── _bootstrap-overrides.scss  ← :root CSS var overrides
│   ├── _navbar.scss        ← navbar component
│   ├── _footer.scss        ← footer component
│   └── app.scss            ← główny plik (importuje wszystko)
└── entrypoint.js           ← dodajemy: import './styles/app.scss'
```

`app.scss`:
```scss
@import "tokens";
@import "bootstrap-overrides";
@import "navbar";
@import "footer";
```

---

## Twig — integracja z Sylius Twig Hooks

Navbar i footer wstrzykujemy przez istniejące hooki (już skonfigurowane w `_sylius.yaml`):

| Hook | Co wstawiamy |
|------|-------------|
| `sylius_shop.base#stylesheets` | `encore_entry_link_tags('app-shop-entry')` ← już działa |
| `sylius_shop.base#header` | nowy szablon `templates/shop/layout/navbar.html.twig` |
| `sylius_shop.base#footer` | nowy szablon `templates/shop/layout/footer.html.twig` |

Navbar controller JS dostaje atrybut `data-controller="navbar"` na elemencie `<nav>`.

---

## Kryteria ukończenia Fali 0

- [ ] `assets/shop/styles/app.scss` istnieje i jest importowany przez `entrypoint.js`
- [ ] `_tokens.scss` definiuje pełną paletę i nadpisuje Bootstrap CSS vars
- [ ] Font DM Sans ładuje się na wszystkich stronach
- [ ] Navbar transparent na górze, solid po 60px scrollu
- [ ] Navbar sticky na wszystkich stronach
- [ ] Navbar hamburger działa na mobile (≤768px)
- [ ] Footer 3-kolumnowy z poprawnymi linkami
- [ ] `npm run build` bez błędów
- [ ] Wygląd sprawdzony w przeglądarce na: homepage, liście wydarzeń, stronie szczegółów
