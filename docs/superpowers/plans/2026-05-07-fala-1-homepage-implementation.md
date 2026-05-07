# Fala 1 — Implementation Plan: Homepage Redesign

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the WATRA homepage to 5 sections: enhanced hero (brand copy), Wyróżnione spotlight, Manifesto (Iskra/Płomień/Żar), Miasta city tabs, and CTA Przystań closing section.

**Architecture:** Existing `HomepageSpotlight` and `HomepageCity` Live Components keep their PHP logic — only their Twig markup is updated. Two new static components (`HomepageManifesto`, `cta.html.twig`) are added. All styles go into a new `_homepage.scss` partial. Hook order in `sylius_twig_hooks.yaml` is updated to insert the two new sections.

**Tech stack:** Symfony UX Live Components · Twig Hooks · SCSS (tokens from Fala 0) · Bootstrap 5 grid replaced by CSS grid in homepage context

**Prerequisite:** Fala 0 must be complete (`_tokens.scss` and `app.scss` must exist and build successfully).

---

## File map

| Action | Path | Responsibility |
|--------|------|---------------|
| Modify | `templates/shop/homepage/hero.html.twig` | New headline, subtitle, two CTAs, gradient background |
| Modify | `templates/components/HomepageSpotlight.html.twig` | Updated markup using WATRA tokens |
| Modify | `templates/components/HomepageCity.html.twig` | Pill-style tabs, updated card grid |
| Create | `templates/components/HomepageManifesto.html.twig` | Static Iskra/Płomień/Żar section |
| Create | `templates/shop/homepage/cta.html.twig` | Static Przystań CTA closing section |
| Create | `assets/shop/styles/_homepage.scss` | All homepage-specific styles |
| Modify | `assets/shop/styles/app.scss` | Add `@import "homepage"` |
| Modify | `config/packages/sylius_twig_hooks.yaml` | Add manifesto + cta hooks |
| Modify | `translations/messages.pl.yaml` | New PL translation keys |
| Modify | `translations/messages.en.yaml` | New EN translation keys |
| Modify | `features/shop/layout.feature` | Add homepage content smoke tests |

---

## Task 1: Homepage SCSS partial

**Files:**
- Create: `assets/shop/styles/_homepage.scss`
- Modify: `assets/shop/styles/app.scss`

- [ ] **Step 1: Create `_homepage.scss`**

