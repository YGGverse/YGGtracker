<?php

namespace App\Service;

use App\Entity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ActivityService
{
    private EntityManagerInterface $entityManager;
    private ActivityRepository $activityRepository;
    private ParameterBagInterface $parameterBagInterface;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBagInterface
    )
    {
        $this->entityManager = $entityManager;
        $this->activityRepository = $entityManager->getRepository(Activity::class);
        $this->parameterBagInterface = $parameterBagInterface;
    }

    public function addEvent(int $userId, string $event, array $data): ?Activity
    {
        $activity = new Activity();

        $activity->setEvent($event);
        $activity->setUserId($userId);
        $activity->setApproved($approved);
        $activity->setAdded(time());

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        return $activity;
    }

    public function findLast(bool $moderator): ?array
    {
        if ($moderator)
        {
            return $this->activityRepository->findLast();
        }

        else
        {
            return $this->activityRepository->findLastByApprovedField(true);
        }
    }
}