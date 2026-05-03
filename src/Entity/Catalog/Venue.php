<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Resource\Model\ResourceInterface;
use Sylius\Resource\Model\TranslatableInterface;
use Sylius\Resource\Model\TranslatableTrait;

#[ORM\Entity]
#[ORM\Table(name: 'watra_venue')]
class Venue implements ResourceInterface, TranslatableInterface
{
    use TranslatableTrait {
        __construct as private initializeTranslationsCollection;
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: City::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?City $city = null;

    public function __construct()
    {
        $this->initializeTranslationsCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): void
    {
        $this->city = $city;
    }

    public function getName(): string
    {
        /** @var VenueTranslation $translation */
        $translation = $this->getTranslation();

        return $translation->getName();
    }

    public function getAddress(): string
    {
        /** @var VenueTranslation $translation */
        $translation = $this->getTranslation();

        return $translation->getAddress();
    }

    protected function createTranslation(): VenueTranslation
    {
        return new VenueTranslation();
    }
}
