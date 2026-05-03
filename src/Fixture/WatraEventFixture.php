<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Entity\Product\Product;
use App\Entity\Product\ProductTaxon;
use App\Entity\Product\ProductTranslation;
use App\Entity\Product\ProductVariant;
use App\Entity\Product\ProductVariantTranslation;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Taxation\Repository\TaxCategoryRepositoryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class WatraEventFixture extends AbstractFixture
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ChannelRepositoryInterface $channelRepository,
        private readonly TaxonRepositoryInterface $taxonRepository,
        private readonly TaxCategoryRepositoryInterface $taxCategoryRepository,
        /** @var FactoryInterface<ChannelPricingInterface> */
        private readonly FactoryInterface $channelPricingFactory,
    ) {
    }

    public function load(array $options): void
    {
        $channel = $this->channelRepository->findOneByCode('WATRA');
        $taxCategory = $this->taxCategoryRepository->findOneBy(['code' => 'default']);

        $events = [
            [
                'name' => 'Warsztaty PHP 8.3 — nowoczesne wzorce',
                'code' => 'WATRA-PHP-83',
                'slug' => 'warsztaty-php-83-nowoczesne-wzorce',
                'description' => 'Intensywne warsztaty z nowoczesnego PHP 8.3: enumy, fibry, readonly properties, intersect types. Warsztat praktyczny — piszemy kod przez cały dzień.',
                'shortDescription' => 'Praktyczne warsztaty PHP 8.3 dla średniozaawansowanych i zaawansowanych.',
                'taxon' => 'WATRA_WARSZTATY',
                'variants' => [
                    ['name' => '15 czerwca 2026 — Kraków', 'code' => 'WATRA-PHP-83-KRK-0615', 'onHand' => 20],
                    ['name' => '20 lipca 2026 — Kraków', 'code' => 'WATRA-PHP-83-KRK-0720', 'onHand' => 20],
                ],
            ],
            [
                'name' => 'Meetup Laravel — najlepsze praktyki 2026',
                'code' => 'WATRA-LARAVEL-01',
                'slug' => 'meetup-laravel-najlepsze-praktyki-2026',
                'description' => 'Comiesięczny meetup społeczności Laravel w Warszawie. Trzy prezentacje, networking, pizza. Dołącz do kilkudziesięciu developerów!',
                'shortDescription' => 'Comiesięczny meetup społeczności Laravel — prezentacje i networking.',
                'taxon' => 'WATRA_MEETUPY',
                'variants' => [
                    ['name' => '8 czerwca 2026 — Warszawa', 'code' => 'WATRA-LARAVEL-WAW-0608', 'onHand' => 60],
                ],
            ],
            [
                'name' => 'PHPCon Poland 2026',
                'code' => 'WATRA-PHPCON-2026',
                'slug' => 'phpcon-poland-2026',
                'description' => 'Największa konferencja PHP w Polsce. Dwa dni, trzy ścieżki tematyczne, ponad 30 prelegentów z kraju i ze świata. Tematy: architektura, DDD, testy, bezpieczeństwo.',
                'shortDescription' => 'Największa konferencja PHP w Polsce — dwa dni, 30+ prelegentów.',
                'taxon' => 'WATRA_KONFERENCJE',
                'variants' => [
                    ['name' => 'Dzień 1 — 15 września 2026', 'code' => 'WATRA-PHPCON-D1', 'onHand' => 300],
                    ['name' => 'Dzień 2 — 16 września 2026', 'code' => 'WATRA-PHPCON-D2', 'onHand' => 300],
                    ['name' => 'Bilet 2-dniowy', 'code' => 'WATRA-PHPCON-2D', 'onHand' => 250],
                ],
            ],
            [
                'name' => 'Warsztaty Vue.js + Inertia',
                'code' => 'WATRA-VUE-INERTIA',
                'slug' => 'warsztaty-vuejs-inertia',
                'description' => 'Jeden dzień z Vue.js i Inertia.js — jak budować SPA bez API i bez bólu głowy. Integracja z Laravelem i Symfony. Wymagana znajomość JavaScript.',
                'shortDescription' => 'Warsztaty Vue.js + Inertia.js — nowoczesny frontend bez API.',
                'taxon' => 'WATRA_WARSZTATY',
                'variants' => [
                    ['name' => '5 lipca 2026 — Kraków', 'code' => 'WATRA-VUE-KRK-0705', 'onHand' => 15],
                ],
            ],
            [
                'name' => 'Meetup Symfony UX — Live Components w akcji',
                'code' => 'WATRA-SYMFONYUX-01',
                'slug' => 'meetup-symfony-ux-live-components',
                'description' => 'Spotkanie poświęcone Symfony UX: Live Components, Stimulus, Turbo i TwigComponents. Dwa live-coding demo i dyskusja.',
                'shortDescription' => 'Spotkanie o Symfony UX — Live Components, Stimulus, Turbo.',
                'taxon' => 'WATRA_MEETUPY',
                'variants' => [
                    ['name' => '22 czerwca 2026 — Warszawa', 'code' => 'WATRA-SYMFONYUX-WAW-0622', 'onHand' => 45],
                ],
            ],
        ];

        foreach ($events as $eventData) {
            $this->createEvent($eventData, $channel, $taxCategory);
        }

        $this->em->flush();
    }

    public function getName(): string
    {
        return 'watra_events';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
    }

    private function createEvent(array $data, $channel, $taxCategory): void
    {
        $product = new Product();
        $product->setCode($data['code']);
        $product->setEnabled(true);
        $product->setVariantSelectionMethod(Product::VARIANT_SELECTION_CHOICE);

        $translation = new ProductTranslation();
        $translation->setLocale('pl_PL');
        $translation->setName($data['name']);
        $translation->setSlug($data['slug']);
        $translation->setDescription($data['description']);
        $translation->setShortDescription($data['shortDescription']);
        $product->addTranslation($translation);

        $product->addChannel($channel);

        $taxon = $this->taxonRepository->findOneBy(['code' => $data['taxon']]);
        if ($taxon !== null) {
            $product->setMainTaxon($taxon);

            $productTaxon = new ProductTaxon();
            $productTaxon->setProduct($product);
            $productTaxon->setTaxon($taxon);
            $product->addProductTaxon($productTaxon);
            $this->em->persist($productTaxon);
        }

        $this->em->persist($product);

        foreach ($data['variants'] as $variantData) {
            $variant = new ProductVariant();
            $variant->setCode($variantData['code']);
            $variant->setEnabled(true);
            $variant->setTracked(true);
            $variant->setOnHand($variantData['onHand']);
            $variant->setShippingRequired(false);
            $variant->setTaxCategory($taxCategory);

            $variantTranslation = new ProductVariantTranslation();
            $variantTranslation->setLocale('pl_PL');
            $variantTranslation->setName($variantData['name']);
            $variant->addTranslation($variantTranslation);

            /** @var ChannelPricingInterface $channelPricing */
            $channelPricing = $this->channelPricingFactory->createNew();
            $channelPricing->setChannelCode('WATRA');
            $channelPricing->setPrice(0);
            $channelPricing->setOriginalPrice(0);
            $variant->addChannelPricing($channelPricing);

            $product->addVariant($variant);
            $this->em->persist($variant);
        }
    }
}
