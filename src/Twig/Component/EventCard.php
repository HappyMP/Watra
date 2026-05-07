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
    ) {
    }

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
