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
