<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Entity\Admin\AdministrationRole;
use App\Entity\User\AdminUser;
use App\Security\Permission;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdministrationRoleFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(array $options): void
    {
        $superAdmin = $this->createRole('Super Admin', true, []);

        $this->createRole('Editor', false, [
            Permission::EVENT_INDEX->value,
            Permission::EVENT_SHOW->value,
            Permission::EVENT_CREATE->value,
            Permission::EVENT_UPDATE->value,
            Permission::BOOKING_INDEX->value,
            Permission::BOOKING_SHOW->value,
            Permission::BOOKING_UPDATE->value,
            Permission::ATTENDEE_INDEX->value,
            Permission::ATTENDEE_SHOW->value,
            Permission::TAG_INDEX->value,
            Permission::TAG_CREATE->value,
            Permission::TAG_UPDATE->value,
            Permission::DASHBOARD_ACCESS->value,
        ]);

        $this->createRole('Marketer', false, [
            Permission::EVENT_INDEX->value,
            Permission::EVENT_SHOW->value,
            Permission::EVENT_UPDATE->value,
            Permission::TAG_INDEX->value,
            Permission::TAG_CREATE->value,
            Permission::TAG_UPDATE->value,
            Permission::DASHBOARD_ACCESS->value,
        ]);

        $this->createRole('Front Desk', false, [
            Permission::BOOKING_INDEX->value,
            Permission::BOOKING_SHOW->value,
            Permission::BOOKING_UPDATE->value,
            Permission::BOOKING_EXPORT->value,
            Permission::ATTENDEE_INDEX->value,
            Permission::ATTENDEE_SHOW->value,
            Permission::DASHBOARD_ACCESS->value,
        ]);

        $this->em->flush();
        $this->em->refresh($superAdmin);

        $this->createAdminUser('admin@watra.test', 'watra2026!', $superAdmin);

        $this->em->flush();
    }

    public function getName(): string
    {
        return 'watra_roles';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
    }

    /** @param array<string> $permissions */
    private function createRole(string $name, bool $isSuperAdmin, array $permissions): AdministrationRole
    {
        $role = new AdministrationRole();
        $role->setName($name);
        $role->setIsSuperAdmin($isSuperAdmin);
        $role->setPermissions($permissions);
        $this->em->persist($role);

        return $role;
    }

    private function createAdminUser(string $email, string $plainPassword, AdministrationRole $role): void
    {
        $user = new AdminUser();
        $user->setEmail($email);
        $user->setUsername($email);
        $user->setEnabled(true);
        $user->setLocaleCode('pl_PL');
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->addAdministrationRole($role);
        $this->em->persist($user);
    }
}
