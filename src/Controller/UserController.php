<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ActivityService;
use App\Service\UserService;
use App\Service\TorrentService;

class UserController extends AbstractController
{
    #[Route('/')]
    public function root(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        return $this->redirectToRoute(
            'torrent_recent',
            [
                '_locale' => $user->getLocale()
            ]
        );
    }

    #[Route(
        '/{_locale}/settings',
        name: 'user_settings',
        defaults: [
            '_locale' => '%app.locale%'
        ],
        requirements: [
            '_locale' => '%app.locales%',
        ],
    )]
    public function settings(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

            // Update events
            $events = [];
            foreach ((array) $request->get('events') as $event)
            {
                if (in_array($event, $activityService->getEventCodes()))
                {
                    $events[] = $event;
                }
            }

            $user->setEvents(
                $events
            );

            // Update sensitive
            $user->setSensitive(
                $request->get('sensitive') === 'true'
            );

            // Update yggdrasil
            $user->setYggdrasil(
                $request->get('yggdrasil') === 'true'
            );

            // Save changes to DB
            $userService->save($user);

            // Redirect user to new locale
            return $this->redirectToRoute(
                'user_settings',
                [
                    '_locale' => $user->getLocale()
                ]
            );
        }

        // Render template
        return $this->render(
            'default/user/settings.html.twig',
            [
                'user' => [
                    'id'        => $user->getId(),
                    'sensitive' => $user->isSensitive(),
                    'yggdrasil' => $user->isYggdrasil(),
                    'locale'    => $user->getLocale(),
                    'locales'   => $user->getLocales(),
                    'events'    => $user->getEvents(),
                    'theme'     => $user->getTheme(),
                    'added'     => $user->getAdded()
                ],
                'locales' => explode('|', $this->getParameter('app.locales')),
                'themes'  => explode('|', $this->getParameter('app.themes')),
                'events'  => $activityService->getEventsTree()
            ]
        );
    }

    #[Route(
        '/{_locale}/profile/{userId}',
        name: 'user_info',
        defaults: [
            '_locale' => '%app.locale%',
            'userId'  => 0,
        ],
        requirements: [
            '_locale' => '%app.locales%',
            'userId'  => '\d+',
        ],
    )]
    public function info(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        if (!$user->isStatus())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init target user
        if (!$userTarget = $userService->getUser(
            $request->get('userId') ? $request->get('userId') : $user->getId()
        ))
        {
            throw $this->createNotFoundException();
        }

        // Get total activities
        $total = $activityService->findActivitiesTotalByUserId(
            $userTarget->getId(),
            $user->getEvents()
        );

        // Init page
        $page = $request->get('page') ? (int) $request->get('page') : 1;

        // Render template
        return $this->render(
            'default/user/info.html.twig',
            [
                'user' => [
                    'id'        => $userTarget->getId(),
                    'address'   => $userTarget->getId() === $user->getId() ? $userTarget->getAddress() : false,
                    'moderator' => $userTarget->isModerator(),
                    'approved'  => $userTarget->isApproved(),
                    'status'    => $userTarget->isStatus(),
                    'sensitive' => $userTarget->isSensitive(),
                    'yggdrasil' => $userTarget->isYggdrasil(),
                    'locale'    => $userTarget->getLocale(),
                    'locales'   => $userTarget->getLocales(),
                    'events'    => $userTarget->getEvents(),
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
                    'activities' => $activityService->findLastActivitiesByUserId(
                        $userTarget->getId(),
                        $userTarget->getEvents()
                    )
                ],
                'events'     => $activityService->getEventsTree(),
                'activities' => $activityService->findLastActivitiesByUserId(
                    $userTarget->getId(),
                    $user->getEvents(),
                    $this->getParameter('app.pagination'),
                    ($page - 1) * $this->getParameter('app.pagination')
                ),
                'pagination' =>
                [
                    'page'  => $page,
                    'pages' => ceil($total / $this->getParameter('app.pagination')),
                    'total' => $total
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
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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
        $value = $userService->toggleUserStar(
            $user->getId(),
            $userTarget->getId(),
            time()
        );

        // Add activity event
        if ($value)
        {
            $activityService->addEventUserStarAdd(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        else
        {
            $activityService->addEventUserStarDelete(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        // Redirect
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
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Update user moderator
        $value = $userService->toggleUserModerator(
            $userTarget->getId()
        )->isModerator();

        // Add activity event
        if ($value)
        {
            $activityService->addEventUserModeratorAdd(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        else
        {
            $activityService->addEventUserModeratorDelete(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        // Redirect
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
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Update user status
        $value = $userService->toggleUserStatus(
            $userTarget->getId()
        )->isStatus();

        // Add activity event
        if ($value)
        {
            $activityService->addEventUserStatusAdd(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        else
        {
            $activityService->addEventUserStatusDelete(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        // Redirect
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
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

            // @TODO make event for each item
        }

        // Update user approved
        $value = $userService->toggleUserApproved(
            $userTarget->getId()
        )->isApproved();

        // Add activity event
        if ($value)
        {
            $activityService->addEventUserApproveAdd(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        else
        {
            $activityService->addEventUserApproveDelete(
                $user->getId(),
                time(),
                $userTarget->getId()
            );
        }

        // Redirect
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

    private function initUser(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): ?\App\Entity\User
    {
        // Init user
        if (!$user = $userService->findUserByAddress($request->getClientIp()))
        {
            $user = $userService->addUser(
                $request->getClientIp(),
                time(),
                $this->getParameter('app.locale'),
                explode('|', $this->getParameter('app.locales')),
                $activityService->getEventCodes(),
                $this->getParameter('app.theme'),
                $this->getParameter('app.sensitive'),
                $this->getParameter('app.yggdrasil'),
                $this->getParameter('app.approved')
            );

            // Add user join event
            $activityService->addEventUserAdd(
                $user->getId(),
                time()
            );
        }

        return $user;
    }
}