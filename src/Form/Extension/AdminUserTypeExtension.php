<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Admin\AdministrationRole;
use Sylius\Bundle\AdminBundle\Form\Type\AdminUserType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class AdminUserTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('administrationRoles', EntityType::class, [
            'class' => AdministrationRole::class,
            'label' => 'app.ui.administration_roles',
            'multiple' => true,
            'expanded' => false,
            'required' => false,
            'choice_label' => fn (AdministrationRole $role): string => $role->getName(),
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [AdminUserType::class];
    }
}