```scss
// assets/shop/styles/_homepage.scss

// ─── Hero ───────────────────────────────────────────────
.watra-hero {
  position: relative;
  min-height: 480px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 100px 1.5rem 64px;
  background:
    radial-gradient(ellipse 80% 60% at 50% -10%, rgba(124, 58, 237, 0.35), transparent 70%),
    linear-gradient(160deg, #1a1030 0%, $watra-bg-base 60%);

  &__glow {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    width: 400px;
    height: 150px;
    background: radial-gradient(ellipse, rgba(124, 58, 237, 0.12), transparent 70%);
    pointer-events: none;
  }

  &__inner {
    position: relative;
    text-align: center;
    max-width: 640px;
    width: 100%;
  }

  &__label {
    color: $watra-text-subtle;
    font-size: 0.6rem;
    letter-spacing: 3px;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }

  &__title {
    font-size: clamp(1.8rem, 5vw, 2.6rem);
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 1rem;
    background: linear-gradient(135deg, $watra-text-primary 30%, $watra-purple-300 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  &__subtitle {
    color: $watra-text-muted;
    font-size: 1rem;
    line-height: 1.75;
    margin: 0 auto 1.75rem;
    max-width: 480px;
  }

  &__actions {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
  }

  &__cta-primary {
    background: $watra-purple-500;
    color: $watra-text-primary !important;
    padding: 0.75rem 1.75rem;
    border-radius: $watra-radius-sm;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;

    &:hover {
      background: $watra-purple-600;
      color: $watra-text-primary !important;
    }
  }

  &__cta-secondary {
    background: rgba(196, 181, 253, 0.08);
    border: 1px solid $watra-border-accent;
    color: $watra-purple-300 !important;
    padding: 0.75rem 1.5rem;
    border-radius: $watra-radius-sm;
    font-size: 0.875rem;
    text-decoration: none;
    transition: background 0.2s;

    &:hover {
      background: rgba(196, 181, 253, 0.14);
    }
  }
}

// ─── Section shared ─────────────────────────────────────
.watra-section-label {
  color: $watra-text-subtle;
  font-size: 0.6rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  font-weight: 600;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;

  &::before {
    content: '';
    display: inline-block;
    width: 20px;
    height: 1px;
    background: $watra-text-subtle;
    flex-shrink: 0;
  }
}

// ─── Spotlight ──────────────────────────────────────────
.watra-spotlight {
  padding: 2.5rem 0;

  &__grid {
    display: grid;
    grid-template-columns: 1.8fr 1fr;
    gap: 0.75rem;

    @media (max-width: 767.98px) {
      grid-template-columns: 1fr;
    }
  }

  &__featured {
    background: linear-gradient(135deg, $watra-purple-800, $watra-purple-600);
    border-radius: $watra-radius-md;
    padding: 1.25rem;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    text-decoration: none;
    transition: opacity 0.2s;

    &:hover { opacity: 0.9; }
  }

  &__featured-meta {
    color: $watra-purple-300;
    font-size: 0.62rem;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 0.375rem;
  }

  &__featured-title {
    color: $watra-text-primary;
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    line-height: 1.3;
  }

  &__featured-btn {
    display: inline-block;
    background: $watra-purple-500;
    color: $watra-text-primary;
    padding: 0.375rem 1rem;
    border-radius: $watra-radius-sm;
    font-size: 0.72rem;
    font-weight: 600;
    width: fit-content;
  }

  &__sidebar {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  &__small-card {
    background: $watra-bg-surface;
    border-radius: $watra-radius-md;
    padding: 0.875rem;
    flex: 1;
    border: 1px solid $watra-border-base;
    text-decoration: none;
    transition: border-color 0.2s;

    &:hover { border-color: $watra-border-accent; }
  }

  &__small-meta {
    color: $watra-purple-400;
    font-size: 0.58rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.25rem;
  }

  &__small-title {
    color: $watra-text-primary;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.375rem;
  }

  &__small-price {
    color: $watra-purple-300;
    font-size: 0.72rem;
    font-weight: 600;
  }
}

// ─── Manifesto ──────────────────────────────────────────
.watra-manifesto {
  background: $watra-bg-nav;
  border-top: 1px solid $watra-border-base;
  padding: 3rem 0;
  text-align: center;

  &__title {
    font-size: clamp(1.3rem, 3vw, 1.7rem);
    font-weight: 700;
    margin: 0.75rem 0 1rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.3;
  }

  &__subtitle {
    color: $watra-text-subtle;
    font-size: 0.9rem;
    line-height: 1.8;
    max-width: 480px;
    margin: 0 auto 2rem;
  }

  &__grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    max-width: 680px;
    margin: 0 auto;

    @media (max-width: 575.98px) {
      grid-template-columns: 1fr;
    }
  }

  &__card {
    background: rgba(124, 58, 237, 0.08);
    border: 1px solid $watra-border-accent;
    border-radius: $watra-radius-md;
    padding: 1.375rem 1rem;
  }

  &__icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    display: block;
  }

  &__card-title {
    color: $watra-purple-300;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 0.375rem;
  }

  &__card-desc {
    color: $watra-text-subtle;
    font-size: 0.72rem;
    line-height: 1.65;
    margin: 0;
  }
}

// ─── City tabs ──────────────────────────────────────────
.watra-city {
  padding: 2.5rem 0;

  &__tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
  }

  &__tab-label {
    background: $watra-bg-surface;
    color: $watra-text-subtle;
    padding: 0.375rem 1rem;
    border-radius: $watra-radius-pill;
    font-size: 0.78rem;
    border: 1px solid $watra-border-base;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
  }

  &__tab-input:checked + &__tab-label,
  &__tab-label:hover {
    background: $watra-purple-500;
    color: $watra-text-primary;
    border-color: $watra-purple-500;
  }

  &__more-link {
    display: block;
    text-align: center;
    margin-top: 1.5rem;
    color: $watra-purple-400;
    font-size: 0.82rem;
    text-decoration: none;
    border-bottom: 1px solid rgba(167, 139, 250, 0.3);
    width: fit-content;
    margin-left: auto;
    margin-right: auto;
    padding-bottom: 1px;

    &:hover { color: $watra-purple-300; }
  }
}

// ─── CTA Przystań ────────────────────────────────────────
.watra-cta-harbor {
  background: linear-gradient(135deg, rgba(124, 58, 237, 0.15), rgba(30, 27, 75, 0.3));
  border-top: 1px solid $watra-border-accent;
  padding: 3rem 1.5rem;
  text-align: center;

  &__icon {
    font-size: 1.75rem;
    margin-bottom: 0.75rem;
    display: block;
  }

  &__title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.625rem;
  }

  &__subtitle {
    color: $watra-text-subtle;
    font-size: 0.875rem;
    line-height: 1.75;
    max-width: 400px;
    margin: 0 auto 1.375rem;
  }

  &__btn {
    background: $watra-purple-500;
    color: $watra-text-primary !important;
    padding: 0.7rem 1.75rem;
    border-radius: $watra-radius-sm;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: background 0.2s;

    &:hover { background: $watra-purple-600; }
  }
}
```

