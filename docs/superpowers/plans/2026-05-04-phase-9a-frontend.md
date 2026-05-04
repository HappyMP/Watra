# Phase 9A — Public Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the core public browsing experience — homepage with Spotlight + city tabs, a reusable EventCard component, and an event detail page override — all via Sylius Twig Hooks with no custom HomeController.

**Architecture:** Twig Hooks replace Sylius defaults on the homepage (`sylius_shop.homepage.index`) and product show page (`sylius_shop.product.show.*`). Data is fetched by regular (non-live) Twig Components using constructor-injected repositories. A thin `CartController::addVariant` action enables per-variant "Zapisz się" without JavaScript.

**Tech Stack:** Sylius 2.2 Twig Hooks, Symfony UX TwigComponent (`#[AsTwigComponent]`), Doctrine ORM QueryBuilder, Bootstrap 5.

---

## File Map

| File | Action |
|------|--------|
| `src/Repository/Product/ProductRepository.php` | Create — 4 custom query methods |
| `src/Twig/Component/EventCard.php` | Create — regular Twig Component |
| `src/Twig/Component/HomepageSpotlightComponent.php` | Create — fetches featured + upcoming |
| `src/Twig/Component/HomepageCityComponent.php` | Create — fetches events by city/online |
| `src/Controller/Shop/CartController.php` | Create — addVariant action |
| `src/Controller/Shop/StaticPageController.php` | Create — regulamin + privacy |
| `templates/components/EventCard.html.twig` | Create |
| `templates/components/HomepageSpotlight.html.twig` | Create |
| `templates/components/HomepageCity.html.twig` | Create |
| `templates/shop/homepage/hero.html.twig` | Create |
| `templates/shop/product/show/watra_hero.html.twig` | Create |
| `templates/shop/product/show/watra_description.html.twig` | Create |
| `templates/shop/product/show/watra_terms.html.twig` | Create |
| `templates/shop/static/regulamin.html.twig` | Create |
| `templates/shop/static/privacy.html.twig` | Create |
| `config/packages/sylius_twig_hooks.yaml` | Modify — homepage + product show hooks |
| `config/routes/app_shop.yaml` | Create — cart add-variant + static routes |
| `translations/messages.pl.yaml` | Modify |
| `translations/messages.en.yaml` | Modify |
| `tests/Repository/ProductRepositoryTest.php` | Create |
| `tests/Functional/CartControllerTest.php` | Create |

---

## Task 1: ProductRepository — Custom Query Methods

**Files:**
- Create: `src/Repository/Product/ProductRepository.php`
- Create: `tests/Repository/ProductRepositoryTest.php`

Sylius's default `ProductRepositoryInterface` doesn't know about `startsAt`, `eventStatus`, `city`, or `isOnline`. We need custom DQL queries.

- [ ] **Step 1: Create the repository**

```php
<?php

declare(strict_types=1);

namespace App\Repository\Product;

use App\Entity\Product\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Sylius\Component\Channel\Context\ChannelContextInterface;

final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Products tagged with taxon code "featured", published, with a future startsAt.
     * Returns max $limit results ordered by nearest startsAt.
     *
     * @return Product[]
     */
    public function findFeatured(string $channelCode, string $locale, int $limit = 1): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.productTaxons', 'pt')
            ->innerJoin('pt.taxon', 'taxon')
            ->innerJoin('p.variants', 'v')
            ->innerJoin('p.channels', 'ch')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('taxon.code = :taxonCode')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('taxonCode', 'featured')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addOrderBy('v.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Published products with at least one future variant, ordered by nearest startsAt.
     *
     * @param int[] $excludeIds
     * @return Product[]
     */
    public function findUpcoming(string $channelCode, string $locale, int $limit = 4, array $excludeIds = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.variants', 'v')
            ->innerJoin('p.channels', 'ch')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addOrderBy('v.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->distinct();

        if (!empty($excludeIds)) {
            $qb->andWhere('p.id NOT IN (:excludeIds)')->setParameter('excludeIds', $excludeIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Published products in a given city name (case-insensitive).
     *
     * @return Product[]
     */
    public function findByCity(string $cityName, string $channelCode, string $locale, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.channels', 'ch')
            ->innerJoin('p.city', 'city')
            ->innerJoin('p.variants', 'v')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('LOWER(city.name) = LOWER(:cityName)')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('cityName', $cityName)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addOrderBy('v.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->distinct()
            ->getQuery()
            ->getResult();
    }

    /**
     * Published online events (isOnline = true) with future variants.
     *
     * @return Product[]
     */
    public function findOnline(string $channelCode, string $locale, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.channels', 'ch')
            ->innerJoin('p.variants', 'v')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('p.isOnline = true')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addOrderBy('v.startsAt', 'ASC')
            ->setMaxResults($limit)
            ->distinct()
            ->getQuery()
            ->getResult();
    }
}
```

