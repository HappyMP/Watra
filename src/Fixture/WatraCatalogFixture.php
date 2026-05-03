<?php

declare(strict_types=1);

namespace App\Fixture;

use App\Entity\Catalog\City;
use App\Entity\Catalog\CityTranslation;
use App\Entity\Catalog\Venue;
use App\Entity\Catalog\VenueTranslation;
use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class WatraCatalogFixture extends AbstractFixture
{
    public function __construct(private readonly ObjectManager $manager)
    {
    }

    public function load(array $options): void
    {
        $cities = $this->createCities();
        $this->createVenues($cities);

        $this->manager->flush();
    }

    public function getName(): string
    {
        return 'watra_catalog';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
    }

    /** @return array<string, City> */
    private function createCities(): array
    {
        $cityData = [
            'krakow' => 'Kraków',
            'warszawa' => 'Warszawa',
            'wroclaw' => 'Wrocław',
        ];

        $cities = [];
        foreach ($cityData as $key => $name) {
            $city = new City();

            $translation = new CityTranslation();
            $translation->setLocale('pl_PL');
            $translation->setName($name);
            $city->addTranslation($translation);

            $this->manager->persist($city);
            $cities[$key] = $city;
        }

        return $cities;
    }

    /** @param array<string, City> $cities */
    private function createVenues(array $cities): void
    {
        $venueData = [
            ['name' => 'Centrum Kongresowe ICE', 'address' => 'ul. Konopnickiej 17, 30-302 Kraków', 'city' => 'krakow'],
            ['name' => 'Tauron Arena', 'address' => 'ul. Lema 7, 31-571 Kraków', 'city' => 'krakow'],
            ['name' => 'Centrum Nauki Kopernik', 'address' => 'ul. Wybrzeże Kościuszkowskie 20, 00-390 Warszawa', 'city' => 'warszawa'],
            ['name' => 'Hala Stulecia', 'address' => 'ul. Wystawowa 1, 51-618 Wrocław', 'city' => 'wroclaw'],
        ];

        foreach ($venueData as $data) {
            $venue = new Venue();
            $venue->setCity($cities[$data['city']]);

            $translation = new VenueTranslation();
            $translation->setLocale('pl_PL');
            $translation->setName($data['name']);
            $translation->setAddress($data['address']);
            $venue->addTranslation($translation);

            $this->manager->persist($venue);
        }
    }
}
