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
    ) {
    }

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
