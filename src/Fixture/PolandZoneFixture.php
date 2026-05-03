<?php

declare(strict_types=1);

namespace App\Fixture;

use Doctrine\Persistence\ObjectManager;
use Sylius\Bundle\FixturesBundle\Fixture\AbstractFixture;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Addressing\Model\ZoneMemberInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class PolandZoneFixture extends AbstractFixture
{
    /**
     * @param FactoryInterface<ZoneInterface> $zoneFactory
     * @param FactoryInterface<ZoneMemberInterface> $zoneMemberFactory
     * @param RepositoryInterface<ZoneInterface> $zoneRepository
     */
    public function __construct(
        private readonly FactoryInterface $zoneFactory,
        private readonly FactoryInterface $zoneMemberFactory,
        private readonly ObjectManager $zoneManager,
        private readonly RepositoryInterface $zoneRepository,
    ) {
    }

    public function load(array $options): void
    {
        if (null !== $this->zoneRepository->findOneBy(['code' => 'PL'])) {
            return;
        }

        /** @var ZoneInterface $zone */
        $zone = $this->zoneFactory->createNew();
        $zone->setCode('PL');
        $zone->setName('Polska');
        $zone->setType(ZoneInterface::TYPE_COUNTRY);

        /** @var ZoneMemberInterface $member */
        $member = $this->zoneMemberFactory->createNew();
        $member->setCode('PL');
        $zone->addMember($member);

        $this->zoneManager->persist($zone);
        $this->zoneManager->flush();
    }

    public function getName(): string
    {
        return 'poland_zone';
    }

    protected function configureOptionsNode(ArrayNodeDefinition $optionsNode): void
    {
    }
}