- [ ] **Step 2: Add `@import "homepage"` to `app.scss`**

```scss
// assets/shop/styles/app.scss
@import "tokens";
@import "bootstrap-overrides";
@import "navbar";
@import "footer";
@import "homepage";
```

- [ ] **Step 3: Build check**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -10"
```

Expected: clean build, no SCSS errors.

- [ ] **Step 4: Commit**

```bash
git add assets/shop/styles/_homepage.scss assets/shop/styles/app.scss
git commit -m "feat(fala-1): add homepage SCSS partial"
```

---

## Task 2: Translation keys

**Files:**
- Modify: `translations/messages.pl.yaml`
- Modify: `translations/messages.en.yaml`

- [ ] **Step 1: Add Polish keys**

In `translations/messages.pl.yaml`, under `app.ui`, add/replace:

```yaml
        homepage_hero_title: 'Małe grupy. Prawdziwy kontakt. Żar który zostaje.'
        homepage_hero_subtitle: 'Watra to kameralne spotkania w różnych miejscach Polski — i online. Miejsca, w których iskra zamienia się w coś prawdziwego.'
        homepage_hero_cta_primary: 'Znajdź swoje wydarzenie'
        homepage_hero_cta_secondary: 'Czym jest Watra?'
        manifesto_label: 'Nasze korzenie'
        manifesto_title: 'Ognisko, wokół którego ludzie gromadzą się od zawsze.'
        manifesto_subtitle: 'Watra to słowiańskie słowo na ognisko. Wracamy do korzeni — do obecności, do prawdziwej rozmowy, do grup gdzie każdy głos ma znaczenie.'
        manifesto_spark_title: 'Iskra'
        manifesto_spark_desc: 'Każde wydarzenie zaczyna się od iskry — pierwszego spotkania, nowej rozmowy.'
        manifesto_flame_title: 'Płomień'
        manifesto_flame_desc: 'Samo wydarzenie — żywe, dynamiczne, które istnieje tylko tu i teraz.'
        manifesto_ember_title: 'Żar'
        manifesto_ember_desc: 'To co zostaje — znajomości, pomysły, nić między ludźmi która nie gaśnie.'
        cta_harbor_title: 'Szukasz swojej przystani?'
        cta_harbor_subtitle: 'Watra to miejsce dla tych, którzy czują zew — do prawdziwego kontaktu, do rozmów które zostają.'
        cta_harbor_cta: 'Przeglądaj wydarzenia'
