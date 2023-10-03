<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController
{
    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute(
            'dashboard_index',
            [
                '_locale' => $this->getParameter('app.locale')
            ]
        );
    }

    #[Route(
        '/{_locale}',
        name: 'dashboard_index'
    )]
    public function index(Request $request): Response
    {
        return $this->render(
            'default/dashboard/index.html.twig'
        );
    }
}