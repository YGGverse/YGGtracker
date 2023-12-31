<?php

namespace App\Service;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ActivityService
{
    private EntityManagerInterface $entityManagerInterface;
    private TranslatorInterface $translatorInterface;

    public function __construct(
        EntityManagerInterface $entityManagerInterface,
        TranslatorInterface $translatorInterface
    )
    {
        $this->entityManagerInterface = $entityManagerInterface;
        $this->translatorInterface    = $translatorInterface;
    }

    public function getEventCodes(): array
    {
        return
        [
            // User
            Activity::EVENT_USER_ADD,

            Activity::EVENT_USER_APPROVE_ADD,
            Activity::EVENT_USER_APPROVE_DELETE,

            Activity::EVENT_USER_MODERATOR_ADD,
            Activity::EVENT_USER_MODERATOR_DELETE,

            Activity::EVENT_USER_STATUS_ADD,
            Activity::EVENT_USER_STATUS_DELETE,

            Activity::EVENT_USER_STAR_ADD,
            Activity::EVENT_USER_STAR_DELETE,

            // Torrents
            Activity::EVENT_TORRENT_ADD,

            Activity::EVENT_TORRENT_APPROVE_ADD,
            Activity::EVENT_TORRENT_APPROVE_DELETE,

            Activity::EVENT_TORRENT_LOCALES_ADD,
            Activity::EVENT_TORRENT_LOCALES_DELETE,
            Activity::EVENT_TORRENT_LOCALES_APPROVE_ADD,
            Activity::EVENT_TORRENT_LOCALES_APPROVE_DELETE,

            Activity::EVENT_TORRENT_CATEGORIES_ADD,
            Activity::EVENT_TORRENT_CATEGORIES_DELETE,
            Activity::EVENT_TORRENT_CATEGORIES_APPROVE_ADD,
            Activity::EVENT_TORRENT_CATEGORIES_APPROVE_DELETE,

            Activity::EVENT_TORRENT_SENSITIVE_ADD,
            Activity::EVENT_TORRENT_SENSITIVE_DELETE,
            Activity::EVENT_TORRENT_SENSITIVE_APPROVE_ADD,
            Activity::EVENT_TORRENT_SENSITIVE_APPROVE_DELETE,

            Activity::EVENT_TORRENT_POSTER_ADD,
            Activity::EVENT_TORRENT_POSTER_DELETE,
            Activity::EVENT_TORRENT_POSTER_APPROVE_ADD,
            Activity::EVENT_TORRENT_POSTER_APPROVE_DELETE,

            Activity::EVENT_TORRENT_STAR_ADD,
            Activity::EVENT_TORRENT_STAR_DELETE,

            Activity::EVENT_TORRENT_DOWNLOAD_FILE_ADD,
            Activity::EVENT_TORRENT_DOWNLOAD_MAGNET_ADD,

            Activity::EVENT_TORRENT_STATUS_ADD,
            Activity::EVENT_TORRENT_STATUS_DELETE,

            Activity::EVENT_TORRENT_WANTED_ADD,
        ];
    }

    public function getEventsTree(): array
    {
        $events = [];

        foreach ($this->getEventCodes() as $code)
        {
            switch ($code)
            {
                // User
                case Activity::EVENT_USER_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Users')
                    ]
                    [
                        $this->translatorInterface->trans('Joined')
                    ] = $code;

                break;

                /// User approve
                case Activity::EVENT_USER_APPROVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Users')
                    ]
                    [
                        $this->translatorInterface->trans('Approved')
                    ] = $code;

                break;

                case Activity::EVENT_USER_APPROVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Users')
                    ]
                    [
                        $this->translatorInterface->trans('Disapproved')
                    ] = $code;
                break;

