# Phase 5: Extending Sylius Entities Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend Sylius Product, ProductVariant and AdminUser with WATRA-specific event fields (city, venue, eventType, eventStatus, isOnline, startsAt, endsAt, administrationRoles); create backed enums; add form type extensions; validate variant dates; update fixtures with new data.

**Architecture:** Extend existing Sylius entity overrides in `src/Entity/` by adding ORM-mapped fields and relationships. Use `AbstractTypeExtension` (not `AbstractResourceType`) to inject custom fields into Sylius admin forms for Product, ProductVariant, and AdminUser — these are extensions of existing types, not new standalone types. Backed PHP enums stored as VARCHAR via Doctrine 3's native enum mapping.

**Tech Stack:** PHP 8.3, Symfony 7, Doctrine ORM 3, Sylius 2.2, backed PHP enums (`enum Foo: string`), `Symfony\Component\Form\AbstractTypeExtension`

---

## Pre-flight: Already done

- **Task 5.3a** — `App\Entity\Customer\Customer` already exists at `src/Entity/Customer/Customer.php` as an empty extends with no new fields needed in MVP.
- **Task 5.4** — `config/packages/_sylius.yaml` already configures:
  - `sylius_product.resources.product.classes.model: App\Entity\Product\Product`
  - `sylius_product.resources.product_variant.classes.model: App\Entity\Product\ProductVariant`
  - `sylius_user.resources.admin.user.classes.model: App\Entity\User\AdminUser`
  - `sylius_customer.resources.customer.classes.model: App\Entity\Customer\Customer`

---

## Task 1: Create EventStatus and EventType enums (TASKS.md 5.5)

**Files:**
- Create: `src/Enum/EventStatus.php`
- Create: `src/Enum/EventType.php`

- [ ] **Step 1: Create EventStatus enum**

```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
}
```

- [ ] **Step 2: Create EventType enum**

```php
<?php

declare(strict_types=1);

namespace App\Enum;

enum EventType: string
{
    case WORKSHOP = 'workshop';
    case MEETUP = 'meetup';
    case PARTY = 'party';
    case CONFERENCE = 'conference';
    case OTHER = 'other';
}
```

- [ ] **Step 3: Verify syntax**

Run: `sudo docker compose -f compose.yaml exec php php -l src/Enum/EventStatus.php src/Enum/EventType.php`
Expected: `No syntax errors detected in src/Enum/EventStatus.php` and same for EventType.

- [ ] **Step 4: Commit**

```bash
git add src/Enum/EventStatus.php src/Enum/EventType.php
git commit -m "feat(phase-5): add EventStatus and EventType backed enums"
```

---

## Task 2: Extend Product entity (TASKS.md 5.1)

**Files:**
- Modify: `src/Entity/Product/Product.php`

**Note:** `city` is nullable in ORM (JoinColumn nullable: true) because existing DB products have no city value and the migration must not fail on existing data. The form extension makes it required at the UI level. After `make fixtures` re-runs, all products will have city set.

- [ ] **Step 1: Replace Product entity content**

Full content for `src/Entity/Product/Product.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Catalog\City;
use App\Entity\Catalog\Venue;
use App\Enum\EventStatus;
use App\Enum\EventType;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Sylius\MolliePlugin\Entity\ProductInterface;
use Sylius\MolliePlugin\Entity\ProductTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product')]
class Product extends BaseProduct implements ProductInterface
{
    use ProductTrait;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?City $city = null;

    #[ORM\ManyToOne(targetEntity: Venue::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Venue $defaultVenue = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isOnline = false;

    #[ORM\Column(type: 'string', length: 32, enumType: EventStatus::class, options: ['default' => 'draft'])]
    private EventStatus $eventStatus = EventStatus::DRAFT;

    #[ORM\Column(type: 'string', length: 32, enumType: EventType::class, options: ['default' => 'other'])]
    private EventType $eventType = EventType::OTHER;

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): void
    {
        $this->city = $city;
    }

    public function getDefaultVenue(): ?Venue
    {
        return $this->defaultVenue;
    }

    public function setDefaultVenue(?Venue $defaultVenue): void
    {
        $this->defaultVenue = $defaultVenue;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): void
    {
        $this->isOnline = $isOnline;
    }

    public function getEventStatus(): EventStatus
    {
        return $this->eventStatus;
    }

    public function setEventStatus(EventStatus $eventStatus): void
    {
        $this->eventStatus = $eventStatus;
    }

    public function getEventType(): EventType
    {
        return $this->eventType;
    }

    public function setEventType(EventType $eventType): void
    {
        $this->eventType = $eventType;
    }

    protected function createTranslation(): ProductTranslationInterface
    {
        return new ProductTranslation();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Entity/Product/Product.php
git commit -m "feat(phase-5): extend Product with city, defaultVenue, eventStatus, eventType, isOnline"
```