- [ ] **Step 2: Register the repository in services.yaml**

Open `config/services.yaml`. Under `services:`, add after `App\Repository\Customer\InterestRepository`:

```yaml
    App\Repository\Product\ProductRepository: ~
```

- [ ] **Step 3: Verify container wires correctly**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
sudo docker compose -f compose.yaml exec php bin/console debug:container App\\Repository\\Product\\ProductRepository
```

Expected: service definition printed without errors.

- [ ] **Step 4: Create functional tests**

Create `tests/Repository/ProductRepositoryTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\Product\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repo = static::getContainer()->get(ProductRepository::class);
    }

    public function testFindUpcomingReturnsArray(): void
    {
        $results = $this->repo->findUpcoming('WATRA', 'pl_PL', 4);
        self::assertIsArray($results);
    }

    public function testFindUpcomingRespectsLimit(): void
    {
        $results = $this->repo->findUpcoming('WATRA', 'pl_PL', 2);
        self::assertLessThanOrEqual(2, count($results));
    }

    public function testFindByCityReturnsArray(): void
    {
        $results = $this->repo->findByCity('Kraków', 'WATRA', 'pl_PL', 4);
        self::assertIsArray($results);
    }

    public function testFindOnlineReturnsArray(): void
    {
        $results = $this->repo->findOnline('WATRA', 'pl_PL', 4);
        self::assertIsArray($results);
    }

    public function testFindFeaturedReturnsArray(): void
    {
        $results = $this->repo->findFeatured('WATRA', 'pl_PL', 1);
        self::assertIsArray($results);
    }
}
```

- [ ] **Step 5: Run tests**

```bash
sudo docker compose -f compose.yaml exec php vendor/bin/phpunit tests/Repository/ProductRepositoryTest.php
```

Expected: `OK (5 tests, 5+ assertions)`.

- [ ] **Step 6: Commit**

```bash
git add src/Repository/Product/ProductRepository.php tests/Repository/ProductRepositoryTest.php config/services.yaml
git commit -m "feat(phase-9a): add ProductRepository with upcoming/featured/city/online queries"
```

---

## Task 2: EventCard Twig Component

**Files:**
- Create: `src/Twig/Component/EventCard.php`
- Create: `templates/components/EventCard.html.twig`

The reusable card used everywhere. Shows gradient/image background, event type badge, title, next date, city, price, and InterestButton.

- [ ] **Step 1: Create the PHP class**

```php
<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Product\Product;
use App\Entity\Product\ProductVariant;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('EventCard')]
final class EventCard
{
    public Product $product;
    public string $size = 'normal';

    public function __construct(
        private readonly ChannelContextInterface $channelContext,
    ) {}

    public function getNextVariant(): ?ProductVariant
    {
        $now = new \DateTimeImmutable();
        $next = null;

        foreach ($this->product->getVariants() as $variant) {
            /** @var ProductVariant $variant */
            if (!$variant->isEnabled()) {
                continue;
            }
            $startsAt = $variant->getStartsAt();
            if ($startsAt === null || $startsAt <= $now) {
                continue;
            }
            if ($next === null || $startsAt < $next->getStartsAt()) {
                $next = $variant;
            }
        }

        return $next;
    }

    public function getMinPrice(): ?int
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $min = null;

        foreach ($this->product->getVariants() as $variant) {
            /** @var ProductVariant $variant */
            if (!$variant->isEnabled()) {
                continue;
            }
            $pricing = $variant->getChannelPricingForChannel($channel);
            if ($pricing === null) {
                continue;
            }
            $price = $pricing->getPrice();
            if ($price !== null && ($min === null || $price < $min)) {
                $min = $price;
            }
        }

        return $min;
    }

    public function getBackgroundImageUrl(): ?string
    {
        $images = $this->product->getImages();
        if ($images->isEmpty()) {
            return null;
        }

        return '/media/image/' . $images->first()->getPath();
    }
}
```

- [ ] **Step 2: Create the template**

Create `templates/components/EventCard.html.twig`:

```twig
{% set nextVariant = this.nextVariant %}
{% set minPrice = this.minPrice %}
{% set bgUrl = this.backgroundImageUrl %}
{% set isLarge = size == 'large' %}