                /// User status
                case Activity::EVENT_USER_STATUS_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('User statuses')
                    ]
                    [
                        $this->translatorInterface->trans('Enabled')
                    ] = $code;

                break;

                case Activity::EVENT_USER_STATUS_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('User statuses')
                    ]
                    [
                        $this->translatorInterface->trans('Disabled')
                    ] = $code;
                break;

                /// User moderator
                case Activity::EVENT_USER_MODERATOR_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('User moderators')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_USER_MODERATOR_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('User moderators')
                    ]
                    [
                        $this->translatorInterface->trans('Removed')
                    ] = $code;
                break;

                /// User star
                case Activity::EVENT_USER_STAR_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('User stars')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_USER_STAR_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('User stars')
                    ]
                    [
                        $this->translatorInterface->trans('Removed')
                    ] = $code;
                break;

                // Torrent
                case Activity::EVENT_TORRENT_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrents')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_APPROVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrents')
                    ]
                    [
                        $this->translatorInterface->trans('Approved')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_APPROVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrents')
                    ]
                    [
                        $this->translatorInterface->trans('Disapproved')
                    ] = $code;

                break;

                /// Torrent locales
                case Activity::EVENT_TORRENT_LOCALES_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent locales')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_LOCALES_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent locales')
                    ]
                    [
                        $this->translatorInterface->trans('Deleted')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_LOCALES_APPROVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent locales')
                    ]
                    [
                        $this->translatorInterface->trans('Approved')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_LOCALES_APPROVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent locales')
                    ]
                    [
                        $this->translatorInterface->trans('Disapproved')
                    ] = $code;

                break;

                /// Torrent categories
                case Activity::EVENT_TORRENT_CATEGORIES_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent categories')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_CATEGORIES_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent categories')
                    ]
                    [
                        $this->translatorInterface->trans('Deleted')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_CATEGORIES_APPROVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent categories')
                    ]
                    [
                        $this->translatorInterface->trans('Approved')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_CATEGORIES_APPROVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent categories')
                    ]
                    [
                        $this->translatorInterface->trans('Disapproved')
                    ] = $code;

                break;

                /// Torrent sensitive
                case Activity::EVENT_TORRENT_SENSITIVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent sensitive')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_SENSITIVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent sensitive')
                    ]
                    [
                        $this->translatorInterface->trans('Deleted')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_SENSITIVE_APPROVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent sensitive')
                    ]
                    [
                        $this->translatorInterface->trans('Approved')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_SENSITIVE_APPROVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent sensitive')
                    ]
                    [
                        $this->translatorInterface->trans('Disapproved')
                    ] = $code;

                break;

                /// Torrent poster
                case Activity::EVENT_TORRENT_POSTER_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent posters')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_POSTER_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent posters')
                    ]
                    [
                        $this->translatorInterface->trans('Deleted')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_POSTER_APPROVE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent posters')
                    ]
                    [
                        $this->translatorInterface->trans('Approved')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_POSTER_APPROVE_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent posters')
                    ]
                    [
                        $this->translatorInterface->trans('Disapproved')
                    ] = $code;

                break;

                /// Torrent stars
                case Activity::EVENT_TORRENT_STAR_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent stars')
                    ]
                    [
                        $this->translatorInterface->trans('Added')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_STAR_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent stars')
                    ]
                    [
                        $this->translatorInterface->trans('Removed')
                    ] = $code;

                break;

                /// Torrent downloads
                case Activity::EVENT_TORRENT_DOWNLOAD_FILE_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent downloads')
                    ]
                    [
                        $this->translatorInterface->trans('Files')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_DOWNLOAD_MAGNET_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrent downloads')
                    ]
                    [
                        $this->translatorInterface->trans('Magnet links')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_WANTED_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrents')
                    ]
                    [
                        $this->translatorInterface->trans('Wanted')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_STATUS_ADD:

                    $events
                    [
                        $this->translatorInterface->trans('Torrents')
                    ]
                    [
                        $this->translatorInterface->trans('Enabled')
                    ] = $code;

                break;

                case Activity::EVENT_TORRENT_STATUS_DELETE:

                    $events
                    [
                        $this->translatorInterface->trans('Torrents')
                    ]
                    [
                        $this->translatorInterface->trans('Disabled')
                    ] = $code;

                break;
            }
        }

        return $events;
    }

    public function findLastActivities(
        array $whitelist,
        int   $limit  = 10,
        int   $offset = 0
    ): array
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findBy(
                        [
                            'event' => $whitelist
                        ],
                        [
                            'id' => 'DESC'
                        ],
                        $limit,
                        $offset
                    );
    }

    public function findLastActivitiesByUserId(
        int   $userId,
        array $whitelist,
        int   $limit  = 10,
        int   $offset = 0
    ): array
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findBy(
                        [
                            'userId' => $userId,
                            'event'  => $whitelist,
                        ],
                        [
                            'id' => 'DESC'
                        ],
                        $limit,
                        $offset
                    );
    }

    public function findLastActivitiesByTorrentId(
        int   $torrentId,
        array $whitelist,
        int   $limit  = 10,
        int   $offset = 0
    ): array
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findBy(
                        [
                            'torrentId' => $torrentId,
                            'event'     => $whitelist,
                        ],
                        [
                            'id' => 'DESC'
                        ],
                        $limit,
                        $offset
                    );
    }

    public function findActivitiesTotal(
        array $whitelist
    ): int
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findActivitiesTotal($whitelist);
    }

    public function findActivitiesTotalByUserId(
        int $userId,
        array $whitelist
    ): int
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findActivitiesTotalByUserId(
                        $userId,
                        $whitelist
                    );
    }

    public function findActivitiesTotalByTorrentId(
        int $torrentId,
        array $whitelist
    ): int
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findActivitiesTotalByTorrentId(
                        $torrentId,
                        $whitelist
                    );
    }

    // User
    public function addEventUserAdd(
        int $userId,
        int $added
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// User approved
    public function addEventUserApproveAdd(
        int $userId,
        int $added,
        int $userIdTarget,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_APPROVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventUserApproveDelete(
        int $userId,
        int $added,
        int $userIdTarget,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_APPROVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// User status
    public function addEventUserStatusAdd(
        int $userId,
        int $added,
        int $userIdTarget,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_STATUS_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventUserStatusDelete(
        int $userId,
        int $added,
        int $userIdTarget,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_STATUS_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// User moderator
    public function addEventUserModeratorAdd(
        int $userId,
        int $added,
        int $userIdTarget,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_MODERATOR_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventUserModeratorDelete(
        int $userId,
        int $added,
        int $userIdTarget,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_MODERATOR_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// User star
    public function addEventUserStarAdd(
        int $userId,
        int $added,
        int $userIdTarget
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_STAR_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventUserStarDelete(
        int $userId,
        int $added,
        int $userIdTarget
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_USER_STAR_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'userId' => $userIdTarget
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    // Torrent
    public function addEventTorrentAdd(
        int $userId,
        int $added,
        int $torrentId
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setTorrentId(
            $torrentId
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentApproveAdd(
        int $userId,
        int $torrentId,
        int $added
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_APPROVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentApproveDelete(
        int $userId,
        int $torrentId,
        int $added
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_APPROVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentStatusAdd(
        int $userId,
        int $torrentId,
        int $added
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_STATUS_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentStatusDelete(
        int $userId,
        int $torrentId,
        int $added
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_STATUS_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentWantedAdd(
        int $userId,
        int $added,
        int $torrentId
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_WANTED_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setTorrentId(
            $torrentId
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// Torrent Download
    public function addEventTorrentDownloadFileAdd(
        int $userId,
        int $added,
        int $torrentId
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_DOWNLOAD_FILE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setTorrentId(
            $torrentId
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentDownloadMagnetAdd(
        int $userId,
        int $added,
        int $torrentId
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_DOWNLOAD_MAGNET_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setAdded(
            $added
        );

        $activity->setTorrentId(
            $torrentId
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// Torrent star
    public function addEventTorrentStarAdd(
        int $userId,
        int $added,
        int $torrentId
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_STAR_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentStarDelete(
        int $userId,
        int $added,
        int $torrentId
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_STAR_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// Torrent locales
    public function addEventTorrentLocalesAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentLocalesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_LOCALES_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentLocalesId' => $torrentLocalesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentLocalesDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentLocalesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_LOCALES_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentLocalesId' => $torrentLocalesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentLocalesApproveAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentLocalesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_LOCALES_APPROVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentLocalesId' => $torrentLocalesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentLocalesApproveDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentLocalesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_LOCALES_APPROVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentLocalesId' => $torrentLocalesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// Torrent categories
    public function addEventTorrentCategoriesAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentCategoriesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_CATEGORIES_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentCategoriesId' => $torrentCategoriesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentCategoriesDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentCategoriesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_CATEGORIES_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentCategoriesId' => $torrentCategoriesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentCategoriesApproveAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentCategoriesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_CATEGORIES_APPROVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentCategoriesId' => $torrentCategoriesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentCategoriesApproveDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentCategoriesId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_CATEGORIES_APPROVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentCategoriesId' => $torrentCategoriesId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// Torrent sensitive
    public function addEventTorrentSensitiveAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentSensitiveId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_SENSITIVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentSensitiveId' => $torrentSensitiveId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentSensitiveDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentSensitiveId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_SENSITIVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentSensitiveId' => $torrentSensitiveId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentSensitiveApproveAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentSensitiveId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_SENSITIVE_APPROVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentSensitiveId' => $torrentSensitiveId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentSensitiveApproveDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentSensitiveId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_SENSITIVE_APPROVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentSensitiveId' => $torrentSensitiveId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    /// Torrent poster
    public function addEventTorrentPosterAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentPosterId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_POSTER_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentPosterId' => $torrentPosterId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentPosterDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentPosterId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_POSTER_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentPosterId' => $torrentPosterId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentPosterApproveAdd(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentPosterId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_POSTER_APPROVE_ADD
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentPosterId' => $torrentPosterId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }

    public function addEventTorrentPosterApproveDelete(
        int $userId,
        int $torrentId,
        int $added,
        int $torrentPosterId,
    ): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent(
            Activity::EVENT_TORRENT_POSTER_APPROVE_DELETE
        );

        $activity->setUserId(
            $userId
        );

        $activity->setTorrentId(
            $torrentId
        );

        $activity->setAdded(
            $added
        );

        $activity->setData(
            [
                'torrentPosterId' => $torrentPosterId
            ]
        );

        $this->entityManagerInterface->persist($activity);
        $this->entityManagerInterface->flush();

        return $activity;
    }
}