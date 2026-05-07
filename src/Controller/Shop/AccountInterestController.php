<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use App\Entity\Customer\Customer;
use App\Entity\Product\Product;
use App\Repository\Customer\InterestRepository;
use App\Service\InterestService;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class AccountInterestController extends AbstractController
{
    /**
     * @param ProductRepositoryInterface<\Sylius\Component\Core\Model\ProductInterface> $productRepository
     */
    public function __construct(
        private readonly InterestRepository $interestRepository,
        private readonly InterestService $interestService,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    #[Route('/{_locale}/account/interests', name: 'app_shop_account_interests', methods: ['GET'], requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}'])]
    public function index(): Response
    {
        /** @var \Sylius\Component\Core\Model\ShopUserInterface $user */
        $user = $this->getUser();
        /** @var Customer $customer */
        $customer = $user->getCustomer();

        $interests = $this->interestRepository->findByCustomer($customer);

        return $this->render('shop/account/interests.html.twig', [
            'interests' => $interests,
        ]);
    }

    #[Route('/{_locale}/account/interests/{productId}/toggle', name: 'app_shop_interest_toggle', methods: ['POST'], requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}', 'productId' => '\d+'])]
    public function toggle(Request $request, int $productId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \Sylius\Component\Core\Model\ShopUserInterface $user */
        $user = $this->getUser();
        /** @var Customer $customer */
        $customer = $user->getCustomer();

        /** @var Product $product */
        $product = $this->productRepository->find($productId);
        if ($product === null) {
            throw $this->createNotFoundException();
        }

        $this->interestService->toggle($customer, $product);

        return $this->redirectToRoute('app_shop_account_interests', ['_locale' => $request->getLocale()]);
    }
}
