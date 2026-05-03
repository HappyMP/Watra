<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Catalog\City;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceTranslationsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

final class VenueType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('city', EntityType::class, [
                'class' => City::class,
                'label' => 'app.ui.city',
                'placeholder' => 'app.ui.select_city',
            ])
            ->add('translations', ResourceTranslationsType::class, [
                'entry_type' => VenueTranslationType::class,
            ]);
    }

    public function getBlockPrefix(): string
    {
        return 'app_venue';
    }
}