<a href="{{ path('sylius_shop_product_show', {_locale: app.request.locale, slug: product.slug}) }}"
   class="text-decoration-none d-block h-100">
  <div class="event-card position-relative overflow-hidden rounded-3 h-100"
       style="min-height: {{ isLarge ? '320px' : '200px' }};
              background: {% if bgUrl %}url('{{ bgUrl }}') center/cover no-repeat, {% endif %}linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4c1d95 100%);">

    {# Dark overlay #}
    <div class="position-absolute top-0 start-0 w-100 h-100"
         style="background: linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.3) 60%, rgba(0,0,0,0.1) 100%);">
    </div>

    {# Type badge + interest button #}
    <div class="position-absolute top-0 start-0 w-100 d-flex justify-content-between align-items-start p-2">
      <span class="badge rounded-pill" style="background:rgba(255,255,255,0.2);font-size:0.7rem;">
        {{ ('app.enum.event_type.' ~ product.eventType.value)|trans }}
      </span>
      {{ component('InterestButton', { productId: product.id }) }}
    </div>

    {# Content at bottom #}
    <div class="position-absolute bottom-0 start-0 w-100 p-3 text-white">
      {% if nextVariant %}
        <div class="small mb-1" style="opacity:0.8;">
          🗓 {{ nextVariant.startsAt|date('d.m.Y H:i') }}
          {% if product.isOnline %}
            &nbsp;·&nbsp; 🌐 Online
          {% elseif product.city %}
            &nbsp;·&nbsp; 📍 {{ product.city.name }}
          {% endif %}
        </div>
      {% else %}
        <div class="small mb-1" style="opacity:0.6;">{{ 'app.ui.no_upcoming_dates'|trans }}</div>
      {% endif %}

      <div class="fw-bold {{ isLarge ? 'fs-5' : 'fs-6' }} lh-sm mb-2">{{ product.name }}</div>

      <div class="d-flex justify-content-between align-items-center">
        {% if minPrice %}
          <span class="fw-bold" style="color:#86efac;">{{ (minPrice / 100)|number_format(0, ',', ' ') }} zł</span>
        {% else %}
          <span style="color:#86efac;">{{ 'app.ui.free'|trans }}</span>
        {% endif %}
        <span class="btn btn-sm btn-light py-1 px-2" style="font-size:0.75rem;">Zobacz →</span>
      </div>
    </div>
  </div>
</a>
```

- [ ] **Step 3: Clear cache and verify autoload**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
sudo docker compose -f compose.yaml exec php bin/console debug:container App\\Twig\\Component\\EventCard
```

Expected: service definition printed.

- [ ] **Step 4: Commit**

```bash
git add src/Twig/Component/EventCard.php templates/components/EventCard.html.twig
git commit -m "feat(phase-9a): add EventCard Twig Component"
```

---

## Task 3: Homepage — Hero + Disable Sylius Defaults

**Files:**
- Create: `templates/shop/homepage/hero.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

- [ ] **Step 1: Create the hero template**

Create `templates/shop/homepage/hero.html.twig`:

```twig
<section class="text-white text-center py-5"
         style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4c1d95 100%); min-height: 280px; display:flex; align-items:center;">
  <div class="container">
    <h1 class="display-5 fw-bold mb-3">{{ 'app.ui.homepage_hero_title'|trans }}</h1>
    <p class="lead mb-4" style="opacity:0.85;">{{ 'app.ui.homepage_hero_subtitle'|trans }}</p>
    <a href="{{ path('sylius_shop_product_index', {_locale: app.request.locale, slug: 'wydarzenia'}) }}"
       class="btn btn-light btn-lg px-4">
      {{ 'app.ui.homepage_hero_cta'|trans }} →
    </a>
  </div>
</section>
```

- [ ] **Step 2: Add homepage hooks to sylius_twig_hooks.yaml**

Add after the existing hooks in `config/packages/sylius_twig_hooks.yaml`:

```yaml
        'sylius_shop.homepage.index':
            banner:
                enabled: false
            latest_deals:
                enabled: false
            new_collection:
                enabled: false
            latest_products:
                enabled: false
            watra_hero:
                template: 'shop/homepage/hero.html.twig'
                priority: 300
```

- [ ] **Step 3: Clear cache and verify homepage renders**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
```

Open http://localhost in browser, navigate to `/pl_PL/`. Expected: Sylius default homepage content is gone, dark hero section is visible.

- [ ] **Step 4: Commit**

```bash
git add templates/shop/homepage/hero.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(phase-9a): add homepage hero, disable Sylius default homepage hooks"
```

---

## Task 4: HomepageSpotlightComponent

**Files:**
- Create: `src/Twig/Component/HomepageSpotlightComponent.php`
- Create: `templates/components/HomepageSpotlight.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

Fetches the featured event (taxon `featured`) + 4 upcoming. Falls back to 5 upcoming if no featured.

- [ ] **Step 1: Create the PHP class**

```php
<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Repository\Product\ProductRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('HomepageSpotlight')]
final class HomepageSpotlightComponent
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {}

    #[ExposeInTemplate]
    public function getFeatured(): ?object
    {
        $results = $this->productRepository->findFeatured(
            $this->channelContext->getChannel()->getCode(),
            $this->localeContext->getLocaleCode(),
            1,
        );

        return $results[0] ?? null;
    }

    /** @return object[] */
    #[ExposeInTemplate]
    public function getUpcoming(): array
    {
        $featured = $this->getFeatured();
        $excludeIds = $featured !== null ? [$featured->getId()] : [];

        return $this->productRepository->findUpcoming(
            $this->channelContext->getChannel()->getCode(),
            $this->localeContext->getLocaleCode(),
            4,
            $excludeIds,
        );
    }
}
```

- [ ] **Step 2: Create the template**

Create `templates/components/HomepageSpotlight.html.twig`:

```twig
<section class="container py-5">
  <h2 class="h4 fw-bold mb-4">{{ 'app.ui.upcoming_events'|trans }}</h2>

  {% if featured is not null %}
    {# Two-column: large featured on left, small list on right #}
    <div class="row g-4">
      <div class="col-12 col-lg-7">
        {{ component('EventCard', { product: featured, size: 'large' }) }}
      </div>
      <div class="col-12 col-lg-5">
        <div class="d-flex flex-column gap-3 h-100">
          {% for product in upcoming %}
            {{ component('EventCard', { product: product }) }}
          {% else %}
            <p class="text-muted">{{ 'app.ui.no_upcoming_dates'|trans }}</p>
          {% endfor %}
        </div>
      </div>
    </div>
  {% else %}
    {# No featured: show 5 upcoming in a grid #}
    {% set allUpcoming = this.upcoming %}
    <div class="row g-4">
      {% for product in allUpcoming %}
        <div class="col-12 col-sm-6 col-lg-4">
          {{ component('EventCard', { product: product }) }}
        </div>
      {% else %}
        <p class="text-muted col-12">{{ 'app.ui.no_upcoming_dates'|trans }}</p>
      {% endfor %}
    </div>
  {% endif %}
</section>
```

- [ ] **Step 3: Register hookable in sylius_twig_hooks.yaml**

Add `watra_spotlight` under `sylius_shop.homepage.index` (after `watra_hero`):

```yaml
            watra_spotlight:
                component: 'HomepageSpotlight'
                priority: 200
```

- [ ] **Step 4: Clear cache and verify**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
```

Open `/pl_PL/` — expected: spotlight section visible below hero.

- [ ] **Step 5: Commit**

```bash
git add src/Twig/Component/HomepageSpotlightComponent.php templates/components/HomepageSpotlight.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(phase-9a): add HomepageSpotlightComponent with featured + upcoming events"
```

---

## Task 5: HomepageCityComponent

**Files:**
- Create: `src/Twig/Component/HomepageCityComponent.php`
- Create: `templates/components/HomepageCity.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

City tabs using CSS radio trick — no JavaScript.

- [ ] **Step 1: Create the PHP class**

```php
<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Product\Product;
use App\Repository\Product\ProductRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsTwigComponent('HomepageCity')]
final class HomepageCityComponent
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {}

    /** @return array<string, Product[]> Keys: 'krakow', 'warszawa', 'online' */
    #[ExposeInTemplate]
    public function getEventsByCity(): array
    {
        $channel = $this->channelContext->getChannel()->getCode();
        $locale = $this->localeContext->getLocaleCode();

        return [
            'krakow'   => $this->productRepository->findByCity('Kraków', $channel, $locale, 4),
            'warszawa' => $this->productRepository->findByCity('Warszawa', $channel, $locale, 4),
            'online'   => $this->productRepository->findOnline($channel, $locale, 4),
        ];
    }
}
```

- [ ] **Step 2: Create the template**

Create `templates/components/HomepageCity.html.twig`:

```twig
{% set tabs = [
    { id: 'krakow',   label: 'Kraków' },
    { id: 'warszawa', label: 'Warszawa' },
    { id: 'online',   label: '🌐 Online' },
] %}

