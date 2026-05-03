<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Catalog\City;
use App\Entity\Catalog\Venue;
use App\Enum\EventStatus;
use App\Enum\EventType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Sylius\Bundle\ProductBundle\Form\Type\ProductType;

final class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('city', EntityType::class, [
                'class' => City::class,
                'label' => 'app.ui.city',
                'placeholder' => 'app.ui.select_city',
                'required' => false,
                'choice_label' => fn (City $city): string => $city->getName(),
            ])
            ->add('defaultVenue', EntityType::class, [
                'class' => Venue::class,
                'label' => 'app.ui.venue',
                'placeholder' => 'app.ui.select_venue',
                'required' => false,
                'choice_label' => fn (Venue $venue): string => $venue->getName(),
            ])
            ->add('isOnline', CheckboxType::class, [
                'label' => 'app.ui.is_online',
                'required' => false,
            ])
            ->add('eventStatus', EnumType::class, [
                'class' => EventStatus::class,
                'label' => 'app.ui.event_status',
                'choice_label' => fn (EventStatus $status): string => 'app.enum.event_status.' . $status->value,
                'choice_translation_domain' => 'messages',
            ])
            ->add('eventType', EnumType::class, [
                'class' => EventType::class,
                'label' => 'app.ui.event_type',
                'choice_label' => fn (EventType $type): string => 'app.enum.event_type.' . $type->value,
                'choice_translation_domain' => 'messages',
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductType::class];
    }
}
