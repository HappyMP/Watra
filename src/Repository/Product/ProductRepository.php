<?php

declare(strict_types=1);

namespace App\Repository\Product;

use App\Entity\Product\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Products tagged with taxon code "featured", published, with a future startsAt.
     *
     * @return Product[]
     */
    public function findFeatured(string $channelCode, string $locale, int $limit = 1): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->addSelect('MIN(v.startsAt) as HIDDEN minStartsAt')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.productTaxons', 'pt')
            ->innerJoin('pt.taxon', 'taxon')
            ->innerJoin('p.variants', 'v')
            ->innerJoin('p.channels', 'ch')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('taxon.code = :taxonCode')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('taxonCode', 'featured')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('p.id')
            ->addGroupBy('translation.id')
            ->addOrderBy('minStartsAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Published products with at least one future variant, ordered by nearest startsAt.
     *
     * @param int[] $excludeIds
     *
     * @return Product[]
     */
    public function findUpcoming(string $channelCode, string $locale, int $limit = 4, array $excludeIds = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->addSelect('MIN(v.startsAt) as HIDDEN minStartsAt')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.variants', 'v')
            ->innerJoin('p.channels', 'ch')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('p.id')
            ->addGroupBy('translation.id')
            ->addOrderBy('minStartsAt', 'ASC')
            ->setMaxResults($limit);

        if (!empty($excludeIds)) {
            $qb->andWhere('p.id NOT IN (:excludeIds)')->setParameter('excludeIds', $excludeIds);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Published products in a given city name (case-insensitive).
     *
     * @return Product[]
     */
    public function findByCity(string $cityName, string $channelCode, string $locale, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->addSelect('MIN(v.startsAt) as HIDDEN minStartsAt')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.channels', 'ch')
            ->innerJoin('p.city', 'city')
            ->innerJoin('city.translations', 'cityTranslation')
            ->innerJoin('p.variants', 'v')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('LOWER(cityTranslation.name) = LOWER(:cityName)')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('cityName', $cityName)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('p.id')
            ->addGroupBy('translation.id')
            ->addGroupBy('cityTranslation.id')
            ->addOrderBy('minStartsAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Published online events (isOnline = true) with future variants.
     *
     * @return Product[]
     */
    public function findOnline(string $channelCode, string $locale, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->addSelect('MIN(v.startsAt) as HIDDEN minStartsAt')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.channels', 'ch')
            ->innerJoin('p.variants', 'v')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('p.isOnline = true')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('p.id')
            ->addGroupBy('translation.id')
            ->addOrderBy('minStartsAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns distinct city names (in given locale) for cities that have at least
     * one published product with a future variant in this channel.
     *
     * @return string[]
     */
    public function findDistinctCityNames(string $channelCode, string $locale): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('cityTranslation.name AS cityName')
            ->innerJoin('p.channels', 'ch')
            ->innerJoin('p.city', 'city')
            ->innerJoin('city.translations', 'cityTranslation', 'WITH', 'cityTranslation.locale = :locale')
            ->innerJoin('p.variants', 'v')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('channelCode', $channelCode)
            ->setParameter('locale', $locale)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('cityTranslation.name')
            ->orderBy('cityTranslation.name', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'cityName');
    }

    /**
     * Published products matching optional filters, ordered by nearest future variant.
     *
     * @param array{city?: string, eventType?: string, search?: string, dateFrom?: string} $filters
     *
     * @return Product[]
     */
    public function findFiltered(string $channelCode, string $locale, array $filters, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('translation')
            ->addSelect('MIN(v.startsAt) as HIDDEN minStartsAt')
            ->innerJoin('p.translations', 'translation', 'WITH', 'translation.locale = :locale')
            ->innerJoin('p.variants', 'v')
            ->innerJoin('p.channels', 'ch')
            ->andWhere('ch.code = :channelCode')
            ->andWhere('p.eventStatus = :status')
            ->andWhere('p.enabled = true')
            ->andWhere('v.enabled = true')
            ->andWhere('v.startsAt > :now')
            ->setParameter('locale', $locale)
            ->setParameter('channelCode', $channelCode)
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->addGroupBy('p.id')
            ->addGroupBy('translation.id')
            ->addOrderBy('minStartsAt', 'ASC')
            ->setMaxResults($limit);

        if (!empty($filters['city'])) {
            $qb->innerJoin('p.city', 'city')
                ->innerJoin('city.translations', 'cityTranslation')
                ->andWhere('LOWER(cityTranslation.name) = LOWER(:city)')
                ->addGroupBy('cityTranslation.id')
                ->setParameter('city', $filters['city']);
        }

        if (!empty($filters['eventType'])) {
            $qb->andWhere('p.eventType = :eventType')
                ->setParameter('eventType', $filters['eventType']);
        }

        if (!empty($filters['search'])) {
            $qb->andWhere('LOWER(translation.name) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['dateFrom'])) {
            try {
                $qb->andWhere('v.startsAt >= :dateFrom')
                    ->setParameter('dateFrom', new \DateTimeImmutable($filters['dateFrom']));
            } catch (\Exception) {
                // ignore unparseable date string
            }
        }

        return $qb->getQuery()->getResult();
    }
}