<section class="container py-5">
  <h2 class="h4 fw-bold mb-4">{{ 'app.ui.events_by_city'|trans }}</h2>

  {# CSS-only tabs: hidden radios control which panel is visible #}
  {% for tab in tabs %}
    <input type="radio" name="city-tab" id="tab-{{ tab.id }}" class="d-none"
           {{ loop.first ? 'checked' : '' }}>
  {% endfor %}

  <div class="mb-4 d-flex gap-2">
    {% for tab in tabs %}
      <label for="tab-{{ tab.id }}"
             class="btn btn-sm"
             style="cursor:pointer;">
        {{ tab.label }}
      </label>
    {% endfor %}
  </div>

  <style>
    {% for tab in tabs %}
    #tab-{{ tab.id }}:checked ~ .city-panels #panel-{{ tab.id }} { display: block; }
    #tab-{{ tab.id }}:checked ~ .mb-4 label[for="tab-{{ tab.id }}"] { background:#4f46e5; color:white; border-color:#4f46e5; }
    {% endfor %}
    .city-panels > div { display: none; }
  </style>

  <div class="city-panels">
    {% for tab in tabs %}
      <div id="panel-{{ tab.id }}">
        <div class="row g-3">
          {% for product in eventsByCity[tab.id] %}
            <div class="col-12 col-sm-6 col-lg-3">
              {{ component('EventCard', { product: product }) }}
            </div>
          {% else %}
            <p class="text-muted col-12">{{ 'app.ui.no_events_in_city'|trans }}</p>
          {% endfor %}
        </div>
      </div>
    {% endfor %}
  </div>
</section>
```

- [ ] **Step 3: Register hookable in sylius_twig_hooks.yaml**

Add `watra_city` under `sylius_shop.homepage.index` after `watra_spotlight`:

```yaml
            watra_city:
                component: 'HomepageCity'
                priority: 100
```

- [ ] **Step 4: Clear cache and verify**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
```

Open `/pl_PL/` — expected: city tabs section below spotlight, CSS tab switching works.

- [ ] **Step 5: Commit**

```bash
git add src/Twig/Component/HomepageCityComponent.php templates/components/HomepageCity.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(phase-9a): add HomepageCityComponent with Kraków/Warszawa/Online tabs"
```

---

## Task 6: Product Show — WATRA Hero

**Files:**
- Create: `templates/shop/product/show/watra_hero.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

Full-width dark hero replacing the default breadcrumb header on the product show page.

- [ ] **Step 1: Create the hero template**

Create `templates/shop/product/show/watra_hero.html.twig`:

```twig
{% set product = hookable_metadata.context.product %}
{% set bgUrl = null %}
{% if product.images is not empty %}
  {% set bgUrl = '/media/image/' ~ product.images.first().path %}
{% endif %}

<div class="text-white py-5"
     style="background: {% if bgUrl %}url('{{ bgUrl }}') center/cover no-repeat, {% endif %}linear-gradient(135deg, #1e1b4b 0%, #312e81 60%, #4c1d95 100%); position:relative;">
  <div class="position-absolute top-0 start-0 w-100 h-100"
       style="background:rgba(0,0,0,0.5);"></div>
  <div class="container position-relative">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <span class="badge rounded-pill" style="background:rgba(255,255,255,0.2);">
        {{ ('app.enum.event_type.' ~ product.eventType.value)|trans }}
      </span>
      {{ component('InterestButton', { productId: product.id }) }}
    </div>
    <h1 class="display-6 fw-bold mb-2">{{ product.name }}</h1>
    <div class="d-flex gap-3 flex-wrap" style="opacity:0.85;font-size:0.95rem;">
      {% if product.isOnline %}
        <span>🌐 Online</span>
      {% elseif product.city %}
        <span>📍 {{ product.city.name }}</span>
      {% endif %}
      {% if product.defaultVenue %}
        <span>🏢 {{ product.defaultVenue.name }}</span>
      {% endif %}
    </div>
  </div>
</div>
```

- [ ] **Step 2: Add product show hero hook to sylius_twig_hooks.yaml**

```yaml
        'sylius_shop.product.show.content':
            header:
                enabled: false
            watra_hero:
                template: 'shop/product/show/watra_hero.html.twig'
                priority: 300
```

- [ ] **Step 3: Clear cache and verify**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
```

Open any product page — expected: dark gradient hero at top with title, city, type badge and interest button. Breadcrumbs are gone.

- [ ] **Step 4: Commit**

```bash
git add templates/shop/product/show/watra_hero.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(phase-9a): add WATRA hero to product show page"
```

---

## Task 7: Product Show — Description + Terms Sidebar

**Files:**
- Create: `templates/shop/product/show/watra_description.html.twig`
- Create: `templates/shop/product/show/watra_terms.html.twig`
- Modify: `config/packages/sylius_twig_hooks.yaml`

Override the left (overview) and right (summary) columns.

- [ ] **Step 1: Create the description template**

Create `templates/shop/product/show/watra_description.html.twig`:

```twig
{% set product = hookable_metadata.context.product %}

<div class="p-4">
  <h2 class="h5 fw-bold mb-3">{{ 'app.ui.about_event'|trans }}</h2>
  {% if product.description %}
    <div class="text-muted lh-lg">{{ product.description|raw }}</div>
  {% else %}
    <p class="text-muted fst-italic">{{ 'app.ui.no_description'|trans }}</p>
  {% endif %}
</div>
```

- [ ] **Step 2: Create the terms sidebar template**

Create `templates/shop/product/show/watra_terms.html.twig`:

```twig
{% set product = hookable_metadata.context.product %}
{% set now = date() %}

{% set upcomingVariants = [] %}
{% for variant in product.variants %}
  {% if variant.enabled and variant.startsAt is not null and variant.startsAt > now %}
    {% set upcomingVariants = upcomingVariants|merge([variant]) %}
  {% endif %}
{% endfor %}

<div class="p-4">
  <h2 class="h5 fw-bold mb-3">📅 {{ 'app.ui.choose_date'|trans }}</h2>

  {% if upcomingVariants is empty %}
    <p class="text-muted">{{ 'app.ui.no_upcoming_dates'|trans }}</p>
  {% else %}
    <div class="d-flex flex-column gap-3">
      {% for variant in upcomingVariants %}
        <div class="border rounded-3 p-3 {{ loop.first ? 'border-primary bg-primary bg-opacity-10' : '' }}">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="fw-semibold">{{ variant.startsAt|date('D, d M Y', 'Europe/Warsaw') }}</div>
              <div class="text-muted small">
                {{ variant.startsAt|date('H:i', 'Europe/Warsaw') }}
                {% if variant.endsAt %}– {{ variant.endsAt|date('H:i', 'Europe/Warsaw') }}{% endif %}
              </div>
            </div>
            {% set pricing = null %}
            {% for channelPricing in variant.channelPricings %}
              {% set pricing = channelPricing %}
            {% endfor %}
            {% if pricing and pricing.price %}
              <span class="fw-bold text-success fs-5">{{ (pricing.price / 100)|number_format(0, ',', ' ') }} zł</span>
            {% endif %}
          </div>
          <a href="{{ path('app_shop_cart_add_variant', {_locale: app.request.locale, variantCode: variant.code}) }}"
             class="btn w-100 {{ loop.first ? 'btn-primary' : 'btn-outline-secondary' }} btn-sm">
            {{ 'app.ui.book_now'|trans }} →
          </a>
        </div>
      {% endfor %}
    </div>
  {% endif %}
</div>
```

- [ ] **Step 3: Add hooks to sylius_twig_hooks.yaml**

```yaml
        'sylius_shop.product.show.content.info.summary':
            header:
                enabled: false
            prices:
                enabled: false
            catalog_promotions:
                enabled: false
            short_description:
                enabled: false
            add_to_cart:
                enabled: false
            watra_terms:
                template: 'shop/product/show/watra_terms.html.twig'
                priority: 200

        'sylius_shop.product.show.content.info.overview':
            accordion:
                enabled: false
            watra_description:
                template: 'shop/product/show/watra_description.html.twig'
                priority: 100
```

- [ ] **Step 4: Clear cache and verify**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
```

Open a product page — expected: left column shows description, right column shows variant cards with dates, times, prices and "Zapisz się" buttons.

- [ ] **Step 5: Commit**

```bash
git add templates/shop/product/show/watra_description.html.twig templates/shop/product/show/watra_terms.html.twig config/packages/sylius_twig_hooks.yaml
git commit -m "feat(phase-9a): add description + terms sidebar to product show page"
```

---

## Task 8: CartController — Per-Variant Add to Cart

**Files:**
- Create: `src/Controller/Shop/CartController.php`
- Create: `config/routes/app_shop.yaml`
- Create: `tests/Functional/CartControllerTest.php`

A thin controller that adds a specific variant (by code) to the Sylius cart and redirects to checkout.

- [ ] **Step 1: Create the controller**

```php
<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_USER')]
final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartContextInterface $cartContext,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly FactoryInterface $orderItemFactory,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private readonly OrderModifierInterface $orderModifier,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    #[Route(
        '/{_locale}/cart/add-variant/{variantCode}',
        name: 'app_shop_cart_add_variant',
        methods: ['GET'],
        requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}'],
    )]
    public function addVariant(Request $request, string $variantCode): Response
    {
        $variant = $this->variantRepository->findOneBy(['code' => $variantCode]);
        if ($variant === null) {
            throw $this->createNotFoundException();
        }

        /** @var OrderInterface $cart */
        $cart = $this->cartContext->getCart();

        $orderItem = $this->orderItemFactory->createNew();
        $orderItem->setVariant($variant);
        $this->orderItemQuantityModifier->modify($orderItem, 1);
        $this->orderModifier->addToOrder($cart, $orderItem);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $this->redirectToRoute('sylius_shop_checkout_start', [
            '_locale' => $request->getLocale(),
        ]);
    }
}
```

- [ ] **Step 2: Register factory and modifier services**

Open `config/services.yaml`. Add after `App\EventListener\ShopAccountMenuListener`:

```yaml
    App\Controller\Shop\CartController:
        arguments:
            $orderItemFactory: '@sylius.factory.order_item'
        tags: ['controller.service_arguments']