```

- [ ] **Step 2: Add English keys**

In `translations/messages.en.yaml`, under `app.ui`, add:

```yaml
        homepage_hero_title: 'Small groups. Real connection. Warmth that stays.'
        homepage_hero_subtitle: 'WATRA brings intimate gatherings across Poland — and online. Places where a spark turns into something real.'
        homepage_hero_cta_primary: 'Find your event'
        homepage_hero_cta_secondary: 'What is WATRA?'
        manifesto_label: 'Our roots'
        manifesto_title: 'The bonfire people have gathered around since forever.'
        manifesto_subtitle: 'Watra is a Slavic word for bonfire. We return to roots — to presence, to real conversation, to groups where every voice matters.'
        manifesto_spark_title: 'Spark'
        manifesto_spark_desc: 'Every event begins with a spark — a first meeting, a new conversation.'
        manifesto_flame_title: 'Flame'
        manifesto_flame_desc: 'The event itself — alive, dynamic, existing only here and now.'
        manifesto_ember_title: 'Ember'
        manifesto_ember_desc: 'What remains — friendships, ideas, threads between people that never go cold.'
        cta_harbor_title: 'Looking for your harbor?'
        cta_harbor_subtitle: 'WATRA is for those who feel the call — for real connection, for conversations that stay with you.'
        cta_harbor_cta: 'Browse events'
```

- [ ] **Step 3: Commit**

```bash
git add translations/messages.pl.yaml translations/messages.en.yaml
git commit -m "feat(fala-1): add homepage translation keys (hero, manifesto, cta)"
```

---

## Task 3: Hero template

**Files:**
- Modify: `templates/shop/homepage/hero.html.twig`

- [ ] **Step 1: Replace hero template**

```twig
{# templates/shop/homepage/hero.html.twig #}
<div class="watra-hero">
    <div class="watra-hero__glow"></div>
    <div class="watra-hero__inner">
        <p class="watra-hero__label">🔥 {{ 'app.ui.upcoming_events'|trans }}</p>
        <h1 class="watra-hero__title">{{ 'app.ui.homepage_hero_title'|trans }}</h1>
        <p class="watra-hero__subtitle">{{ 'app.ui.homepage_hero_subtitle'|trans }}</p>
        <div class="watra-hero__actions">
            <a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}"
               class="watra-hero__cta-primary">
                {{ 'app.ui.homepage_hero_cta_primary'|trans }} →
            </a>
            <a href="#watra-manifesto" class="watra-hero__cta-secondary">
                {{ 'app.ui.homepage_hero_cta_secondary'|trans }}
            </a>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Add `watra-has-transparent-nav` body class for homepage**

Check `config/packages/sylius_twig_hooks.yaml` for a `sylius_shop.base#body_classes` hook entry. If none exists, add:

```yaml
        'sylius_shop.base#body_classes':
            defaults:
                enabled: true
            homepage_class:
                template: 'shop/homepage/body_class.html.twig'
                priority: 10
```

Then create `templates/shop/homepage/body_class.html.twig`:
```twig
{# templates/shop/homepage/body_class.html.twig #}
{% if app.request.attributes.get('_route') == 'sylius_shop_homepage' %}watra-has-transparent-nav{% endif %}
```

- [ ] **Step 3: Build and visual check**

```bash
sudo docker compose -f compose.yaml exec php bash -c "cd /app && npm run build 2>&1 | tail -5"
```

Open `http://localhost/pl_PL/`. Verify: new headline text, gradient, two CTA buttons visible.

- [ ] **Step 4: Commit**

```bash
git add templates/shop/homepage/hero.html.twig templates/shop/homepage/body_class.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(fala-1): redesign hero section with brand copy and gradient"
```

