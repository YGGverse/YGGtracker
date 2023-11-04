<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ActivityService;
use App\Service\UserService;
use App\Service\TorrentService;

class ActivityController extends AbstractController
{
    #[Route(
        '/{_locale}/activity',
        name: 'activity_all',
        requirements: [
            '_locale' => '%app.locales%'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function all(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        $total = $activityService->findActivitiesTotal(
            $user->getEvents()
        );

        $page = $request->get('page') ? (int) $request->get('page') : 1;

        return $this->render(
            'default/activity/list.html.twig',
            [
                'session'    => [
                    'user' => $user
                ],
                'activities' => $activityService->findLastActivities(
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
        '/{_locale}/rss/activity',
        name: 'rss_activity',
        requirements: [
            '_locale' => '%app.locales%'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function rssAll(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        $total = $activityService->findActivitiesTotal(
            $user->getEvents()
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'default/activity/list.rss.twig',
            [
                'session'    => [
                    'user' => $user
                ],
                'activities' => $activityService->findLastActivities(
                    $user->getEvents()
                )
            ],
            $response
        );
    }

    #[Route(
        '/{_locale}/rss/activity/user/{userId}',
        name: 'rss_activity_user',
        defaults: [
            'userId'  => 0
        ],
        requirements: [
            '_locale' => '%app.locales%',
            'userId'  => '\d+'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function rssUser(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        // Init target user
        if (!$userTarget = $userService->getUser(
            $request->get('userId') ? $request->get('userId') : $user->getId()
        ))
        {
            throw $this->createNotFoundException();
        }

        $total = $activityService->findActivitiesTotalByUserId(
            $userTarget->getId(),
            $user->getEvents()
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'default/activity/list.rss.twig',
            [
                'session'    => [
                    'user' => $user
                ],
                'activities' => $activityService->findLastActivitiesByUserId(
                    $userTarget->getId(),
                    $userTarget->getEvents()
                )
            ],
            $response
        );
    }

    #[Route(
        '/{_locale}/rss/activity/torrent/{torrentId}',
        name: 'rss_activity_torrent',
        requirements: [
            '_locale'    => '%app.locales%',
            'torrentId'  => '\d+'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function rssTorrent(
        Request $request,
        UserService $userService,
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Get total activities
        $total = $activityService->findActivitiesTotalByTorrentId(
            $torrent->getId(),
            $user->getEvents()
        );

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'default/activity/list.rss.twig',
            [
                'session'    => [
                    'user' => $user
                ],
                'activities' => $activityService->findLastActivitiesByTorrentId(
                    $torrent->getId(),
                    $user->getEvents()
                )
            ],
            $response
        );
    }

    public function event(
        \App\Entity\User $user,
        \App\Entity\Activity $activity,
        ActivityService $activityService,
        UserService $userService,
        TorrentService $torrentService,
        ?string $format = null,
    ): Response
    {
        switch ($format)
        {
            case 'rss':

                $extension = '.rss.twig';

            break;

            default:

                $extension = '.html.twig';
        }

        switch ($activity->getEvent())
        {
            // User
            case $activity::EVENT_USER_ADD:

                return $this->render(
                    'default/activity/event/user/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_APPROVE_ADD:

                return $this->render(
                    'default/activity/event/user/approve/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_APPROVE_DELETE:

                return $this->render(
                    'default/activity/event/user/approve/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_MODERATOR_ADD:

                return $this->render(
                    'default/activity/event/user/moderator/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_MODERATOR_DELETE:

                return $this->render(
                    'default/activity/event/user/moderator/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_STATUS_ADD:

                return $this->render(
                    'default/activity/event/user/status/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_STATUS_DELETE:

                return $this->render(
                    'default/activity/event/user/status/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_STAR_ADD:

                return $this->render(
                    'default/activity/event/user/star/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user'  =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_USER_STAR_DELETE:

                return $this->render(
                    'default/activity/event/user/star/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'to' =>
                        [
                            'user' =>
                            [
                                'id'        => $activity->getData()['userId'],
                                'identicon' => $userService->identicon(
                                    $userService->getUser(
                                        $activity->getData()['userId']
                                    )->getAddress()
                                )
                            ]
                        ]
                    ]
                );

            break;

            // Torrent
            case $activity::EVENT_TORRENT_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_APPROVE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/approve/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_APPROVE_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/approve/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            // Torrent Download
            case $activity::EVENT_TORRENT_DOWNLOAD_FILE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/download/file/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_DOWNLOAD_MAGNET_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/download/magnet/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            // Torrent Wanted
            case $activity::EVENT_TORRENT_WANTED_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/wanted/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            // Torrent Status
            case $activity::EVENT_TORRENT_STATUS_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/status/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_STATUS_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/status/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent Locales
            case $activity::EVENT_TORRENT_LOCALES_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/locales/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'locales'    => [
                                'id'     => $activity->getData()['torrentLocalesId'],
                                'exist'  => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_LOCALES_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/locales/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'locales'   => [
                                'id'    => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_LOCALES_APPROVE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/locales/approve/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'locales'   => [
                                'id'    => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_LOCALES_APPROVE_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/locales/approve/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'locales'   => [
                                'id'    => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent Categories
            case $activity::EVENT_TORRENT_CATEGORIES_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/categories/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'categories' => [
                                'id'     => $activity->getData()['torrentCategoriesId'],
                                'exist'  => $torrentService->getTorrentCategories(
                                    $activity->getData()['torrentCategoriesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_CATEGORIES_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/categories/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'categories' => [
                                'id'    => $activity->getData()['torrentCategoriesId'],
                                'exist' => $torrentService->getTorrentCategories(
                                    $activity->getData()['torrentCategoriesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_CATEGORIES_APPROVE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/categories/approve/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'categories' => [
                                'id'    => $activity->getData()['torrentCategoriesId'],
                                'exist' => $torrentService->getTorrentCategories(
                                    $activity->getData()['torrentCategoriesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_CATEGORIES_APPROVE_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/categories/approve/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'categories' => [
                                'id'    => $activity->getData()['torrentCategoriesId'],
                                'exist' => $torrentService->getTorrentCategories(
                                    $activity->getData()['torrentCategoriesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent Sensitive
            case $activity::EVENT_TORRENT_SENSITIVE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/sensitive/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'sensitive' => [
                                'id'    => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_SENSITIVE_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/sensitive/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'sensitive' => [
                                'id'    => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_SENSITIVE_APPROVE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/sensitive/approve/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'sensitive' => [
                                'id'    => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_SENSITIVE_APPROVE_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/sensitive/approve/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'sensitive' => [
                                'id'    => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent Poster
            case $activity::EVENT_TORRENT_POSTER_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/poster/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'poster' => [
                                'id'    => $activity->getData()['torrentPosterId'],
                                'exist' => $torrentService->getTorrentPoster(
                                    $activity->getData()['torrentPosterId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_POSTER_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/poster/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'poster' => [
                                'id'    => $activity->getData()['torrentPosterId'],
                                'exist' => $torrentService->getTorrentPoster(
                                    $activity->getData()['torrentPosterId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_POSTER_APPROVE_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/poster/approve/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'poster' => [
                                'id'    => $activity->getData()['torrentPosterId'],
                                'exist' => $torrentService->getTorrentPoster(
                                    $activity->getData()['torrentPosterId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_POSTER_APPROVE_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/poster/approve/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName(),
                            'poster' => [
                                'id'    => $activity->getData()['torrentPosterId'],
                                'exist' => $torrentService->getTorrentPoster(
                                    $activity->getData()['torrentPosterId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent star
            case $activity::EVENT_TORRENT_STAR_ADD:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/star/add' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user'  =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_STAR_DELETE:

                // Init torrent
                if (!$torrent = $torrentService->getTorrent($activity->getTorrentId()))
                {
                    throw $this->createNotFoundException();
                }

                return $this->render(
                    'default/activity/event/torrent/star/delete' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user'  =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ],
                        'torrent' =>
                        [
                            'id'        => $torrent->getId(),
                            'sensitive' => $torrent->isSensitive(),
                            'approved'  => $torrent->isApproved(),
                            'status'    => $torrent->isStatus(),
                            'name'      => $torrentService->readTorrentFileByTorrentId(
                                $torrent->getId()
                            )->getName()
                        ],
                        'session' =>
                        [
                            'user' =>
                            [
                                'id'        => $user->getId(),
                                'sensitive' => $user->isSensitive(),
                                'moderator' => $user->isModerator(),
                                'owner'     => $user->getId() === $torrent->getUserId(),
                            ]
                        ]
                    ]
                );

            break;

            default:

                return $this->render(
                    'default/activity/event/undefined' . $extension,
                    [
                        'id'    => $activity->getId(),
                        'added' => $activity->getAdded(),
                        'user' =>
                        [
                            'id'        => $activity->getUserId(),
                            'identicon' => $userService->identicon(
                                $userService->getUser(
                                    $activity->getUserId()
                                )->getAddress()
                            )
                        ]
                    ]
                );
        }
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
                $this->getParameter('app.posters'),
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