```

- [ ] **Step 3: Clear cache and check route is registered**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
sudo docker compose -f compose.yaml exec php bin/console debug:router | grep add_variant
```

Expected: `app_shop_cart_add_variant   GET   /{_locale}/cart/add-variant/{variantCode}`

- [ ] **Step 4: Write functional test**

Create `tests/Functional/CartControllerTest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartControllerTest extends WebTestCase
{
    public function testGuestIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pl_PL/cart/add-variant/SOME-CODE');

        self::assertResponseRedirects();
        self::assertStringContainsString('login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testUnknownVariantReturns404(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user, 'Fixture user jan.kowalski@example.pl must exist');

        $client->loginUser($user, 'shop');
        $client->request('GET', '/pl_PL/cart/add-variant/NONEXISTENT-CODE-999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLoggedInUserCanAddVariantToCart(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user);

        /** @var ProductVariantRepositoryInterface $variantRepo */
        $variantRepo = $container->get('sylius.repository.product_variant');
        $variants = $variantRepo->findAll();
        if (empty($variants)) {
            self::markTestSkipped('No variants in fixtures');
        }

        $client->loginUser($user, 'shop');
        $client->request('GET', '/pl_PL/cart/add-variant/' . $variants[0]->getCode());

        // Should redirect to checkout (not 500)
        self::assertResponseRedirects();
        self::assertNotSame(500, $client->getResponse()->getStatusCode());
    }
}
```

