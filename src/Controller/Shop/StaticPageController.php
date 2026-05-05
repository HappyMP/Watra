<?php

declare(strict_types=1);

namespace App\Controller\Shop;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StaticPageController extends AbstractController
{
    #[Route('/{_locale}/regulamin', name: 'app_shop_regulamin', methods: ['GET'], requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}'])]
    public function regulamin(): Response
    {
        return $this->render('shop/static/regulamin.html.twig');
    }

    #[Route('/{_locale}/polityka-prywatnosci', name: 'app_shop_privacy', methods: ['GET'], requirements: ['_locale' => '[a-z]{2}_[A-Z]{2}'])]
    public function privacy(): Response
    {
        return $this->render('shop/static/privacy.html.twig');
    }
}
