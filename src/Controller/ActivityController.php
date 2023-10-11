<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ActivityService;
use App\Service\UserService;
use App\Service\ArticleService;
use App\Service\TorrentService;

class ActivityController extends AbstractController
{
    public function template(
        $activity,
        ActivityService $activityService,
        UserService $userService,
        ArticleService $articleService,
        TorrentService $torrentService,
    ): Response
    {
        switch ($activity->getEvent())
        {
            // User
            case $activity::EVENT_USER_ADD:

                return $this->render(
                    'default/activity/event/user/add.html.twig',
                    [
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
                    'default/activity/event/user/approve/add.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/approve/delete.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/moderator/add.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/moderator/delete.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/status/add.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/status/delete.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/star/add.html.twig',
                    [
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
                        'by' =>
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
                    'default/activity/event/user/star/delete.html.twig',
                    [
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
                        'by' =>
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

                return $this->render(
                    'default/activity/event/torrent/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName()
                        ]
                    ]
                );

            break;

            // Torrent Download
            case $activity::EVENT_TORRENT_DOWNLOAD_FILE_ADD:

                return $this->render(
                    'default/activity/event/torrent/download/file/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName()
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_DOWNLOAD_MAGNET_ADD:

                return $this->render(
                    'default/activity/event/torrent/download/magnet/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName()
                        ]
                    ]
                );

            break;

            /// Torrent Locales
            case $activity::EVENT_TORRENT_LOCALES_ADD:

                return $this->render(
                    'default/activity/event/torrent/locales/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'locales' => [
                                'id'     => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_LOCALES_DELETE:

                return $this->render(
                    'default/activity/event/torrent/locales/delete.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'locales' => [
                                'id' => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_LOCALES_APPROVE_ADD:

                return $this->render(
                    'default/activity/event/torrent/locales/approve/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'locales' => [
                                'id' => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_LOCALES_APPROVE_DELETE:

                return $this->render(
                    'default/activity/event/torrent/locales/approve/delete.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'locales' => [
                                'id' => $activity->getData()['torrentLocalesId'],
                                'exist' => $torrentService->getTorrentLocales(
                                    $activity->getData()['torrentLocalesId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent Sensitive
            case $activity::EVENT_TORRENT_SENSITIVE_ADD:

                return $this->render(
                    'default/activity/event/torrent/sensitive/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'sensitive' => [
                                'id'     => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_SENSITIVE_DELETE:

                return $this->render(
                    'default/activity/event/torrent/sensitive/delete.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'sensitive' => [
                                'id' => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_SENSITIVE_APPROVE_ADD:

                return $this->render(
                    'default/activity/event/torrent/sensitive/approve/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'sensitive' => [
                                'id' => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_SENSITIVE_APPROVE_DELETE:

                return $this->render(
                    'default/activity/event/torrent/sensitive/approve/delete.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName(),
                            'sensitive' => [
                                'id' => $activity->getData()['torrentSensitiveId'],
                                'exist' => $torrentService->getTorrentSensitive(
                                    $activity->getData()['torrentSensitiveId'] // could be deleted by moderator, remove links
                                )
                            ]
                        ]
                    ]
                );

            break;

            /// Torrent star
            case $activity::EVENT_TORRENT_STAR_ADD:

                return $this->render(
                    'default/activity/event/torrent/star/add.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName()
                        ]
                    ]
                );

            break;

            case $activity::EVENT_TORRENT_STAR_DELETE:

                return $this->render(
                    'default/activity/event/torrent/star/delete.html.twig',
                    [
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
                            'id'   => $activity->getTorrentId(),
                            'name' => $torrentService->readTorrentFileByTorrentId(
                                $activity->getTorrentId()
                            )->getName()
                        ]
                    ]
                );

            break;

            // @TODO Page

            default:

                return $this->render(
                    'default/activity/event/undefined.html.twig',
                    [
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
}