- [ ] **Step 5: Run tests**

```bash
sudo docker compose -f compose.yaml exec php vendor/bin/phpunit tests/Functional/CartControllerTest.php
```

Expected: `OK (3 tests)` or with skips.

- [ ] **Step 6: Commit**

```bash
git add src/Controller/Shop/CartController.php config/services.yaml tests/Functional/CartControllerTest.php
git commit -m "feat(phase-9a): add CartController for per-variant add-to-cart"
```

---

## Task 9: Static Pages (Regulamin + Privacy)

**Files:**
- Create: `src/Controller/Shop/StaticPageController.php`
- Create: `templates/shop/static/regulamin.html.twig`
- Create: `templates/shop/static/privacy.html.twig`
- Create: `config/routes/app_shop.yaml`

- [ ] **Step 1: Create the controller**

```php
<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StaticPageController extends AbstractController
{
    #[Route('/{_locale}/regulamin', name: 'app_shop_regulamin', methods: ['GET'], requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}'])]
    public function regulamin(): Response
    {
        return $this->render('shop/static/regulamin.html.twig');
    }

    #[Route('/{_locale}/polityka-prywatnosci', name: 'app_shop_privacy', methods: ['GET'], requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}'])]
    public function privacy(): Response
    {
        return $this->render('shop/static/privacy.html.twig');
    }
}
```

