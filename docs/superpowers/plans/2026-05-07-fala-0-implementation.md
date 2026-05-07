# Fala 0 — Implementation Plan: Design Tokens + Global Shell

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Introduce WATRA design tokens (Dark Premium palette, DM Sans font) and replace the Sylius default header/footer with a custom navbar (transparent→solid on scroll) and a 3-column footer.

**Architecture:** A new `assets/shop/styles/app.scss` entry (imported by the existing webpack entrypoint) layers WATRA CSS custom properties on top of Sylius's pre-compiled Bootstrap CSS. The navbar and footer are standalone Twig templates injected via Twig Hooks, replacing all default Sylius header/footer hookables. A Stimulus controller handles the scroll-to-solid navbar transition.

**Tech stack:** Symfony UX Stimulus · Webpack Encore (SCSS via `enableSassLoader()`) · Sylius Twig Hooks · Bootstrap 5 CSS custom properties

---

## File map

| Action | Path | Responsibility |
|--------|------|---------------|
| Create | `assets/shop/styles/app.scss` | Main SCSS entry — imports all partials |
| Create | `assets/shop/styles/_tokens.scss` | Design tokens: palette, spacing, border-radius |
| Create | `assets/shop/styles/_bootstrap-overrides.scss` | Override Bootstrap 5 CSS vars + load DM Sans |
| Create | `assets/shop/styles/_navbar.scss` | Navbar component styles |
| Create | `assets/shop/styles/_footer.scss` | Footer component styles |
| Create | `assets/shop/controllers/navbar_controller.js` | Stimulus: transparent→solid on scroll |
| Create | `templates/shop/layout/navbar.html.twig` | Navbar HTML |
| Create | `templates/shop/layout/footer.html.twig` | Footer HTML |
| Modify | `assets/shop/entrypoint.js` | Add `import './styles/app.scss'` |
| Modify | `config/packages/sylius_twig_hooks.yaml` | Disable Sylius header/footer, inject watra_navbar/watra_footer |
| Create | `features/shop/layout.feature` | Behat: navbar + footer present on key pages |

---

## Task 1: SCSS architecture — scaffold and wire up

**Files:**
- Create: `assets/shop/styles/app.scss`
- Modify: `assets/shop/entrypoint.js`

- [ ] **Step 1: Create `app.scss` scaffold**

```scss
// assets/shop/styles/app.scss
@import "tokens";
@import "bootstrap-overrides";
@import "navbar";
@import "footer";
```

- [ ] **Step 2: Create placeholder partials so the build doesn't fail**

Create `assets/shop/styles/_tokens.scss`:
```scss
// tokens — filled in Task 2
```

Create `assets/shop/styles/_bootstrap-overrides.scss`:
```scss
// bootstrap overrides — filled in Task 2
```

Create `assets/shop/styles/_navbar.scss`:
```scss
// navbar — filled in Task 4
```

Create `assets/shop/styles/_footer.scss`:
```scss
// footer — filled in Task 6
```

- [ ] **Step 3: Import `app.scss` in the shop entrypoint**

Edit `assets/shop/entrypoint.js` — add as first line:
```js
import './styles/app.scss';
import '@vendor/sylius/mollie-plugin/assets/shop/entrypoint';
import './bootstrap.js';

console.log('Hello Webpack Encore! Edit me in assets/shop/entrypoint.js');
```

- [ ] **Step 4: Verify the build passes**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -20"
```

Expected: build finishes without SCSS errors. If you see `Module not found: Error: Can't resolve`, verify file paths in `@import` statements.

- [ ] **Step 5: Commit**

```bash
git add assets/shop/styles/ assets/shop/entrypoint.js
git commit -m "feat(fala-0): scaffold SCSS architecture and wire into entrypoint"
```

---

## Task 2: Design tokens + Bootstrap CSS variable overrides

**Files:**
- Modify: `assets/shop/styles/_tokens.scss`
- Modify: `assets/shop/styles/_bootstrap-overrides.scss`

- [ ] **Step 1: Fill in `_tokens.scss`**

