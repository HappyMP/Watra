<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CartControllerTest extends WebTestCase
{
    public function testGuestIsRedirectedToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pl_PL/cart/add-variant/SOME-CODE');

        self::assertResponseRedirects();
        self::assertStringContainsString('login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testUnknownVariantReturns404(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user, 'Fixture user jan.kowalski@example.pl must exist');

        $client->loginUser($user, 'shop');
        $client->request('GET', '/pl_PL/cart/add-variant/NONEXISTENT-CODE-999');

        self::assertResponseStatusCodeSame(404);
    }

    public function testLoggedInUserCanAddVariantToCart(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user);

        /** @var ProductVariantRepositoryInterface $variantRepo */
        $variantRepo = $container->get('sylius.repository.product_variant');
        $variants = $variantRepo->findAll();
        if (empty($variants)) {
            self::markTestSkipped('No variants in fixtures');
        }

        $client->loginUser($user, 'shop');
        $client->request('GET', '/pl_PL/cart/add-variant/' . $variants[0]->getCode());

        self::assertResponseRedirects();
        self::assertNotSame(500, $client->getResponse()->getStatusCode());
    }
}
