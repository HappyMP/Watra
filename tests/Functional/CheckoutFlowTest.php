<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CheckoutFlowTest extends WebTestCase
{
    public function testEventVariantsHaveShippingDisabled(): void
    {
        $container = static::getContainer();

        /** @var ProductVariantRepositoryInterface $variantRepo */
        $variantRepo = $container->get('sylius.repository.product_variant');
        $variants = $variantRepo->findAll();

        self::assertNotEmpty($variants, 'Fixtures must provide at least one product variant');

        foreach ($variants as $variant) {
            self::assertFalse(
                $variant->isShippingRequired(),
                sprintf('Variant "%s" must have shippingRequired=false', $variant->getCode()),
            );
        }
    }

    public function testCheckoutAddressPageIsAccessibleWhenLoggedIn(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user, 'Fixture user jan.kowalski@example.pl must exist');

        $client->loginUser($user, 'shop');

        $client->request('GET', '/pl_PL/checkout/address');
        // With empty cart, Sylius redirects to cart summary — still accessible (not 500)
        self::assertThat(
            $client->getResponse()->getStatusCode(),
            self::logicalOr(
                self::equalTo(200),
                self::equalTo(302),
            ),
        );
        self::assertNotSame(500, $client->getResponse()->getStatusCode());
    }

    public function testThankYouPageRouteExists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pl_PL/order/thank-you');
        self::assertThat(
            $client->getResponse()->getStatusCode(),
            self::logicalOr(
                self::equalTo(200),
                self::equalTo(302),
            ),
        );
    }
}
