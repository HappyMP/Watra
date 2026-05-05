<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Repository\Product\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repo = static::getContainer()->get(ProductRepository::class);
    }

    public function testFindUpcomingReturnsArray(): void
    {
        $results = $this->repo->findUpcoming('WATRA', 'pl_PL', 4);
        self::assertIsArray($results);
    }

    public function testFindUpcomingRespectsLimit(): void
    {
        $results = $this->repo->findUpcoming('WATRA', 'pl_PL', 2);
        self::assertLessThanOrEqual(2, count($results));
    }

    public function testFindByCityReturnsArray(): void
    {
        $results = $this->repo->findByCity('Kraków', 'WATRA', 'pl_PL', 4);
        self::assertIsArray($results);
    }

    public function testFindOnlineReturnsArray(): void
    {
        $results = $this->repo->findOnline('WATRA', 'pl_PL', 4);
        self::assertIsArray($results);
    }

    public function testFindFeaturedReturnsArray(): void
    {
        $results = $this->repo->findFeatured('WATRA', 'pl_PL', 1);
        self::assertIsArray($results);
    }
}
