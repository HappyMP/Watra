<?php

declare(strict_types=1);

namespace App\Controller\Api\Shop;

use App\Entity\Product\Product;
use App\Entity\Product\ProductVariant;
use App\Repository\Product\ProductRepository;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ChannelContextInterface $channelContext,
    ) {}

    #[Route('/api/v2/shop/events', name: 'app_api_shop_events', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        /** @var ChannelInterface $channel */
        $channel = $this->channelContext->getChannel();
        $locale  = $request->query->getString('locale', 'pl_PL');

        $filters = array_filter([
            'city'      => $request->query->getString('city'),
            'eventType' => $request->query->getString('eventType'),
            'search'    => $request->query->getString('search'),
            'dateFrom'  => $request->query->getString('dateFrom'),
        ]);

        $products = $this->productRepository->findFiltered(
            $channel->getCode(),
            $locale,
            $filters,
            50,
        );

        $items = array_map(fn (Product $p) => $this->serializeProduct($p, $channel), $products);

        return $this->json(['total' => count($items), 'items' => $items]);
    }

    /** @return array<string, mixed> */
    private function serializeProduct(Product $product, ChannelInterface $channel): array
    {
        $city  = $product->getCity();
        $venue = $product->getDefaultVenue();

        $images = $product->getImages();
        $imageUrl = $images->isEmpty()
            ? null
            : '/media/image/' . $images->first()->getPath();

        return [
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'slug'        => $product->getSlug(),
            'eventType'   => $product->getEventType()->value,
            'eventStatus' => $product->getEventStatus()->value,
            'isOnline'    => $product->isOnline(),
            'city'        => $city  ? ['name' => $city->getName()]  : null,
            'venue'       => $venue ? ['name' => $venue->getName(), 'address' => $venue->getAddress()] : null,
            'description' => $product->getDescription(),
            'imageUrl'    => $imageUrl,
            'nextVariant' => $this->serializeNextVariant($product, $channel),
        ];
    }

    /** @return array<string, mixed>|null */
    private function serializeNextVariant(Product $product, ChannelInterface $channel): ?array
    {
        $now  = new \DateTimeImmutable();
        $next = null;

        foreach ($product->getVariants() as $variant) {
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

        if ($next === null) {
            return null;
        }

        $pricing = $next->getChannelPricingForChannel($channel);

        return [
            'id'       => $next->getId(),
            'startsAt' => $next->getStartsAt()?->format(\DateTimeInterface::ATOM),
            'endsAt'   => $next->getEndsAt()?->format(\DateTimeInterface::ATOM),
            'price'    => $pricing?->getPrice() ?? 0,
            'onHand'   => $next->getOnHand(),
        ];
    }
}