---

## Task 3: Extend ProductVariant entity (TASKS.md 5.2)

**Files:**
- Modify: `src/Entity/Product/ProductVariant.php`

- [ ] **Step 1: Replace ProductVariant entity content**

Full content for `src/Entity/Product/ProductVariant.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity\Product;

use App\Entity\Catalog\Venue;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\MolliePlugin\Entity\ProductVariantInterface;
use Sylius\MolliePlugin\Entity\RecurringProductVariantTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_product_variant')]
#[Assert\Callback([self::class, 'validateDates'])]
class ProductVariant extends BaseProductVariant implements ProductVariantInterface
{
    use RecurringProductVariantTrait;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startsAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endsAt = null;

    #[ORM\ManyToOne(targetEntity: Venue::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Venue $venue = null;

    public function getStartsAt(): ?\DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(?\DateTimeInterface $startsAt): void
    {
        $this->startsAt = $startsAt;
    }

    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeInterface $endsAt): void
    {
        $this->endsAt = $endsAt;
    }

    public function getVenue(): ?Venue
    {
        return $this->venue;
    }

    public function setVenue(?Venue $venue): void
    {
        $this->venue = $venue;
    }

    public static function validateDates(self $variant, ExecutionContextInterface $context): void
    {
        $startsAt = $variant->getStartsAt();
        $endsAt = $variant->getEndsAt();

        if ($startsAt !== null && $endsAt !== null && $startsAt >= $endsAt) {
            $context->buildViolation('app.validation.starts_at_must_be_before_ends_at')
                ->atPath('startsAt')
                ->addViolation();
        }

        if ($startsAt !== null && $startsAt <= new \DateTime()) {
            $context->buildViolation('app.validation.starts_at_must_be_in_future')
                ->atPath('startsAt')
                ->addViolation();
        }
    }

    protected function createTranslation(): ProductVariantTranslationInterface
    {
        return new ProductVariantTranslation();
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Entity/Product/ProductVariant.php
git commit -m "feat(phase-5): extend ProductVariant with startsAt, endsAt, venue; add date validation"
```

---

## Task 4: Extend AdminUser entity (TASKS.md 5.3)

**Files:**
- Modify: `src/Entity/User/AdminUser.php`

- [ ] **Step 1: Replace AdminUser entity content**

Full content for `src/Entity/User/AdminUser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Admin\AdministrationRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;
use Sylius\MolliePlugin\Entity\OnboardingStatusAwareInterface;
use Sylius\MolliePlugin\Entity\OnboardingStatusAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_admin_user')]
class AdminUser extends BaseAdminUser implements OnboardingStatusAwareInterface
{
    use OnboardingStatusAwareTrait;

    /** @var Collection<int, AdministrationRole> */
    #[ORM\ManyToMany(targetEntity: AdministrationRole::class)]
    #[ORM\JoinTable(name: 'watra_admin_user_administration_role')]
    private Collection $administrationRoles;

    public function __construct()
    {
        parent::__construct();
        $this->administrationRoles = new ArrayCollection();
    }

    /** @return Collection<int, AdministrationRole> */
    public function getAdministrationRoles(): Collection
    {
        return $this->administrationRoles;
    }

    public function addAdministrationRole(AdministrationRole $role): void
    {
        if (!$this->administrationRoles->contains($role)) {
            $this->administrationRoles->add($role);
        }
    }

    public function removeAdministrationRole(AdministrationRole $role): void
    {
        $this->administrationRoles->removeElement($role);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Entity/User/AdminUser.php
git commit -m "feat(phase-5): extend AdminUser with M2M administrationRoles collection"
```

---

## Task 5: Generate and apply migration (TASKS.md 5.6)

**Files:**
- Create: `migrations/Version<timestamp>.php` (auto-generated by Doctrine)

- [ ] **Step 1: Generate migration**

Run: `sudo docker compose -f compose.yaml exec php bin/console doctrine:migrations:diff`
Expected: `Generated new migration class to "migrations/Version2026050XXXXX.php"`

If you see "No changes detected" — the entity changes from Tasks 2–4 were not picked up. Check that the PHP container was rebuilt or cache was cleared: `sudo docker compose -f compose.yaml exec php bin/console cache:clear`

- [ ] **Step 2: Review generated migration**

