<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InterestTest extends WebTestCase
{
    public function testGuestSeesLoginLinkOnProductShowPage(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var \Sylius\Component\Core\Repository\ProductRepositoryInterface $productRepo */
        $productRepo = $container->get('sylius.repository.product');
        $products = $productRepo->findAll();

        if (empty($products)) {
            self::markTestSkipped('No products in fixtures');
        }

        $product = $products[0];
        $slug = $product->getTranslation('pl_PL')->getSlug();
        $client->request('GET', '/pl_PL/products/' . $slug);

        self::assertResponseIsSuccessful();
        // The InterestButton renders a login link with a tooltip for guests
        self::assertStringContainsString('Zaloguj się, aby śledzić wydarzenie', (string) $client->getResponse()->getContent());
    }

    public function testLoggedInUserSeesHeartButton(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user, 'Fixture user jan.kowalski@example.pl must exist');

        $client->loginUser($user, 'shop');

        /** @var \Sylius\Component\Core\Repository\ProductRepositoryInterface $productRepo */
        $productRepo = $container->get('sylius.repository.product');
        $products = $productRepo->findAll();

        if (empty($products)) {
            self::markTestSkipped('No products in fixtures');
        }

        $product = $products[0];
        $slug = $product->getTranslation('pl_PL')->getSlug();
        $client->request('GET', '/pl_PL/products/' . $slug);

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        // Live Component renders a button with live#action for logged-in users
        self::assertStringContainsString('live#action', $content);
    }

    public function testAccountInterestsPageRequiresLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/pl_PL/account/interests');

        // Sylius redirects guests to login
        self::assertResponseRedirects();
        self::assertStringContainsString('login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAccountInterestsPageLoadsForLoggedInUser(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepo */
        $userRepo = $container->get('sylius.repository.shop_user');
        $user = $userRepo->findOneByEmail('jan.kowalski@example.pl');
        self::assertNotNull($user);

        $client->loginUser($user, 'shop');
        $client->request('GET', '/pl_PL/account/interests');

        self::assertResponseIsSuccessful();
    }
}
