<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;

class DashboardController extends AbstractController
{
    #[Route('/')]
    public function root(
        Request $request,
        UserService $userService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        return $this->redirectToRoute(
            'dashboard_index',
            [
                '_locale' => $user->getLocale()
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