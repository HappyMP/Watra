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
