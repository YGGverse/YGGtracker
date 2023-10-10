<?php

namespace App\Service;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityService
{
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        EntityManagerInterface $entityManagerInterface
    )
    {
        $this->entityManagerInterface = $entityManagerInterface;
    }

    public function findLastActivities(): array
    {
        return $this->entityManagerInterface
                    ->getRepository(Activity::class)
                    ->findBy(
                        [],
                        [
                            'id' => 'DESC'
                        ]
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
}