<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use Doctrine\ORM\EntityManagerInterface;
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
