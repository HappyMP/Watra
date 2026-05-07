<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/products/{id}', requirements: ['id' => '\d+'])]
final class AttendeeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/attendees', name: 'app_admin_attendees', methods: ['GET'])]
    public function index(int $id): Response
    {
        $product = $this->em->find(Product::class, $id);
        if ($product === null) {
            throw new NotFoundHttpException();
        }

        $orders = $this->getOrders($id);

        return $this->render('admin/attendees/index.html.twig', [
            'product' => $product,
            'orders' => $orders,
        ]);
    }

    #[Route('/attendees.csv', name: 'app_admin_attendees_csv', methods: ['GET'])]
    public function csv(int $id): StreamedResponse
    {
        $product = $this->em->find(Product::class, $id);
        if ($product === null) {
            throw new NotFoundHttpException();
        }

        $orders = $this->getOrders($id);
        $filename = sprintf('uczestnicy-%s.csv', date('Y-m-d'));

        $response = new StreamedResponse(function () use ($orders): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['Imię', 'Nazwisko', 'Email', 'Termin', 'Numer rezerwacji', 'Status'], ';');

            foreach ($orders as $order) {
                $customer = $order->getCustomer();
                $firstName = $customer?->getFirstName() ?? '';
                $lastName = $customer?->getLastName() ?? '';
                $email = $customer?->getEmail() ?? '';

                $startsAt = '';
                foreach ($order->getItems() as $item) {
                    /** @var OrderItem $item */
                    $variant = $item->getVariant();
                    if ($variant !== null && method_exists($variant, 'getStartsAt') && $variant->getStartsAt() !== null) {
                        $startsAt = $variant->getStartsAt()->format('d.m.Y H:i');

                        break;
                    }
                }

                fputcsv($handle, [
                    $firstName,
                    $lastName,
                    $email,
                    $startsAt,
                    '#' . $order->getNumber(),
                    $order->getState(),
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));

        return $response;
    }

    /** @return Order[] */
    private function getOrders(int $productId): array
    {
        return $this->em->createQueryBuilder()
            ->select('DISTINCT o')
            ->from(Order::class, 'o')
            ->innerJoin('o.items', 'oi')
            ->innerJoin('oi.variant', 'v')
            ->innerJoin('v.product', 'p')
            ->leftJoin('o.customer', 'c')
            ->andWhere('p.id = :productId')
            ->andWhere('o.state NOT IN (:excluded)')
            ->setParameter('productId', $productId)
            ->setParameter('excluded', ['cart', 'cancelled'])
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(200)
            ->getQuery()
            ->getResult();
    }
}
