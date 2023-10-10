<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ActivityService;
use App\Service\UserService;
use App\Service\ArticleService;
use App\Service\TorrentService;

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

            $activityUser = $userService->getUser(
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

            // Update theme
            if (in_array($request->get('theme'), explode('|', $this->getParameter('app.themes'))))
            {
                $user->setTheme(
                    $request->get('theme')
                );
            }

            // Update sensitive
            $user->setSensitive(
                $request->get('sensitive') === 'true'
            );

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
                    'sensitive' => $user->isSensitive(),
                    'locale'    => $user->getLocale(),
                    'locales'   => $user->getLocales(),
                    'theme'     => $user->getTheme(),
                    'added'     => $user->getAdded(),
                    'identicon' => $userService->identicon(
                        $user->getAddress(),
                        48
                    ),
                ],
                'locales' => explode('|', $this->getParameter('app.locales')),
                'themes'  => explode('|', $this->getParameter('app.themes'))
            ]
        );
    }

    #[Route(
        '/{_locale}/user/{userId}',
        name: 'user_info',
        defaults: [
            '_locale' => '%app.locale%'
        ],
        requirements: [
            '_locale' => '%app.locales%',
        ],
    )]
    public function info(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isStatus())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init target user
        if (!$userTarget = $userService->getUser($request->get('userId')))
        {
            throw $this->createNotFoundException();
        }

        // Render template
        return $this->render(
            'default/user/info.html.twig',
            [
                'user' => [
                    'id'        => $userTarget->getId(),
                    'address'   => $request->getClientIp() == $userTarget->getAddress() ? $userTarget->getAddress() : false,
                    'moderator' => $userTarget->isModerator(),
                    'approved'  => $userTarget->isApproved(),
                    'status'    => $userTarget->isStatus(),
                    'sensitive' => $userTarget->isSensitive(),
                    'locale'    => $userTarget->getLocale(),
                    'locales'   => $userTarget->getLocales(),
                    'theme'     => $userTarget->getTheme(),
                    'added'     => $userTarget->getAdded(),
                    'identicon' => $userService->identicon(
                        $userTarget->getAddress(),
                        48
                    ),
                    'owner'     => $user->getId() === $userTarget->getId(),
                    'star'      =>
                    [
                        'exist' => (bool) $userService->findUserStar(
                            $user->getId(),
                            $userTarget->getId()
                        ),
                        'total' => $userService->findUserStarsTotalByUserIdTarget(
                            $userTarget->getId()
                        )
                    ],
                ]
            ]
        );
    }

    #[Route(
        '/{_locale}/user/star/toggle/{userId}',
        name: 'user_star_toggle',
        requirements:
        [
            'userId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function toggleStar(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isStatus())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init target user
        if (!$userTarget = $userService->getUser($request->get('userId')))
        {
            throw $this->createNotFoundException();
        }

        // Update
        $userService->toggleUserStar(
            $user->getId(),
            $userTarget->getId(),
            time()
        );

        // Redirect to info article created
        return $this->redirectToRoute(
            'user_info',
            [
                '_locale' => $request->get('_locale'),
                'userId'  => $userTarget->getId()
            ]
        );
    }

    #[Route(
        '/{_locale}/user/{userId}/moderator/toggle',
        name: 'user_moderator_toggle',
        requirements:
        [
            'userId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function toggleModerator(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isModerator())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init target user
        if (!$userTarget = $userService->getUser($request->get('userId')))
        {
            throw $this->createNotFoundException();
        }

        // Update
        $userService->toggleUserModerator(
            $userTarget->getId()
        );

        // Redirect to info article created
        return $this->redirectToRoute(
            'user_info',
            [
                '_locale' => $request->get('_locale'),
                'userId'  => $userTarget->getId()
            ]
        );
    }

    #[Route(
        '/{_locale}/user/{userId}/status/toggle',
        name: 'user_status_toggle',
        requirements:
        [
            'userId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function toggleStatus(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isModerator())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init target user
        if (!$userTarget = $userService->getUser($request->get('userId')))
        {
            throw $this->createNotFoundException();
        }

        // Update
        $userService->toggleUserStatus(
            $userTarget->getId()
        );

        // Redirect to info article created
        return $this->redirectToRoute(
            'user_info',
            [
                '_locale' => $request->get('_locale'),
                'userId'  => $userTarget->getId()
            ]
        );
    }

    #[Route(
        '/{_locale}/user/{userId}/approved/toggle',
        name: 'user_approved_toggle',
        requirements:
        [
            'userId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function toggleApproved(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        ArticleService $articleService,
        TorrentService $torrentService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isModerator())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init target user
        if (!$userTarget = $userService->getUser($request->get('userId')))
        {
            throw $this->createNotFoundException();
        }

        // Auto-approve all related content on user approve
        if (!$userTarget->isApproved())
        {
            $torrentService->setTorrentsApprovedByUserId(
                $userTarget->getId(),
                true
            );

            $torrentService->setTorrentLocalesApprovedByUserId(
                $userTarget->getId(),
                true
            );

            $torrentService->setTorrentSensitivesApprovedByUserId(
                $userTarget->getId(),
                true
            );
        }

        // Update user approved
        $userService->toggleUserApproved(
            $userTarget->getId()
        );

        // Redirect to info article created
        return $this->redirectToRoute(
            'user_info',
            [
                '_locale' => $request->get('_locale'),
                'userId'  => $userTarget->getId()
            ]
        );
    }

    public function module(?string $route): Response
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