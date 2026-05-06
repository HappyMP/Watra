<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Product\Product;
use App\Enum\EventType;
use App\Repository\Product\ProductRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('EventListFilters', route: 'sylius_shop_live_component')]
final class EventListFiltersComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $city = '';

    #[LiveProp(writable: true)]
    public string $eventType = '';

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public string $dateFrom = '';

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ChannelContextInterface $channelContext,
        private readonly LocaleContextInterface $localeContext,
    ) {}

    /** @return Product[] */
    #[ExposeInTemplate]
    public function getProducts(): array
    {
        return $this->productRepository->findFiltered(
            $this->channelContext->getChannel()->getCode(),
            $this->localeContext->getLocaleCode(),
            [
                'city'      => $this->city,
                'eventType' => $this->eventType,
                'search'    => $this->search,
                'dateFrom'  => $this->dateFrom,
            ],
        );
    }

    /** @return string[] */
    #[ExposeInTemplate]
    public function getAvailableCities(): array
    {
        return $this->productRepository->findDistinctCityNames(
            $this->channelContext->getChannel()->getCode(),
            $this->localeContext->getLocaleCode(),
        );
    }

    /** @return EventType[] */
    #[ExposeInTemplate]
    public function getEventTypes(): array
    {
        return EventType::cases();
    }
}