Open the generated file and verify it contains:
- `ALTER TABLE sylius_product ADD city_id INT DEFAULT NULL` (nullable FK to watra_city)
- `ALTER TABLE sylius_product ADD default_venue_id INT DEFAULT NULL` (nullable FK to watra_venue)
- `ALTER TABLE sylius_product ADD is_online BOOLEAN NOT NULL DEFAULT false`
- `ALTER TABLE sylius_product ADD event_status VARCHAR(32) NOT NULL DEFAULT 'draft'`
- `ALTER TABLE sylius_product ADD event_type VARCHAR(32) NOT NULL DEFAULT 'other'`
- `ALTER TABLE sylius_product_variant ADD starts_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL`
- `ALTER TABLE sylius_product_variant ADD ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL`
- `ALTER TABLE sylius_product_variant ADD venue_id INT DEFAULT NULL`
- `CREATE TABLE watra_admin_user_administration_role (admin_user_id INT NOT NULL, administration_role_id INT NOT NULL, PRIMARY KEY(admin_user_id, administration_role_id))`
- FK constraints referencing `watra_city`, `watra_venue`, `watra_administration_role`

Remove any unrelated SQL if the migration contains changes from Mollie/other plugins that are already applied.

- [ ] **Step 3: Apply migration**

Run: `sudo docker compose -f compose.yaml exec php bin/console doctrine:migrations:migrate --no-interaction`
Expected: `[notice] Migrating up to DoctrineMigrations\Version2026...` then `[OK]`

- [ ] **Step 4: Commit**

```bash
git add migrations/
git commit -m "feat(phase-5): migration — add event fields to Product/ProductVariant, M2M AdminUser roles"
```

---

## Task 6: Add translation keys for new fields (TASKS.md — supporting task)

**Files:**
- Modify: `translations/messages.pl.yaml`
- Modify: `translations/messages.en.yaml`

- [ ] **Step 1: Add Polish translations**

In `translations/messages.pl.yaml`, under `app: ui:` (after the existing `select_city` key), add:

```yaml
        event_type: 'Typ wydarzenia'
        event_status: 'Status wydarzenia'
        is_online: 'Wydarzenie online'
        starts_at: 'Początek'
        ends_at: 'Koniec'
        administration_roles: 'Role administracyjne'
        select_venue: 'Wybierz lokalizację'
        select_event_type: 'Wybierz typ'
        select_event_status: 'Wybierz status'
```

Also add a new `app: enum:` section at the end of the `app:` block:

```yaml
    enum:
        event_status:
            draft: 'Szkic'
            published: 'Opublikowane'
            cancelled: 'Anulowane'
            completed: 'Zakończone'
        event_type:
            workshop: 'Warsztaty'
            meetup: 'Meetup'
            party: 'Impreza'
            conference: 'Konferencja'
            other: 'Inne'
    validation:
        starts_at_must_be_before_ends_at: 'Data rozpoczęcia musi być wcześniejsza niż data zakończenia'
        starts_at_must_be_in_future: 'Data rozpoczęcia musi być w przyszłości'
```

- [ ] **Step 2: Add English translations**

In `translations/messages.en.yaml`, under `app: ui:`, add the same keys in English:

```yaml
        event_type: 'Event type'
        event_status: 'Event status'
        is_online: 'Online event'
        starts_at: 'Starts at'
        ends_at: 'Ends at'
        administration_roles: 'Administration roles'
        select_venue: 'Select venue'
        select_event_type: 'Select type'
        select_event_status: 'Select status'
```

And add:

```yaml
    enum:
        event_status:
            draft: 'Draft'
            published: 'Published'
            cancelled: 'Cancelled'
            completed: 'Completed'
        event_type:
            workshop: 'Workshop'
            meetup: 'Meetup'
            party: 'Party'
            conference: 'Conference'
            other: 'Other'
    validation:
        starts_at_must_be_before_ends_at: 'Start date must be before end date'
        starts_at_must_be_in_future: 'Start date must be in the future'
```

- [ ] **Step 3: Commit**

```bash
git add translations/
git commit -m "feat(phase-5): add translation keys for event fields, enums and variant validation"
```

---

## Task 7: ProductTypeExtension form extension (TASKS.md 5.7)

**Files:**
- Create: `src/Form/Extension/ProductTypeExtension.php`

**Pattern note:** For Sylius entity forms, we use `AbstractTypeExtension` (not `AbstractResourceType`). With `autoconfigure: true` in services.yaml, classes extending `AbstractTypeExtension` are automatically tagged as `form.type_extension` — no manual service registration needed.

- [ ] **Step 1: Find Sylius ProductType FQCN**

