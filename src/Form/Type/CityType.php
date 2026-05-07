<?php

declare(strict_types=1);

namespace App\Form\Type;

use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Component\Form\FormBuilderInterface;

final class CityType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('translations', ResourceTranslationsType::class, [
            'entry_type' => CityTranslationType::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'app_city';
    }
}
