<?php

declare(strict_types=1);

namespace App\Form\Extension;

use App\Entity\Catalog\Venue;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Sylius\Bundle\ProductBundle\Form\Type\ProductVariantType;
use Sylius\Component\Core\Model\ProductVariantInterface;

final class ProductVariantTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startsAt', DateTimeType::class, [
                'label' => 'app.ui.starts_at',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('endsAt', DateTimeType::class, [
                'label' => 'app.ui.ends_at',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('venue', EntityType::class, [
                'class' => Venue::class,
                'label' => 'app.ui.venue',
                'placeholder' => 'app.ui.select_venue',
                'required' => false,
                'choice_label' => fn (Venue $venue): string => $venue->getName(),
            ]);

        // WATRA events are never physically shipped
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $variant = $event->getData();
            if ($variant instanceof ProductVariantInterface) {
                $variant->setShippingRequired(false);
            }
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [ProductVariantType::class];
    }
}