Run: `sudo docker compose -f compose.yaml exec php bin/console debug:form | grep -i "ProductType"`
Expected: A line like `Sylius\Bundle\ProductBundle\Form\Type\ProductType`

Note: the FQCN needed is for the admin product form. It should be `Sylius\Bundle\ProductBundle\Form\Type\ProductType`.

- [ ] **Step 2: Create ProductTypeExtension**

Create `src/Form/Extension/ProductTypeExtension.php`:

```php
<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Catalog\City;
use App\Entity\Catalog\Venue;
use App\Enum\EventStatus;
use App\Enum\EventType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;

final class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('city', EntityType::class, [
                'class' => City::class,
                'label' => 'app.ui.city',
                'placeholder' => 'app.ui.select_city',
                'required' => false,
                'choice_label' => fn (City $city): string => $city->getName(),
            ])
            ->add('defaultVenue', EntityType::class, [
                'class' => Venue::class,
                'label' => 'app.ui.venue',
                'placeholder' => 'app.ui.select_venue',
                'required' => false,
                'choice_label' => fn (Venue $venue): string => $venue->getName(),
            ])
            ->add('isOnline', CheckboxType::class, [
                'label' => 'app.ui.is_online',
                'required' => false,
            ])
            ->add('eventStatus', EnumType::class, [
                'class' => EventStatus::class,
                'label' => 'app.ui.event_status',
                'choice_label' => fn (EventStatus $status): string => 'app.enum.event_status.' . $status->value,
                'choice_translation_domain' => 'messages',
            ])
            ->add('eventType', EnumType::class, [
                'class' => EventType::class,
                'label' => 'app.ui.event_type',
                'choice_label' => fn (EventType $type): string => 'app.enum.event_type.' . $type->value,
                'choice_translation_domain' => 'messages',
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}
```

- [ ] **Step 3: Clear cache and verify no errors**

Run: `sudo docker compose -f compose.yaml exec php bin/console cache:clear`
Expected: `[OK] Cache for the "dev" environment (debug=true) was successfully cleared.`

If there's a `class not found` error for `ProductType`, go back to Step 1 and use the actual class name from debug:form output.

- [ ] **Step 4: Commit**

```bash
git add src/Form/Extension/ProductTypeExtension.php
git commit -m "feat(phase-5): add ProductTypeExtension — city, venue, eventStatus, eventType, isOnline"
```

---

## Task 8: ProductVariantTypeExtension form extension (TASKS.md 5.8)

**Files:**
- Create: `src/Form/Extension/ProductVariantTypeExtension.php`

- [ ] **Step 1: Find Sylius ProductVariantType FQCN**

Run: `sudo docker compose -f compose.yaml exec php bin/console debug:form | grep -i "ProductVariantType"`
Expected: `Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType`

- [ ] **Step 2: Create ProductVariantTypeExtension**

Create `src/Form/Extension/ProductVariantTypeExtension.php`:

```php
<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Catalog\Venue;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType;

final class ProductVariantTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startsAt', DateTimeType::class, [
                'label' => 'app.ui.starts_at',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'app.ui.ends_at',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('venue', EntityType::class, [
                'class' => Venue::class,
                'label' => 'app.ui.venue',
                'placeholder' => 'app.ui.select_venue',
                'required' => false,
                'choice_label' => fn (Venue $venue): string => $venue->getName(),
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductVariantType::class];
    }
}
```

- [ ] **Step 3: Clear cache**

Run: `sudo docker compose -f compose.yaml exec php bin/console cache:clear`
Expected: No errors

- [ ] **Step 4: Commit**

```bash
git add src/Form/Extension/ProductVariantTypeExtension.php
git commit -m "feat(phase-5): add ProductVariantTypeExtension — startsAt, endsAt, venue"
```

---

## Task 9: AdminUserTypeExtension form extension (TASKS.md 5.9)

**Files:**
- Create: `src/Form/Extension/AdminUserTypeExtension.php`

- [ ] **Step 1: Find Sylius AdminUser form type FQCN**

Run: `sudo docker compose -f compose.yaml exec php bin/console debug:form | grep -i "admin.*user\|AdminUser"`

Look for the full class name. In Sylius 2.x it is typically one of:
- `Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType`
- `Sylius\Bundle\AdminBundle\Form\Type\AdminUserType`

Note the exact FQCN from the output.

- [ ] **Step 2: Create AdminUserTypeExtension**

Create `src/Form/Extension/AdminUserTypeExtension.php`.

Replace the `use` import below with the actual class found in Step 1:

