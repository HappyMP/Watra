<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer\Customer;
use App\Entity\Customer\Interest;
use App\Entity\Product\Product;
use App\Repository\Customer\InterestRepository;
use Doctrine\ORM\EntityManagerInterface;

final class InterestService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly InterestRepository $interestRepository,
    ) {
    }

    public function toggle(Customer $customer, Product $product): bool
    {
        $existing = $this->interestRepository->findOneByCustomerAndProduct($customer, $product);

        if ($existing !== null) {
            $this->em->remove($existing);
            $this->em->flush();

            return false;
        }

        $this->em->persist(new Interest($customer, $product));
        $this->em->flush();

        return true;
    }
}