---

## Task 4: HomepageSpotlight markup update

**Files:**
- Modify: `templates/components/HomepageSpotlight.html.twig`

- [ ] **Step 1: Replace template markup** (PHP logic in the backing class is unchanged — only HTML)

```twig
{# templates/components/HomepageSpotlight.html.twig #}
<section class="container">
    <p class="watra-section-label">✨ {{ 'app.ui.upcoming_events'|trans }}</p>

    {% if featured is not null %}
        <div class="watra-spotlight__grid">
            <a href="{{ path('sylius_shop_product_show', {_locale: app.request.locale, slug: featured.slug}) }}"
               class="watra-spotlight__featured">
                <p class="watra-spotlight__featured-meta">
                    {{ ('app.enum.event_type.' ~ featured.eventType.value)|trans }}
                    {% if featured.city %} · {{ featured.city.name }}{% endif %}
                </p>
                <h2 class="watra-spotlight__featured-title">{{ featured.name }}</h2>
                <span class="watra-spotlight__featured-btn">{{ 'app.ui.homepage_hero_cta_primary'|trans }} →</span>
            </a>

            <div class="watra-spotlight__sidebar">
                {% for product in upcoming %}
                    <a href="{{ path('sylius_shop_product_show', {_locale: app.request.locale, slug: product.slug}) }}"
                       class="watra-spotlight__small-card">
                        <p class="watra-spotlight__small-meta">
                            {{ ('app.enum.event_type.' ~ product.eventType.value)|trans }}
                            {% if product.isOnline %} · Online{% elseif product.city %} · {{ product.city.name }}{% endif %}
                        </p>
                        <p class="watra-spotlight__small-title">{{ product.name }}</p>
                        <span class="watra-spotlight__small-price">
                            {% if this.minPrice(product) %}
                                {{ (this.minPrice(product) / 100)|number_format(0, ',', ' ') }} zł
                            {% else %}
                                {{ 'app.ui.free'|trans }}
                            {% endif %}
                        </span>
                    </a>
                {% else %}
                    <p class="text-muted">{{ 'app.ui.no_upcoming_dates'|trans }}</p>
                {% endfor %}
            </div>
        </div>
    {% else %}
        <div class="row g-4">
            {% for product in upcoming %}
                <div class="col-12 col-sm-6 col-lg-4">
                    {{ component('EventCard', { product: product }) }}
                </div>
            {% else %}
                <p style="color: var(--watra-text-muted, #94a3b8);">{{ 'app.ui.no_upcoming_dates'|trans }}</p>
            {% endfor %}
        </div>
    {% endif %}
</section>
```

> **Note:** If `this.minPrice(product)` doesn't match the actual method signature in `HomepageSpotlightComponent.php`, check the class and adjust. The price display pattern mirrors what `EventCard` already uses.

- [ ] **Step 2: Visual check**

Open `http://localhost/pl_PL/`. Verify spotlight renders — large card on left, smaller on right.

- [ ] **Step 3: Commit**

```bash
git add templates/components/HomepageSpotlight.html.twig
git commit -m "feat(fala-1): update HomepageSpotlight markup with WATRA design tokens"
```

---

## Task 5: HomepageManifesto — new component