```php
<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Admin\AdministrationRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType; // verify via debug:form

final class AdminUserTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('administrationRoles', EntityType::class, [
            'class' => AdministrationRole::class,
            'label' => 'app.ui.administration_roles',
            'multiple' => true,
            'expanded' => false,
            'required' => false,
            'choice_label' => fn (AdministrationRole $role): string => $role->getName(),
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [AdminUserType::class]; // use the class found in Step 1
    }
}
```

- [ ] **Step 3: Clear cache and verify no errors**

Run: `sudo docker compose -f compose.yaml exec php bin/console cache:clear`
Expected: No errors. If you see `The "Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType" class does not exist`, update the `use` import and `getExtendedTypes()` with the correct class found in Step 1.

- [ ] **Step 4: Commit**

```bash
git add src/Form/Extension/AdminUserTypeExtension.php
git commit -m "feat(phase-5): add AdminUserTypeExtension — administrationRoles multi-select"
```

---

## Task 10: Update WatraEventFixture with new fields (TASKS.md 5.11)

**Files:**
- Modify: `src/Fixture/WatraEventFixture.php`

**Note:** City lookup uses `findAll()` + translation iteration since City has no `code` field. The fixture builds a `$cityByName` map keyed by Polish translation name. WatraCatalogFixture (which runs before watra_events in the suite) creates: Kraków → index 0, Warszawa → index 1, Wrocław → index 2.

- [ ] **Step 1: Replace WatraEventFixture content**

Full content for `src/Fixture/WatraEventFixture.php`:

