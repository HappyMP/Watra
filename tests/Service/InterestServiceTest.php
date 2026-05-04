<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Customer\Customer;
use App\Entity\Customer\Interest;
use App\Entity\Product\Product;
use App\Repository\Customer\InterestRepository;
use App\Service\InterestService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class InterestServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private InterestRepository&MockObject $repo;
    private InterestService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(InterestRepository::class);
        $this->service = new InterestService($this->em, $this->repo);
    }

    public function testToggleAddsInterestWhenNotExisting(): void
    {
        $customer = new Customer();
        $product = new Product();

        $this->repo->expects(self::once())
            ->method('findOneByCustomerAndProduct')
            ->with($customer, $product)
            ->willReturn(null);

        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Interest::class));
        $this->em->expects(self::once())->method('flush');

        $result = $this->service->toggle($customer, $product);

        self::assertTrue($result);
    }

    public function testToggleRemovesInterestWhenExisting(): void
    {
        $customer = new Customer();
        $product = new Product();
        $interest = new Interest($customer, $product);

        $this->repo->expects(self::once())
            ->method('findOneByCustomerAndProduct')
            ->with($customer, $product)
            ->willReturn($interest);

        $this->em->expects(self::once())->method('remove')->with($interest);
        $this->em->expects(self::once())->method('flush');

        $result = $this->service->toggle($customer, $product);

        self::assertFalse($result);
    }
}
