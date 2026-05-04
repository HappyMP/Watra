<?php

declare(strict_types=1);

namespace App\Repository\Customer;

use App\Entity\Customer\Customer;
use App\Entity\Customer\Interest;
use App\Entity\Product\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Interest>
 */
final class InterestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interest::class);
    }

    /** @return Interest[] */
    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCustomerAndProduct(Customer $customer, Product $product): ?Interest
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.customer = :customer')
            ->andWhere('i.product = :product')
            ->setParameter('customer', $customer)
            ->setParameter('product', $product)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByProduct(Product $product): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->andWhere('i.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