```php
<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Entity\Catalog\City;
use App\Entity\Catalog\CityTranslation;
use App\Entity\Product\Product;
use App\Entity\Product\ProductTaxon;
use App\Entity\Product\ProductTranslation;
use App\Entity\Product\ProductVariant;
use App\Entity\Product\ProductVariantTranslation;
use App\Enum\EventStatus;
use App\Enum\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Taxation\Repository\TaxCategoryRepositoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class WatraEventFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly TaxCategoryRepositoryInterface $taxCategoryRepository,
        /** @var FactoryInterface<ChannelPricingInterface> */
        private readonly FactoryInterface $channelPricingFactory,
    ) {
    }

    public function load(array $options): void
    {
        $channel = $this->channelRepository->findOneByCode('WATRA');
        $taxCategory = $this->taxCategoryRepository->findOneBy(['code' => 'default']);

        $cityByName = $this->buildCityMap();

        $events = [
            [
                'name' => 'Warsztaty PHP 8.3 — nowoczesne wzorce',
                'code' => 'WATRA-PHP-83',
                'slug' => 'warsztaty-php-83-nowoczesne-wzorce',
                'description' => 'Intensywne warsztaty z nowoczesnego PHP 8.3: enumy, fibry, readonly properties, intersect types. Warsztat praktyczny — piszemy kod przez cały dzień.',
                'shortDescription' => 'Praktyczne warsztaty PHP 8.3 dla średniozaawansowanych i zaawansowanych.',
                'taxon' => 'WATRA_WARSZTATY',
                'eventType' => EventType::WORKSHOP,
                'eventStatus' => EventStatus::PUBLISHED,
                'isOnline' => false,
                'city' => 'Kraków',
                'variants' => [
                    ['name' => '15 czerwca 2026 — Kraków', 'code' => 'WATRA-PHP-83-KRK-0615', 'onHand' => 20, 'startsAt' => '2026-06-15 09:00:00', 'endsAt' => '2026-06-15 17:00:00'],
                    ['name' => '20 lipca 2026 — Kraków', 'code' => 'WATRA-PHP-83-KRK-0720', 'onHand' => 20, 'startsAt' => '2026-07-20 09:00:00', 'endsAt' => '2026-07-20 17:00:00'],
                ],
            ],
            [
                'name' => 'Meetup Laravel — najlepsze praktyki 2026',
                'code' => 'WATRA-LARAVEL-01',
                'slug' => 'meetup-laravel-najlepsze-praktyki-2026',
                'description' => 'Comiesięczny meetup społeczności Laravel w Warszawie. Trzy prezentacje, networking, pizza. Dołącz do kilkudziesięciu developerów!',
                'shortDescription' => 'Comiesięczny meetup społeczności Laravel — prezentacje i networking.',
                'taxon' => 'WATRA_MEETUPY',
                'eventType' => EventType::MEETUP,
                'eventStatus' => EventStatus::PUBLISHED,
                'isOnline' => false,
                'city' => 'Warszawa',
                'variants' => [
                    ['name' => '8 czerwca 2026 — Warszawa', 'code' => 'WATRA-LARAVEL-WAW-0608', 'onHand' => 60, 'startsAt' => '2026-06-08 18:00:00', 'endsAt' => '2026-06-08 21:00:00'],
                ],
            ],
            [
                'name' => 'PHPCon Poland 2026',
                'code' => 'WATRA-PHPCON-2026',
                'slug' => 'phpcon-poland-2026',
                'description' => 'Największa konferencja PHP w Polsce. Dwa dni, trzy ścieżki tematyczne, ponad 30 prelegentów z kraju i ze świata. Tematy: architektura, DDD, testy, bezpieczeństwo.',
                'shortDescription' => 'Największa konferencja PHP w Polsce — dwa dni, 30+ prelegentów.',
                'taxon' => 'WATRA_KONFERENCJE',
                'eventType' => EventType::CONFERENCE,
                'eventStatus' => EventStatus::PUBLISHED,
                'isOnline' => false,
                'city' => 'Kraków',
                'variants' => [
                    ['name' => 'Dzień 1 — 15 września 2026', 'code' => 'WATRA-PHPCON-D1', 'onHand' => 300, 'startsAt' => '2026-09-15 09:00:00', 'endsAt' => '2026-09-15 18:00:00'],
                    ['name' => 'Dzień 2 — 16 września 2026', 'code' => 'WATRA-PHPCON-D2', 'onHand' => 300, 'startsAt' => '2026-09-16 09:00:00', 'endsAt' => '2026-09-16 18:00:00'],
                    ['name' => 'Bilet 2-dniowy', 'code' => 'WATRA-PHPCON-2D', 'onHand' => 250, 'startsAt' => '2026-09-15 09:00:00', 'endsAt' => '2026-09-16 18:00:00'],
                ],
            ],
            [
                'name' => 'Warsztaty Vue.js + Inertia',
                'code' => 'WATRA-VUE-INERTIA',
                'slug' => 'warsztaty-vuejs-inertia',
                'description' => 'Jeden dzień z Vue.js i Inertia.js — jak budować SPA bez API i bez bólu głowy. Integracja z Laravelem i Symfony. Wymagana znajomość JavaScript.',
                'shortDescription' => 'Warsztaty Vue.js + Inertia.js — nowoczesny frontend bez API.',
                'taxon' => 'WATRA_WARSZTATY',
                'eventType' => EventType::WORKSHOP,
                'eventStatus' => EventStatus::PUBLISHED,
                'isOnline' => false,
                'city' => 'Kraków',
                'variants' => [
                    ['name' => '5 lipca 2026 — Kraków', 'code' => 'WATRA-VUE-KRK-0705', 'onHand' => 15, 'startsAt' => '2026-07-05 09:00:00', 'endsAt' => '2026-07-05 17:00:00'],
                ],
            ],
            [
                'name' => 'Meetup Symfony UX — Live Components w akcji',
                'code' => 'WATRA-SYMFONYUX-01',
                'slug' => 'meetup-symfony-ux-live-components',
                'description' => 'Spotkanie poświęcone Symfony UX: Live Components, Stimulus, Turbo i TwigComponents. Dwa live-coding demo i dyskusja.',
                'shortDescription' => 'Spotkanie o Symfony UX — Live Components, Stimulus, Turbo.',
                'taxon' => 'WATRA_MEETUPY',
                'eventType' => EventType::MEETUP,
                'eventStatus' => EventStatus::PUBLISHED,
                'isOnline' => false,
                'city' => 'Warszawa',
                'variants' => [
                    ['name' => '22 czerwca 2026 — Warszawa', 'code' => 'WATRA-SYMFONYUX-WAW-0622', 'onHand' => 45, 'startsAt' => '2026-06-22 18:00:00', 'endsAt' => '2026-06-22 21:00:00'],
                ],
            ],
            [
                'name' => 'Warsztaty Docker + Kubernetes dla PHP devów',
                'code' => 'WATRA-DOCKER-K8S',
                'slug' => 'warsztaty-docker-kubernetes-php',
                'description' => 'Jednodniowe warsztaty z konteneryzacji: Docker Compose, multi-stage builds, Kubernetes basics, Helm charts dla aplikacji PHP.',
                'shortDescription' => 'Warsztaty z Docker i Kubernetes dla programistów PHP.',
                'taxon' => 'WATRA_WARSZTATY',
                'eventType' => EventType::WORKSHOP,
                'eventStatus' => EventStatus::DRAFT,
                'isOnline' => true,
                'city' => 'Kraków',
                'variants' => [
                    ['name' => '10 sierpnia 2026 — Online', 'code' => 'WATRA-DOCKER-ONLINE-0810', 'onHand' => 100, 'startsAt' => '2026-08-10 09:00:00', 'endsAt' => '2026-08-10 17:00:00'],
                ],
            ],
        ];

        foreach ($events as $eventData) {
            $city = $cityByName[$eventData['city']] ?? null;
            $this->createEvent($eventData, $channel, $taxCategory, $city);
        }

        $this->em->flush();
    }

    public function getName(): string
    {
        return 'watra_events';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
    }

    /** @return array<string, City> */
    private function buildCityMap(): array
    {
        /** @var City[] $cities */
        $cities = $this->em->getRepository(City::class)->findAll();
        $map = [];

        foreach ($cities as $city) {
            /** @var CityTranslation $translation */
            foreach ($city->getTranslations() as $translation) {
                $map[$translation->getName()] = $city;
            }
        }

        return $map;
    }

    private function createEvent(array $data, $channel, $taxCategory, ?City $city): void
    {
        $product = new Product();
        $product->setCode($data['code']);
        $product->setEnabled(true);
        $product->setVariantSelectionMethod(Product::VARIANT_SELECTION_CHOICE);
        $product->setCity($city);
        $product->setEventType($data['eventType']);
        $product->setEventStatus($data['eventStatus']);
        $product->setIsOnline($data['isOnline']);

        $translation = new ProductTranslation();
        $translation->setLocale('pl_PL');
        $translation->setName($data['name']);
        $translation->setSlug($data['slug']);
        $translation->setDescription($data['description']);
        $translation->setShortDescription($data['shortDescription']);
        $product->addTranslation($translation);

        $product->addChannel($channel);

        $taxon = $this->taxonRepository->findOneBy(['code' => $data['taxon']]);
        if ($taxon !== null) {
            $product->setMainTaxon($taxon);

            $productTaxon = new ProductTaxon();
            $productTaxon->setProduct($product);
            $productTaxon->setTaxon($taxon);
            $product->addProductTaxon($productTaxon);
            $this->em->persist($productTaxon);
        }

        $this->em->persist($product);

        foreach ($data['variants'] as $variantData) {
            $variant = new ProductVariant();
            $variant->setCode($variantData['code']);
            $variant->setEnabled(true);
            $variant->setTracked(true);
            $variant->setOnHand($variantData['onHand']);
            $variant->setShippingRequired(false);
            $variant->setTaxCategory($taxCategory);
            $variant->setStartsAt(new \DateTime($variantData['startsAt']));
            $variant->setEndsAt(new \DateTime($variantData['endsAt']));

            $variantTranslation = new ProductVariantTranslation();
            $variantTranslation->setLocale('pl_PL');
            $variantTranslation->setName($variantData['name']);
            $variant->addTranslation($variantTranslation);

            /** @var ChannelPricingInterface $channelPricing */
            $channelPricing = $this->channelPricingFactory->createNew();
            $channelPricing->setChannelCode('WATRA');
            $channelPricing->setPrice(0);
            $channelPricing->setOriginalPrice(0);
            $variant->addChannelPricing($channelPricing);

            $product->addVariant($variant);
            $this->em->persist($variant);
        }
    }
}
```