```scss
// assets/shop/styles/_tokens.scss

// --- Backgrounds ---
$watra-bg-base:       #0d0b1a;
$watra-bg-surface:    #1c1c2e;
$watra-bg-elevated:   #252540;
$watra-bg-nav:        #13111f;
$watra-bg-footer:     #080615;

// --- Purple brand scale ---
$watra-purple-900:    #1a1030;
$watra-purple-800:    #1e1b4b;
$watra-purple-700:    #2d1f6e;
$watra-purple-600:    #4c1d95;
$watra-purple-500:    #7c3aed;
$watra-purple-400:    #a78bfa;
$watra-purple-300:    #c4b5fd;
$watra-purple-200:    #ddd6fe;

// --- Text ---
$watra-text-primary:  #f1f5f9;
$watra-text-muted:    #94a3b8;
$watra-text-subtle:   #64748b;
$watra-text-ghost:    #334155;

// --- Borders ---
$watra-border-base:   rgba(255, 255, 255, 0.06);
$watra-border-accent: rgba(167, 139, 250, 0.15);

// --- Radii ---
$watra-radius-sm:     6px;
$watra-radius-md:     10px;
$watra-radius-lg:     16px;
$watra-radius-pill:   9999px;
```

- [ ] **Step 2: Fill in `_bootstrap-overrides.scss`**

```scss
// assets/shop/styles/_bootstrap-overrides.scss
// Loaded after Sylius's Bootstrap — overrides via CSS custom properties

@import url('https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,700&display=swap');

:root {
  // Typography
  --bs-body-font-family: 'DM Sans', system-ui, -apple-system, sans-serif;

  // Colors
  --bs-primary:          #{$watra-purple-500};
  --bs-primary-rgb:      124, 58, 237;
  --bs-body-bg:          #{$watra-bg-base};
  --bs-body-color:       #{$watra-text-primary};
  --bs-border-color:     #{$watra-border-base};
  --bs-link-color:       #{$watra-purple-300};
  --bs-link-hover-color: #{$watra-purple-200};

  // Form controls on dark background
  --bs-form-control-bg:          #{$watra-bg-surface};
  --bs-form-control-color:       #{$watra-text-primary};
  --bs-form-control-border-color: #{$watra-border-base};
}

body {
  background-color: $watra-bg-base;
  color: $watra-text-primary;
}
```

- [ ] **Step 3: Build and verify font + body color apply**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -10"
```

Open `http://localhost/pl_PL/` in the browser. Body background should be near-black (`#0d0b1a`), text near-white, font DM Sans. If font is unchanged — clear browser cache or check the stylesheet order in `<head>`.

- [ ] **Step 4: Commit**

```bash
git add assets/shop/styles/_tokens.scss assets/shop/styles/_bootstrap-overrides.scss
git commit -m "feat(fala-0): add design tokens and Bootstrap CSS variable overrides"
```

---

## Task 3: Navbar Stimulus controller

**Files:**
- Create: `assets/shop/controllers/navbar_controller.js`

- [ ] **Step 1: Write the controller**

```js
// assets/shop/controllers/navbar_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = { threshold: { type: Number, default: 60 } };

    connect() {
        this._onScroll = this._handleScroll.bind(this);
        window.addEventListener('scroll', this._onScroll, { passive: true });
        this._handleScroll();
    }

    disconnect() {
        window.removeEventListener('scroll', this._onScroll);
    }

    _handleScroll() {
        const scrolled = window.scrollY > this.thresholdValue;
        this.element.classList.toggle('watra-navbar--scrolled', scrolled);
    }
}
```

- [ ] **Step 2: Build to verify no JS errors**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -10"
```

Expected: clean build. If `@hotwired/stimulus` is not resolved — it's a devDependency in `package.json`, check with `npm ls @hotwired/stimulus`.

- [ ] **Step 3: Commit**

```bash
git add assets/shop/controllers/navbar_controller.js
git commit -m "feat(fala-0): add navbar Stimulus controller (transparent→solid on scroll)"
```

---

## Task 4: Navbar SCSS

**Files:**
- Modify: `assets/shop/styles/_navbar.scss`

- [ ] **Step 1: Fill in `_navbar.scss`**

```scss
// assets/shop/styles/_navbar.scss

