<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\TimeService;

class UserController extends AbstractController
{
    #[Route(
        '/{_locale}/profile',
        name: 'user_profile',
        defaults: [
            '_locale' => '%app.locale%'
        ],
        requirements: [
            '_locale' => '%app.locales%',
        ],
    )]
    public function profile(
        Request $request,
        UserService $userService,
        TimeService $timeService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        // Process post request
        if ($request->isMethod('post'))
        {
            // Update locale
            if (in_array($request->get('locale'), explode('|', $this->getParameter('app.locales'))))
            {
                $user->setLocale(
                    $request->get('locale')
                );
            }

            // Update locales
            if ($request->get('locales'))
            {
                $user->setLocales(
                    $request->get('locales')
                );
            }

            // Save changes to DB
            $userService->save($user);

            // Redirect user to new locale
            return $this->redirectToRoute(
                'user_profile',
                [
                    '_locale' => $user->getLocale()
                ]
            );
        }

        // Generate identicon
        $identicon = new \Jdenticon\Identicon();

        $identicon->setValue($user->getAddress());
        $identicon->setSize(48);
        $identicon->setStyle(
            [
                'backgroundColor' => 'rgba(255, 255, 255, 0)',
                'padding' => 0
            ]
        );

        // Render template
        return $this->render(
            'default/user/profile.html.twig',
            [
                'user' => [
                    'id'        => $user->getId(),
                    'address'   => $request->getClientIp() == $user->getAddress() ? $user->getAddress() : false,
                    'moderator' => $user->isModerator(),
                    'approved'  => $user->isApproved(),
                    'status'    => $user->isStatus(),
                    'locale'    => $user->getLocale(),
                    'locales'   => $user->getLocales(),
                    'added'     => $timeService->ago(
                        $user->getAdded()
                    ),
                    'identicon' => $identicon->getImageDataUri('webp'),
                ],
                'locales' => explode('|', $this->getParameter('app.locales'))
            ]
        );
    }

    #[Route(
        '/{_locale}/user/{id}',
        name: 'user_info',
        defaults: [
            '_locale' => '%app.locale%'
        ],
        requirements: [
            '_locale' => '%app.locales%',
        ],
    )]
    public function info(
        int $id,
        Request $request,
        UserService $userService,
        TimeService $timeService): Response
    {
        // Init user
        if (!$user = $userService->get($id))
        {
            throw $this->createNotFoundException();
        }

        // Generate identicon
        $identicon = new \Jdenticon\Identicon();

        $identicon->setValue($user->getAddress());
        $identicon->setSize(48);
        $identicon->setStyle(
            [
                'backgroundColor' => 'rgba(255, 255, 255, 0)',
                'padding' => 0
            ]
        );

        // Render template
        return $this->render(
            'default/user/info.html.twig',
            [
                'user' => [
                    'id'        => $user->getId(),
                    'address'   => $request->getClientIp() == $user->getAddress() ? $user->getAddress() : false,
                    'moderator' => $user->isModerator(),
                    'approved'  => $user->isApproved(),
                    'status'    => $user->isStatus(),
                    'locale'    => $user->getLocale(),
                    'locales'   => $user->getLocales(),
                    'added'     => $timeService->ago(
                        $user->getAdded()
                    ),
                    'identicon' => $identicon->getImageDataUri('webp'),
                ]
            ]
        );
    }

    public function module(string $route = ''): Response
    {
        return $this->render(
            'default/user/module.html.twig',
            [
                'route'     => $route,
                'stars'     => 0,
                'views'     => 0,
                'comments'  => 0,
                'downloads' => 0,
                'editions'  => 0,
            ]
        );
    }
}