- [ ] **Step 2: Run fixtures to verify**

Run: `make fixtures`
Expected: No errors. Output includes `watra_events` fixture running successfully.

Verify with DB query: `sudo docker compose -f compose.yaml exec php bin/console doctrine:query:sql "SELECT p.code, p.event_type, p.event_status, pv.starts_at FROM sylius_product p JOIN sylius_product_variant pv ON pv.product_id = p.id LIMIT 6"`
Expected: 6 rows with event_type, event_status and starts_at values populated.

- [ ] **Step 3: Commit**

```bash
git add src/Fixture/WatraEventFixture.php
git commit -m "feat(phase-5): update WatraEventFixture with city, eventType, eventStatus, startsAt/endsAt on variants"
```

---

## Task 11: Smoke test (TASKS.md 5.12)

- [ ] **Step 1: Open admin and navigate to Events**

Open: `http://localhost/admin/login`
Login: `sylius@example.com` / `sylius`

Navigate to: Catalog → "Wydarzenia" (Products)

- [ ] **Step 2: Edit an existing event and verify new fields**

Click "Edit" on any event (e.g., "Warsztaty PHP 8.3").
Scroll through the form — verify the following new fields are visible:
- City dropdown (showing Kraków/Warszawa/Wrocław)
- Venue dropdown
- Event type dropdown (Warsztaty / Meetup / etc.)
- Event status dropdown (Szkic / Opublikowane / etc.)
- "Wydarzenie online" checkbox

