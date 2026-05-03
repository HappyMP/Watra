<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Entity\Admin\AdministrationRole;
use App\Entity\User\AdminUser;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RbacTest extends WebTestCase
{
    public function testFrontDeskCannotAccessEventCreate(): void
    {
        $client = static::createClient();
        $em = $this->getEm();
        $user = $this->createFrontDeskUser($em, 'fd1@rbactest.watra');
        $this->loginAs($client, $user);

        $client->request('GET', '/admin/products/new');

        self::assertResponseStatusCodeSame(403);
    }

    public function testFrontDeskCanAccessBookings(): void
    {
        $client = static::createClient();
        $em = $this->getEm();
        $user = $this->createFrontDeskUser($em, 'fd2@rbactest.watra');
        $this->loginAs($client, $user);

        $client->request('GET', '/admin/orders/');

        self::assertNotSame(403, $client->getResponse()->getStatusCode());
    }

    public function testFrontDeskSidebarDoesNotContainEventCreateLink(): void
    {
        $client = static::createClient();
        $em = $this->getEm();
        $user = $this->createFrontDeskUser($em, 'fd3@rbactest.watra');
        $this->loginAs($client, $user);

        $client->request('GET', '/admin/');
        $content = (string) $client->getResponse()->getContent();

        self::assertStringNotContainsString('/admin/products/new', $content);
    }

    public function testSuperAdminCanAccessEventCreate(): void
    {
        $client = static::createClient();
        $em = $this->getEm();
        $user = $this->createSuperAdminUser($em, 'sa1@rbactest.watra');
        $this->loginAs($client, $user);

        $client->request('GET', '/admin/products/new');

        self::assertNotSame(403, $client->getResponse()->getStatusCode());
    }

    private function getEm(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        return $em;
    }

    private function createFrontDeskUser(EntityManagerInterface $em, string $email): AdminUser
    {
        $role = new AdministrationRole();
        $role->setName('Test FD ' . $email);
        $role->setIsSuperAdmin(false);
        $role->setPermissions([
            Permission::BOOKING_INDEX->value,
            Permission::BOOKING_SHOW->value,
            Permission::ATTENDEE_INDEX->value,
            Permission::ATTENDEE_SHOW->value,
            Permission::DASHBOARD_ACCESS->value,
        ]);
        $em->persist($role);

        return $this->persistUser($em, $email, $role);
    }

    private function createSuperAdminUser(EntityManagerInterface $em, string $email): AdminUser
    {
        $role = new AdministrationRole();
        $role->setName('Test SA ' . $email);
        $role->setIsSuperAdmin(true);
        $role->setPermissions([]);
        $em->persist($role);

        return $this->persistUser($em, $email, $role);
    }

    private function persistUser(EntityManagerInterface $em, string $email, AdministrationRole $role): AdminUser
    {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $user = new AdminUser();
        $user->setEmail($email);
        $user->setUsername($email);
        $user->setEnabled(true);
        $user->setLocaleCode('pl_PL');
        $user->setPassword($hasher->hashPassword($user, 'testpass'));
        $user->addAdministrationRole($role);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function loginAs(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, AdminUser $user): void
    {
        $client->loginUser($user, 'admin');
    }
}
