<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ActivityService;
use App\Service\UserService;

class UserController extends AbstractController
{
    #[Route('/')]
    public function root(
        Request $request,
        UserService $userService
    ): Response
    {
        $user = $userService->init(
            $request->getClientIp()
        );

        return $this->redirectToRoute(
            'user_dashboard',
            [
                '_locale' => $user->getLocale()
            ]
        );
    }

    #[Route(
        '/{_locale}',
        name: 'user_dashboard'
    )]
    public function index(
        Request $request,
        ActivityService $activityService,
        UserService $userService
    ): Response
    {
        // Init user session
        $user = $userService->init(
            $request->getClientIp()
        );

        // Build activity history
        $activities = [];

        /*
        foreach ($activityService->findLast($user->isModerator()) as $activity)
        {
            if (!$activity->getUserId())
            {
                continue;
            }

            $activityUser = $userService->get(
                $activity->getUserId()
            );

            $activities[] =
            [
                'user' =>
                [
                    'id'        => $activityUser->getId(),
                    'identicon' => $userService->identicon(
                        $activityUser->getAddress(),
                        24
                    )
                ],
                'type'  => 'join',
                'added' => $activity->getAdded()
            ];
        }
        */

        return $this->render(
            'default/user/dashboard.html.twig',
            [
                'activities' => $activities
            ]
        );
    }

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
        UserService $userService
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
                $locales = [];
                foreach ((array) $request->get('locales') as $locale)
                {
                    if (in_array($locale, explode('|', $this->getParameter('app.locales'))))
                    {
                        $locales[] = $locale;
                    }
                }

                $user->setLocales(
                    $locales
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
                    'added'     => $user->getAdded(),
                    'identicon' => $userService->identicon(
                        $user->getAddress(),
                        48
                    ),
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
        UserService $userService): Response
    {
        // Init user
        if (!$user = $userService->get($id))
        {
            throw $this->createNotFoundException();
        }

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
                    'added'     => $user->getAdded(),
                    'identicon' => $userService->identicon(
                        $user->getAddress(),
                        48
                    ),
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