.watra-navbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1030;
  height: 60px;
  display: flex;
  align-items: center;
  background: transparent;
  transition: background 0.3s ease, border-color 0.3s ease, backdrop-filter 0.3s ease;
  border-bottom: 1px solid transparent;

  &--scrolled {
    background: $watra-bg-nav;
    border-bottom-color: $watra-border-accent;
  }

  .container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
  }

  &__logo {
    color: $watra-text-primary;
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 2px;
    text-decoration: none;

    &:hover {
      color: $watra-text-primary;
      text-decoration: none;
    }
  }

  &__links {
    display: flex;
    align-items: center;
    gap: 1.5rem;

    a {
      color: $watra-text-muted;
      font-size: 0.85rem;
      text-decoration: none;
      transition: color 0.2s;

      &:hover {
        color: $watra-text-primary;
      }

      &.active {
        color: $watra-purple-300;
      }
    }
  }

  &__cta {
    background: $watra-purple-500;
    color: $watra-text-primary !important;
    padding: 0.4rem 1rem;
    border-radius: $watra-radius-sm;
    font-size: 0.8rem;
    font-weight: 600;
    transition: background 0.2s;

    &:hover {
      background: $watra-purple-600 !important;
      color: $watra-text-primary !important;
    }
  }

  // Push page content below fixed navbar
  & + * {
    padding-top: 60px;
  }

  // Mobile burger (hidden on desktop)
  &__burger {
    display: none;
    background: none;
    border: none;
    color: $watra-text-primary;
    cursor: pointer;
    padding: 0.25rem;
  }

  @media (max-width: 767.98px) {
    &__links {
      display: none;
      position: absolute;
      top: 60px;
      left: 0;
      right: 0;
      flex-direction: column;
      align-items: flex-start;
      padding: 1rem 1.5rem;
      background: $watra-bg-nav;
      border-bottom: 1px solid $watra-border-accent;
      gap: 1rem;

      &--open {
        display: flex;
      }
    }

    &__burger {
      display: block;
    }
  }
}

// Offset pages that have a hero directly under navbar
.watra-has-transparent-nav {
  .watra-navbar + * {
    padding-top: 0;
  }
}
```

- [ ] **Step 2: Build check**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -10"
```

- [ ] **Step 3: Commit**

```bash
git add assets/shop/styles/_navbar.scss
git commit -m "feat(fala-0): add navbar component SCSS"
```

---

## Task 5: Navbar Twig template + Twig Hook

**Files:**
- Create: `templates/shop/layout/navbar.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

- [ ] **Step 1: Create the navbar template**

```twig
{# templates/shop/layout/navbar.html.twig #}
<nav class="watra-navbar"
     data-controller="navbar">
    <div class="container">
        <a href="{{ path('sylius_shop_homepage', {_locale: app.request.locale}) }}"
           class="watra-navbar__logo">
            WATRA
        </a>

        <div class="watra-navbar__links" id="watra-nav-links">
            <a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}">
                {{ 'app.ui.all_events'|trans }}
            </a>

            {% if app.user %}
                <a href="{{ path('sylius_shop_account_order_index', {_locale: app.request.locale}) }}">
                    {{ 'app.ui.my_bookings'|trans }}
                </a>
                <a href="{{ path('sylius_shop_account_profile_update', {_locale: app.request.locale}) }}">
                    {{ 'sylius.ui.my_account'|trans({}, 'sylius_ui') }}
                </a>
            {% else %}
                <a href="{{ path('sylius_shop_login', {_locale: app.request.locale}) }}"
                   class="watra-navbar__cta">
                    {{ 'sylius.ui.log_in'|trans({}, 'sylius_ui') }}
                </a>
            {% endif %}
        </div>

        <button class="watra-navbar__burger"
                aria-label="Menu"
                onclick="this.closest('nav').querySelector('#watra-nav-links').classList.toggle('watra-navbar__links--open')">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                <rect y="3" width="20" height="2" rx="1"/>
                <rect y="9" width="20" height="2" rx="1"/>
                <rect y="15" width="20" height="2" rx="1"/>
            </svg>
        </button>
    </div>
</nav>
```

- [ ] **Step 2: Disable Sylius header entries and inject our navbar**

In `config/packages/sylius_twig_hooks.yaml`, add under the `hooks:` key:

```yaml
        'sylius_shop.base.header':
            top_bar:
                enabled: false
            content:
                enabled: false
            navbar:
                enabled: false
            flashes:
                enabled: false
            watra_navbar:
                template: 'shop/layout/navbar.html.twig'
                priority: 100
```

- [ ] **Step 3: Add `watra-has-transparent-nav` body class to homepage**

Verify homepage hero renders directly under the navbar (no gap). If you see a 60px white gap, add the body class `watra-has-transparent-nav` to the homepage template or hero section.

Check by visiting `http://localhost/pl_PL/` — the navbar should be transparent over the hero, then become dark on scroll.

- [ ] **Step 4: Commit**

```bash
git add templates/shop/layout/navbar.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(fala-0): add WATRA navbar template and replace Sylius header via Twig Hooks"
```

---

## Task 6: Footer SCSS

**Files:**
- Modify: `assets/shop/styles/_footer.scss`

- [ ] **Step 1: Fill in `_footer.scss`**

