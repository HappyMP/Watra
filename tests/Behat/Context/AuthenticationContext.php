<?php

declare(strict_types=1);

namespace App\Tests\Behat\Context;

use App\Entity\User\AdminUser;
use Behat\Mink\Driver\BrowserKitDriver;
use Behat\MinkExtension\Context\RawMinkContext;
use Doctrine\ORM\EntityManagerInterface;
use Webmozart\Assert\Assert;

final class AuthenticationContext extends RawMinkContext
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @Given I am logged in as admin :email with password :password
     */
    public function iAmLoggedInAsAdmin(string $email, string $_password): void
    {
        /** @var AdminUser|null $user */
        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['email' => $email]);
        Assert::notNull($user, sprintf('Admin user "%s" not found in test DB', $email));

        $driver = $this->getSession()->getDriver();
        Assert::isInstanceOf($driver, BrowserKitDriver::class, 'BrowserKit driver required for loginUser');

        $driver->getClient()->loginUser($user, 'admin');
    }
}