**Files:**
- Create: `templates/components/HomepageManifesto.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

- [ ] **Step 1: Create the template**

```twig
{# templates/components/HomepageManifesto.html.twig #}
<section class="watra-manifesto" id="watra-manifesto">
    <div class="container">
        <p class="watra-section-label" style="justify-content:center;">🌱 {{ 'app.ui.manifesto_label'|trans }}</p>
        <h2 class="watra-manifesto__title">{{ 'app.ui.manifesto_title'|trans }}</h2>
        <p class="watra-manifesto__subtitle">{{ 'app.ui.manifesto_subtitle'|trans }}</p>

        <div class="watra-manifesto__grid">
            <div class="watra-manifesto__card">
                <span class="watra-manifesto__icon">✨</span>
                <p class="watra-manifesto__card-title">{{ 'app.ui.manifesto_spark_title'|trans }}</p>
                <p class="watra-manifesto__card-desc">{{ 'app.ui.manifesto_spark_desc'|trans }}</p>
            </div>
            <div class="watra-manifesto__card">
                <span class="watra-manifesto__icon">🔥</span>
                <p class="watra-manifesto__card-title">{{ 'app.ui.manifesto_flame_title'|trans }}</p>
                <p class="watra-manifesto__card-desc">{{ 'app.ui.manifesto_flame_desc'|trans }}</p>
            </div>
            <div class="watra-manifesto__card">
                <span class="watra-manifesto__icon">🌡️</span>
                <p class="watra-manifesto__card-title">{{ 'app.ui.manifesto_ember_title'|trans }}</p>
                <p class="watra-manifesto__card-desc">{{ 'app.ui.manifesto_ember_desc'|trans }}</p>
            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Register component as an anonymous component**

`HomepageManifesto` is a static template — no PHP class needed. Register it as an anonymous Twig component in `config/packages/twig_component.yaml` (or equivalent):

```yaml
twig_component:
    anonymous_template_directory: 'components/'
```

This is likely already set. If not, check `config/packages/twig.yaml` for existing anonymous component config.

Then hook it in `config/packages/sylius_twig_hooks.yaml`, inside `'sylius_shop.homepage.index'`:

```yaml
            watra_manifesto:
                component: 'HomepageManifesto'
                priority: 150
```

(Between `watra_spotlight` at 200 and `watra_city` at 100.)

- [ ] **Step 3: Visual check**

Open `http://localhost/pl_PL/`. Scroll past Spotlight — the Manifesto section should appear with dark `#13111f` background, three cards.

- [ ] **Step 4: Commit**

```bash
git add templates/components/HomepageManifesto.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(fala-1): add HomepageManifesto section (Iskra/Płomień/Żar)"
```

---

## Task 6: HomepageCity markup update

**Files:**
- Modify: `templates/components/HomepageCity.html.twig`

- [ ] **Step 1: Replace template markup**

```twig
{# templates/components/HomepageCity.html.twig #}
{% set tabs = [
    { id: 'krakow',   label: 'Kraków' },
    { id: 'warszawa', label: 'Warszawa' },
    { id: 'online',   label: '🌐 Online' },
] %}

<section class="container">
    <p class="watra-section-label">📍 {{ 'app.ui.events_by_city'|trans }}</p>

    <div class="watra-city">
        {# Hidden radio inputs for CSS-only tab switching #}
        {% for tab in tabs %}
            <input type="radio"
                   name="city-tab"
                   id="city-tab-{{ tab.id }}"
                   class="watra-city__tab-input d-none"
                   {{ loop.first ? 'checked' : '' }}>
        {% endfor %}

        <div class="watra-city__tabs">
            {% for tab in tabs %}
                <label for="city-tab-{{ tab.id }}" class="watra-city__tab-label">
                    {{ tab.label }}
                </label>
            {% endfor %}
        </div>

        <style>
            {% for tab in tabs %}
            #city-tab-{{ tab.id }}:checked ~ .watra-city__tabs label[for="city-tab-{{ tab.id }}"] {
                background: #7c3aed;
                color: #f1f5f9;
                border-color: #7c3aed;
            }
            #city-tab-{{ tab.id }}:checked ~ .city-panels #city-panel-{{ tab.id }} { display: block; }
            {% endfor %}
            .city-panels > div { display: none; }
        </style>

        <div class="city-panels">
            {% for tab in tabs %}
                <div id="city-panel-{{ tab.id }}">
                    <div class="row g-3">
                        {% for product in eventsByCity[tab.id] %}
                            <div class="col-12 col-sm-6 col-lg-3">
                                {{ component('EventCard', { product: product }) }}
                            </div>
                        {% else %}
                            <p class="col-12" style="color:#64748b;">{{ 'app.ui.no_events_in_city'|trans }}</p>
                        {% endfor %}
                    </div>
                </div>
            {% endfor %}
        </div>

        <a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}"
           class="watra-city__more-link">
            {{ 'app.ui.all_events'|trans }} →
        </a>
    </div>
</section>
```

- [ ] **Step 2: Visual check**

Open `http://localhost/pl_PL/`. City tabs should now show as pills, active tab purple.

- [ ] **Step 3: Commit**

```bash
git add templates/components/HomepageCity.html.twig
git commit -m "feat(fala-1): update HomepageCity with pill-style tabs"
```

---

## Task 7: CTA Przystań — new template + hook

**Files:**
- Create: `templates/shop/homepage/cta.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

- [ ] **Step 1: Create the template**

```twig
{# templates/shop/homepage/cta.html.twig #}
<section class="watra-cta-harbor">
    <span class="watra-cta-harbor__icon">⚓</span>
    <h2 class="watra-cta-harbor__title">{{ 'app.ui.cta_harbor_title'|trans }}</h2>
    <p class="watra-cta-harbor__subtitle">{{ 'app.ui.cta_harbor_subtitle'|trans }}</p>
    <a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}"
       class="watra-cta-harbor__btn">
        {{ 'app.ui.cta_harbor_cta'|trans }} →
    </a>
</section>
```

- [ ] **Step 2: Add hook entry**

In `config/packages/sylius_twig_hooks.yaml`, inside `'sylius_shop.homepage.index'`, add after `watra_city`:

```yaml
            watra_cta:
                template: 'shop/homepage/cta.html.twig'
                priority: 50
```

- [ ] **Step 3: Visual check**

Open `http://localhost/pl_PL/`. Scroll to bottom — CTA Przystań section should appear above the footer.

- [ ] **Step 4: Commit**

```bash
git add templates/shop/homepage/cta.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(fala-1): add CTA Przystań closing section to homepage"
```

---

## Task 8: Behat tests + final verification

**Files:**
- Modify: `features/shop/layout.feature`

- [ ] **Step 1: Add homepage content scenarios**

Append to `features/shop/layout.feature`:

```gherkin
  Scenario: Homepage hero has new brand copy
    When I am on "/pl_PL/"
    Then the response should contain "watra-hero"
    And the response should contain "watra-manifesto"
    And the response should contain "watra-cta-harbor"

  Scenario: Homepage manifesto section is present
    When I am on "/pl_PL/"
    Then the response should contain "Iskra"
    And the response should contain "Płomień"
    And the response should contain "Żar"
```

- [ ] **Step 2: Run Behat**

```bash
sudo docker compose -f compose.yaml exec php vendor/bin/behat features/shop/layout.feature --format=progress
```

Expected: all scenarios pass.

- [ ] **Step 3: Run full CI**

```bash
sudo docker compose -f compose.yaml exec php bash bin/ci.sh
```

Expected: PHPStan + ECS + PHPUnit + Behat all pass.

- [ ] **Step 4: Full visual checklist**

| Check | Expected |
|-------|----------|
| `/pl_PL/` — hero | Gradient background, brand headline, 2 CTA buttons |
| `/pl_PL/` — scroll | Spotlight with featured event, then Manifesto with 3 cards |
| `/pl_PL/` — scroll | City tabs as pills, "Przeglądaj →" link |
| `/pl_PL/` — bottom | CTA Przystań above footer |
| `/pl_PL/` — navbar | Transparent at top, solid after scroll |
| Mobile (≤768px) | Manifesto cards stack vertically, spotlight single column |
| "Czym jest Watra?" CTA | Scrolls to `#watra-manifesto` anchor |

- [ ] **Step 5: Commit**

```bash
git add features/shop/layout.feature
git commit -m "test(fala-1): add Behat scenarios for homepage sections"
```