```scss
// assets/shop/styles/_footer.scss

.watra-footer {
  background: $watra-bg-footer;
  border-top: 1px solid $watra-border-base;
  padding: 2.5rem 0 1.25rem;
  margin-top: auto;

  &__grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;

    @media (max-width: 767.98px) {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  }

  &__brand {
    .watra-footer__logo {
      color: $watra-text-primary;
      font-weight: 700;
      font-size: 0.9rem;
      letter-spacing: 2px;
      text-decoration: none;
      display: block;
      margin-bottom: 0.625rem;

      &:hover {
        color: $watra-text-primary;
      }
    }

    p {
      color: $watra-text-ghost;
      font-size: 0.75rem;
      line-height: 1.7;
      margin: 0;
    }
  }

  &__col {
    h4 {
      color: $watra-text-subtle;
      font-size: 0.6rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 0.75rem;
    }

    ul {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 0.375rem;
    }

    a {
      color: $watra-text-ghost;
      font-size: 0.75rem;
      text-decoration: none;
      transition: color 0.2s;

      &:hover {
        color: $watra-text-muted;
      }
    }
  }

  &__bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.04);
    padding-top: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;

    span {
      color: $watra-text-ghost;
      font-size: 0.65rem;
    }

    div {
      display: flex;
      gap: 1rem;
    }

    a {
      color: $watra-text-ghost;
      font-size: 0.65rem;
      text-decoration: none;

      &:hover {
        color: $watra-text-muted;
      }
    }
  }
}
```

- [ ] **Step 2: Build check**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -10"
```

- [ ] **Step 3: Commit**

```bash
git add assets/shop/styles/_footer.scss
git commit -m "feat(fala-0): add footer component SCSS"
```

---

## Task 7: Footer Twig template + Twig Hook

**Files:**
- Create: `templates/shop/layout/footer.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

- [ ] **Step 1: Create the footer template**

```twig
{# templates/shop/layout/footer.html.twig #}
<footer class="watra-footer">
    <div class="container">
        <div class="watra-footer__grid">
            <div class="watra-footer__brand">
                <a href="{{ path('sylius_shop_homepage', {_locale: app.request.locale}) }}"
                   class="watra-footer__logo">WATRA</a>
                <p>{{ 'app.ui.footer_tagline'|trans }}</p>
            </div>

            <div class="watra-footer__col">
                <h4>{{ 'app.ui.footer_discover'|trans }}</h4>
                <ul>
                    <li><a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}">{{ 'app.ui.all_events'|trans }}</a></li>
                    <li><a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}?eventType=workshop">{{ 'app.enum.event_type.workshop'|trans }}</a></li>
                    <li><a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}?eventType=conference">{{ 'app.enum.event_type.conference'|trans }}</a></li>
                    <li><a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}?isOnline=1">{{ 'app.ui.online_events'|trans }}</a></li>
                </ul>
            </div>

            <div class="watra-footer__col">
                <h4>{{ 'app.ui.footer_account'|trans }}</h4>
                <ul>
                    {% if app.user %}
                        <li><a href="{{ path('sylius_shop_account_order_index', {_locale: app.request.locale}) }}">{{ 'app.ui.my_bookings'|trans }}</a></li>
                        <li><a href="{{ path('sylius_shop_account_interests', {_locale: app.request.locale}) }}">{{ 'app.ui.interests'|trans }}</a></li>
                        <li><a href="{{ path('sylius_shop_account_profile_update', {_locale: app.request.locale}) }}">{{ 'sylius.ui.my_account'|trans({}, 'sylius_ui') }}</a></li>
                    {% else %}
                        <li><a href="{{ path('sylius_shop_login', {_locale: app.request.locale}) }}">{{ 'sylius.ui.log_in'|trans({}, 'sylius_ui') }}</a></li>
                        <li><a href="{{ path('sylius_shop_register', {_locale: app.request.locale}) }}">{{ 'sylius.ui.register'|trans({}, 'sylius_ui') }}</a></li>
                    {% endif %}
                </ul>
            </div>
        </div>

        <div class="watra-footer__bottom">
            <span>© {{ "now"|date("Y") }} WATRA</span>
            <div>
                <a href="{{ path('app_shop_regulamin', {_locale: app.request.locale}) }}">{{ 'app.ui.terms'|trans }}</a>
                <a href="{{ path('app_shop_privacy', {_locale: app.request.locale}) }}">{{ 'app.ui.privacy'|trans }}</a>
            </div>
        </div>
    </div>
</footer>
```

