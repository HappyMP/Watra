<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Admin\AdministrationRole;
use App\Entity\User\AdminUser;
use App\Security\Permission;
use App\Security\Voter\PermissionVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PermissionVoterTest extends TestCase
{
    private PermissionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new PermissionVoter();
    }

    private function makeToken(AdminUser $user): UsernamePasswordToken
    {
        return new UsernamePasswordToken($user, 'admin', $user->getRoles());
    }

    public function testSupportsAllPermissionStrings(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        foreach (Permission::all() as $perm) {
            self::assertSame(
                VoterInterface::ACCESS_GRANTED,
                $this->voter->vote($this->makeToken($superAdmin), null, [$perm]),
                "Voter should grant super admin permission: $perm",
            );
        }
    }

    public function testAbstainsForUserWithNoRoles(): void
    {
        $user = new AdminUser();
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->makeToken($user), null, [Permission::EVENT_CREATE->value]),
        );
    }

    public function testGrantsForSuperAdminRole(): void
    {
        $user = $this->makeSuperAdmin();
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->makeToken($user), null, [Permission::EVENT_DELETE->value]),
        );
    }

    public function testGrantsSpecificPermission(): void
    {
        $role = new AdministrationRole();
        $role->setPermissions([Permission::BOOKING_INDEX->value]);

        $user = new AdminUser();
        $user->addAdministrationRole($role);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->makeToken($user), null, [Permission::BOOKING_INDEX->value]),
        );
    }

    public function testDeniesPermissionNotInRole(): void
    {
        $role = new AdministrationRole();
        $role->setPermissions([Permission::BOOKING_INDEX->value]);

        $user = new AdminUser();
        $user->addAdministrationRole($role);

        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->makeToken($user), null, [Permission::EVENT_CREATE->value]),
        );
    }

    public function testAbstainsForNonPermissionAttribute(): void
    {
        $user = $this->makeSuperAdmin();
        self::assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->makeToken($user), null, ['ROLE_ADMIN']),
        );
    }

    private function makeSuperAdmin(): AdminUser
    {
        $role = new AdministrationRole();
        $role->setIsSuperAdmin(true);

        $user = new AdminUser();
        $user->addAdministrationRole($role);

        return $user;
    }
}
