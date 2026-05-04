<?php

declare(strict_types=1);

namespace App\Twig\Component;

use App\Entity\Customer\Customer;
use App\Entity\Product\Product;
use App\Repository\Customer\InterestRepository;
use App\Service\InterestService;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('InterestButton')]
final class InterestButton
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $productId = 0;

    private ?Product $resolvedProduct = null;

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly InterestRepository $interestRepository,
        private readonly InterestService $interestService,
        private readonly Security $security,
    ) {}

    public function mount(int $productId): void
    {
        $this->productId = $productId;
    }

    private function getProduct(): Product
    {
        if ($this->resolvedProduct === null) {
            /** @var Product $product */
            $product = $this->productRepository->find($this->productId);
            $this->resolvedProduct = $product;
        }

        return $this->resolvedProduct;
    }

    private function getCustomer(): ?Customer
    {
        $user = $this->security->getUser();
        if (!$user instanceof \Sylius\Component\Core\Model\ShopUserInterface) {
            return null;
        }

        /** @var Customer $customer */
        $customer = $user->getCustomer();

        return $customer;
    }

    public function isInterested(): bool
    {
        $customer = $this->getCustomer();
        if ($customer === null) {
            return false;
        }

        return $this->interestRepository->findOneByCustomerAndProduct($customer, $this->getProduct()) !== null;
    }

    public function getCount(): int
    {
        return $this->interestRepository->countByProduct($this->getProduct());
    }

    #[LiveAction]
    public function toggle(): void
    {
        $customer = $this->getCustomer();
        if ($customer === null) {
            throw new UnauthorizedHttpException('shop', 'Login required.');
        }

        $this->interestService->toggle($customer, $this->getProduct());
    }
}