- [ ] **Step 2: Create the regulamin template**

Create `templates/shop/static/regulamin.html.twig`:

```twig
{% extends '@SyliusShop/shared/layout/base.html.twig' %}

{% block title %}Regulamin | {{ parent() }}{% endblock %}

{% block content %}
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <h1 class="h2 fw-bold mb-4">Regulamin</h1>
      <div class="text-muted lh-lg">
        <p>Treść regulaminu serwisu WATRA zostanie uzupełniona przez administratora.</p>
      </div>
    </div>
  </div>
</div>
{% endblock %}
```

- [ ] **Step 3: Create the privacy template**

Create `templates/shop/static/privacy.html.twig`:

```twig
{% extends '@SyliusShop/shared/layout/base.html.twig' %}

{% block title %}Polityka prywatności | {{ parent() }}{% endblock %}

{% block content %}
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <h1 class="h2 fw-bold mb-4">Polityka prywatności</h1>
      <div class="text-muted lh-lg">
        <p>Treść polityki prywatności serwisu WATRA zostanie uzupełniona przez administratora.</p>
      </div>
    </div>
  </div>
</div>
{% endblock %}
```

- [ ] **Step 4: Clear cache and verify routes**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
sudo docker compose -f compose.yaml exec php bin/console debug:router | grep "app_shop_regulamin\|app_shop_privacy"
```

Expected: both routes listed.

- [ ] **Step 5: Commit**

```bash
git add src/Controller/Shop/StaticPageController.php templates/shop/static/regulamin.html.twig templates/shop/static/privacy.html.twig
git commit -m "feat(phase-9a): add static pages (regulamin, privacy policy)"
```

---

## Task 10: Translations

**Files:**
- Modify: `translations/messages.pl.yaml`
- Modify: `translations/messages.en.yaml`

- [ ] **Step 1: Add Polish translations**

Open `translations/messages.pl.yaml`. Under `app.ui:` add after `remove_interest`:

```yaml
        homepage_hero_title: 'Odkryj wydarzenia w Twoim mieście'
        homepage_hero_subtitle: 'Warsztaty, meetupy, konferencje — znajdź coś dla siebie'
        homepage_hero_cta: 'Zobacz wszystkie wydarzenia'
        upcoming_events: 'Nadchodzące wydarzenia'
        events_by_city: 'Wydarzenia wg miasta'
        no_events_in_city: 'Brak nadchodzących wydarzeń w tej lokalizacji.'
        about_event: 'O wydarzeniu'
        no_description: 'Brak opisu.'
        choose_date: 'Wybierz termin'
        book_now: 'Zapisz się'
        free: 'Bezpłatne'
