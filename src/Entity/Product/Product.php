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