- [ ] **Step 2: Disable Sylius footer entries and inject our footer**

In `config/packages/sylius_twig_hooks.yaml`, add under the `hooks:` key:

```yaml
        'sylius_shop.base.footer':
            content:
                enabled: false
            watra_footer:
                template: 'shop/layout/footer.html.twig'
                priority: 100
```

- [ ] **Step 3: Add missing translation keys**

In `translations/messages.pl.yaml`, add if missing:

```yaml
app:
    ui:
        footer_tagline: "Platforma wydarzeń edukacyjnych i kulturalnych w Polsce."
        footer_discover: "Odkryj"
        footer_account: "Konto"
        online_events: "Online"
        my_bookings: "Moje rezerwacje"
        interests: "Zainteresowania"
        terms: "Regulamin"
        privacy: "Polityka prywatności"
```

In `translations/messages.en.yaml`, add the same keys in English:

```yaml
app:
    ui:
        footer_tagline: "Platform for educational and cultural events in Poland."
        footer_discover: "Discover"
        footer_account: "Account"
        online_events: "Online"
        my_bookings: "My bookings"
        interests: "Interests"
        terms: "Terms"
        privacy: "Privacy policy"
```

- [ ] **Step 4: Check routes exist**

```bash
sudo docker compose -f compose.yaml exec php bin/console debug:router | grep -E "app_shop_regulamin|app_shop_privacy|sylius_shop_account_interests"
```

Routes `app_shop_regulamin` and `app_shop_privacy` are defined in `src/Controller/Shop/StaticPageController.php` and should exist. If `sylius_shop_account_interests` is missing, replace that link with `path('sylius_shop_account_profile_update', ...)` temporarily.

- [ ] **Step 5: Commit**

```bash
git add templates/shop/layout/footer.html.twig config/packages/sylius_twig_hooks.yaml translations/
git commit -m "feat(fala-0): add WATRA footer template and replace Sylius footer via Twig Hooks"
```

---

## Task 8: Behat layout test

**Files:**
- Create: `features/shop/layout.feature`

- [ ] **Step 1: Write the feature**

```gherkin
# features/shop/layout.feature
Feature: Shop — global layout (navbar + footer)

  Scenario: Navbar is present on homepage
    When I am on "/pl_PL/"
    Then the response status code should be 200
    And the response should contain "watra-navbar"

  Scenario: Footer is present on homepage
    When I am on "/pl_PL/"
    Then the response should contain "watra-footer"

  Scenario: Navbar is present on events list
    When I am on "/pl_PL/taxons/wydarzenia"
    Then the response status code should be 200
    And the response should contain "watra-navbar"
    And the response should contain "watra-footer"

  Scenario: Footer tagline text is present
    When I am on "/pl_PL/"
    Then the response should contain "WATRA"
```

- [ ] **Step 2: Run the Behat suite**

```bash
sudo docker compose -f compose.yaml exec php bash bin/ci.sh 2>&1 | grep -A5 "layout\|navbar\|footer\|Behat"
```

Or run just the layout feature:

```bash
sudo docker compose -f compose.yaml exec php vendor/bin/behat features/shop/layout.feature --format=progress
```

Expected: 4 scenarios, 4 passed. If `the response should contain` step is undefined — check `behat.yml` for the Mink context; it should have `MinkContext` or similar with this step definition built in.

- [ ] **Step 3: Commit**

```bash
git add features/shop/layout.feature
git commit -m "test(fala-0): add Behat layout feature (navbar + footer present)"
```

---

## Task 9: Final verification

- [ ] **Step 1: Run full CI**

```bash
sudo docker compose -f compose.yaml exec php bash bin/ci.sh
```

Expected: PHPStan + ECS + PHPUnit + Behat all pass.

- [ ] **Step 2: Visual browser checklist**

Open each URL and verify:

| URL | Check |
|-----|-------|
| `http://localhost/pl_PL/` | Navbar transparent over hero, solid after scroll, DM Sans font, dark body |
| `http://localhost/pl_PL/taxons/wydarzenia` | Navbar solid (no hero), footer 3 columns |
| `http://localhost/pl_PL/events/<any-slug>` | Navbar transparent over event hero image |
| `http://localhost/pl_PL/login` | Footer present, no Sylius default header |
| Mobile (≤768px) | Burger button visible, links hidden, clicking burger opens links |

- [ ] **Step 3: Commit if any fixes were needed**

```bash
git add -p
git commit -m "fix(fala-0): visual fixes from browser review"
```
