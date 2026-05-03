<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Security\Permission;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class AdministrationRoleType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'sylius.ui.name',
            ])
            ->add('isSuperAdmin', CheckboxType::class, [
                'label' => 'app.ui.super_admin',
                'required' => false,
            ])
            ->add('permissions', ChoiceType::class, [
                'label' => false,
                'choices' => $this->buildGroupedChoices(),
                'choice_translation_domain' => 'messages',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]);
    }

    /**
     * Returns choices grouped by resource prefix.
     * Format: ['Group Label' => ['Permission label' => 'permission:value']]
     *
     * @return array<string, array<string, string>>
     */
    private function buildGroupedChoices(): array
    {
        $groups = [];

        foreach (Permission::resources() as $resource) {
            $groupLabel = 'app.ui.permission_group_' . $resource;
            $groups[$groupLabel] = [];

            foreach (Permission::group($resource) as $permission) {
                $labelKey = 'app.permission.' . str_replace(':', '_', $permission->value);
                $groups[$groupLabel][$labelKey] = $permission->value;
            }
        }

        return $groups;
    }

    public function getBlockPrefix(): string
    {
        return 'app_administration_role';
    }
}
