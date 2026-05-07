<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order\Order;
use App\Entity\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\Customer;

final class DashboardMetricsService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getPublishedEventsCount(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Product::class, 'p')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->setParameter('status', 'published')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getBookingsLast7Days(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(Order::class, 'o')
            ->andWhere('o.state != :cart')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('cart', 'cart')
            ->setParameter('since', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getNewCustomersLast7Days(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(c.id)')
            ->from(Customer::class, 'c')
            ->andWhere('c.createdAt >= :since')
            ->setParameter('since', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<int, array{name: string, occupancyPct: int}>
     */
    public function getUpcomingOccupancy(int $limit = 8): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('p.id, translation.name, SUM(v.onHand) as totalOnHand, MIN(v.startsAt) as HIDDEN minStartsAt')
            ->from(Product::class, 'p')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.variants', 'v')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->andWhere('v.onHand > 0')
            ->setParameter('status', 'published')
            ->setParameter('locale', 'pl_PL')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('p.id')
            ->addGroupBy('translation.id')
            ->addOrderBy('minStartsAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($rows)) {
            return [];
        }

        $productIds = array_column($rows, 'id');

        $bookingRows = $this->em->createQueryBuilder()
            ->select('prod.id as productId, COUNT(DISTINCT o.id) as cnt')
            ->from(Order::class, 'o')
            ->innerJoin('o.items', 'oi')
            ->innerJoin('oi.variant', 'v')
            ->innerJoin('v.product', 'prod')
            ->andWhere('prod.id IN (:ids)')
            ->andWhere('o.state IN (:states)')
            ->setParameter('ids', $productIds)
            ->setParameter('states', ['fulfilled', 'new', 'paid'])
            ->addGroupBy('prod.id')
            ->getQuery()
            ->getResult();

        $countMap = [];
        foreach ($bookingRows as $row) {
            $countMap[(int) $row['productId']] = (int) $row['cnt'];
        }

        $result = [];
        foreach ($rows as $row) {
            $onHand = (int) $row['totalOnHand'];
            if ($onHand === 0) {
                continue;
            }
            $booked = $countMap[(int) $row['id']] ?? 0;
            $result[] = [
                'name' => $row['name'],
                'occupancyPct' => min(100, (int) round($booked / $onHand * 100)),
            ];
        }

        return $result;
    }

    public function getAvgOccupancy(): int
    {
        $rows = $this->getUpcomingOccupancy(20);
        if (empty($rows)) {
            return 0;
        }

        return (int) round(array_sum(array_column($rows, 'occupancyPct')) / count($rows));
    }
}