- [ ] **Step 3: Verify variant form fields**

Scroll to the Variants section — click to edit a variant.
Verify the following new fields are visible:
- "Początek" (starts_at) datetime input
- "Koniec" (ends_at) datetime input
- Venue dropdown

- [ ] **Step 4: Verify AdminUser form**

Navigate to: Configuration → Admin Users → Edit any user
Verify: "Role administracyjne" multi-select dropdown is visible.

- [ ] **Step 5: Create a test event to verify form saves correctly**

Fill in new event form:
- Name (Polish tab): "Test Faza 5"
- City: select any
- Event type: Meetup
- Event status: Szkic

In Variants section, add variant:
- Name: "Test termin"
- Starts at: any future datetime (e.g., `2026-12-01T10:00`)
- Ends at: later than starts_at (e.g., `2026-12-01T12:00`)

Submit. Expected: redirected to index with success flash, no validation errors.

- [ ] **Step 6: Test validation — set endsAt before startsAt**

Create/edit a variant, set:
- Starts at: `2026-12-01T15:00`
- Ends at: `2026-12-01T10:00` (before starts_at)

Submit. Expected: form shows validation error "Data rozpoczęcia musi być wcześniejsza niż data zakończenia".

- [ ] **Step 7: Mark tasks done in TASKS.md**

Edit `docs/TASKS.md` — mark all sub-tasks of Faza 5 (5.1–5.12) as `[x]`.
Add implementation notes under the phase (after `**DoD:**` line):

```
> **Uwagi po realizacji:**
> - Task 5.3a i 5.4 były już zrealizowane w poprzednich fazach
> - city w Product: nullable w ORM (JoinColumn nullable: true), required na poziomie formularza
> - Sylius AdminUser form type FQCN: [wpisz actual class name z debug:form]
> - Form extensions (AbstractTypeExtension) auto-rejestrowane przez autoconfigure: true — brak wpisów w services.yaml
> - City lookup w WatraEventFixture: przez buildCityMap() (translation names, no code field)
```

- [ ] **Step 8: Push to remote**

```bash
git push
```

---

## Self-Review

### Spec coverage

| TASKS.md | Covered by |
|---|---|
| 5.1 Product: city, defaultVenue, isOnline, eventStatus, eventType | Task 2 |
| 5.2 ProductVariant: startsAt, endsAt, venue | Task 3 |
| 5.3 AdminUser M2M administrationRoles | Task 4 |
| 5.3a Customer empty extends | ✅ Already done |
| 5.4 _sylius.yaml resource overrides | ✅ Already done |
| 5.5 EventStatus + EventType enums | Task 1 |
| 5.6 Migration | Task 5 |
| 5.7 ProductTypeExtension | Task 7 |
| 5.8 ProductVariantTypeExtension | Task 8 |
| 5.9 AdminUserTypeExtension | Task 9 |
| 5.10 Validation startsAt < endsAt, startsAt future | Task 3 (embedded in entity) |
| 5.11 Fixtures 5-10 events with 1-3 variants | Task 10 (6 events) |
| 5.12 Smoke test | Task 11 |

### Type consistency

- `EventStatus::DRAFT/PUBLISHED/CANCELLED/COMPLETED` — defined Task 1, used Task 2, Task 10
- `EventType::WORKSHOP/MEETUP/PARTY/CONFERENCE/OTHER` — defined Task 1, used Task 2, Task 10
- `Product::setCity(?City)`, `setEventType(EventType)`, `setEventStatus(EventStatus)`, `setIsOnline(bool)` — defined Task 2, used Task 10
- `Product::getCity(): ?City`, `getEventType(): EventType`, `getEventStatus(): EventStatus` — defined Task 2
- `ProductVariant::setStartsAt(?\DateTimeInterface)`, `setEndsAt(?\DateTimeInterface)`, `setVenue(?Venue)` — defined Task 3, used Task 10
- `ProductVariant::validateDates(self, ExecutionContextInterface)` — uses `getStartsAt()`, `getEndsAt()` defined in same Task 3
- `AdminUser::getAdministrationRoles(): Collection<int, AdministrationRole>` — defined Task 4, used Task 9
- `CityTranslation` used in `buildCityMap()` — existing class from Phase 4
- `ProductTypeExtension::getExtendedTypes()` → `Sylius\Bundle\ProductBundle\Form\Type\ProductType`
- `ProductVariantTypeExtension::getExtendedTypes()` → `Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType`
- `AdminUserTypeExtension::getExtendedTypes()` → discovered in Task 9 Step 1 via debug:form
