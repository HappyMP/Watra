<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Entity\User\AdminUser;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\RawMinkContext;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Webmozart\Assert\Assert;

final class AuthenticationContext extends RawMinkContext
{
    /**
     * @Given I am logged in as admin :email with password :password
     */
    public function iAmLoggedInAsAdmin(string $email, string $_password): void
    {
        $driver = $this->getSession()->getDriver();
        Assert::isInstanceOf($driver, BrowserKitDriver::class, 'BrowserKit driver required for loginUser');

        /** @var KernelBrowser $client */
        $client = $driver->getClient();

        /** @var AdminUser|null $user */
        $user = $client->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository(AdminUser::class)
            ->findOneBy(['email' => $email]);

        Assert::notNull($user, sprintf('Admin user "%s" not found in test DB', $email));

        $client->loginUser($user, 'admin');
    }
}