```

- [ ] **Step 2: Add English translations**

Open `translations/messages.en.yaml`. Under `app.ui:` add after `remove_interest`:

```yaml
        homepage_hero_title: 'Discover events in your city'
        homepage_hero_subtitle: 'Workshops, meetups, conferences — find something for you'
        homepage_hero_cta: 'Browse all events'
        upcoming_events: 'Upcoming events'
        events_by_city: 'Events by city'
        no_events_in_city: 'No upcoming events in this location.'
        about_event: 'About this event'
        no_description: 'No description.'
        choose_date: 'Choose a date'
        book_now: 'Book now'
        free: 'Free'
```

- [ ] **Step 3: Clear cache**

```bash
sudo docker compose -f compose.yaml exec php bin/console cache:clear
```

- [ ] **Step 4: Commit**

```bash
git add translations/messages.pl.yaml translations/messages.en.yaml
git commit -m "feat(phase-9a): add translation keys for homepage and product show"
```

---

## Task 11: Full Test Suite + Mark Complete + Push

- [ ] **Step 1: Run full test suite**

```bash
sudo docker compose -f compose.yaml exec php vendor/bin/phpunit
```

Expected: all green (or skips only — no failures).

- [ ] **Step 2: Mark phase complete in TASKS.md**

Open `docs/TASKS.md`. Find `## [ ] Faza 9` and change to `## [x] Faza 9A`. Mark sub-tasks 9.1–9.8, 9.11 (those in scope for 9A) as `[x]`. Add notes:

```markdown
> **Uwagi po realizacji (9A):**
> - Homepage: Twig Hooks na `sylius_shop.homepage.index` — brak custom HomeController
> - EventCard: `#[AsTwigComponent]` z `ChannelContextInterface` dla ceny
> - ProductRepository: custom DQL z JOIN na variants dla filtrowania po startsAt
> - Per-variant add-to-cart: `CartController::addVariant` GET → redirect do checkout
> - CSS tabs na homepage: radio + label trick, zero JS
> - Faza 9B: mapa Leaflet, EventListFilters Live Component, account orders
```

- [ ] **Step 3: Commit and push**

```bash
git add docs/TASKS.md
git commit -m "docs: mark phase 9A complete"
git push
```

---

## Self-Review Checklist

- [x] **Spec coverage:** ProductRepository queries ✓, EventCard ✓, Homepage Hero ✓, HomepageSpotlight ✓, HomepageCity ✓, Product show hero ✓, Description ✓, Terms sidebar ✓, CartController ✓, Static pages ✓, Translations ✓
- [x] **Placeholder scan:** No TBDs. All code blocks complete.
- [x] **Type consistency:** `ProductRepository` methods use `string $channelCode` (not `ChannelInterface`) — consistent across Tasks 1, 4, 5. `EventCard::getNextVariant()` returns `?ProductVariant` — used as `nextVariant` in template. `HomepageSpotlightComponent::getFeatured()` returns `?object` — called as `featured` in template. `CartController` uses `$variantRepository->findOneBy(['code' => $variantCode])` — consistent with Sylius repository pattern.
- [x] **Note on `sylius_shop_checkout_start` route:** Verify this route name exists (`debug:router | grep checkout_start`) before Task 8. If missing, replace with `sylius_shop_cart_summary`.
