<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Admin\AdministrationRole;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;
use Sylius\MolliePlugin\Entity\OnboardingStatusAwareInterface;
use Sylius\MolliePlugin\Entity\OnboardingStatusAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_admin_user')]
class AdminUser extends BaseAdminUser implements OnboardingStatusAwareInterface
{
    use OnboardingStatusAwareTrait;

    /** @var Collection<int, AdministrationRole> */
    #[ORM\ManyToMany(targetEntity: AdministrationRole::class)]
    #[ORM\JoinTable(name: 'watra_admin_user_administration_role')]
    private Collection $administrationRoles;

    public function __construct()
    {
        parent::__construct();
        $this->administrationRoles = new ArrayCollection();
    }

    /** @return Collection<int, AdministrationRole> */
    public function getAdministrationRoles(): Collection
    {
        return $this->administrationRoles;
    }

    public function addAdministrationRole(AdministrationRole $role): void
    {
        if (!$this->administrationRoles->contains($role)) {
            $this->administrationRoles->add($role);
        }
    }

    public function removeAdministrationRole(AdministrationRole $role): void
    {
        $this->administrationRoles->removeElement($role);
    }
}
