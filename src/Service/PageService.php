<?php

namespace App\Service;

use App\Entity\Page;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PageService
{
    private EntityManagerInterface $entityManager;
    private PageRepository $pageRepository;
    private ParameterBagInterface $parameterBagInterface;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBagInterface
    )
    {
        $this->entityManager = $entityManager;
        $this->pageRepository = $entityManager->getRepository(Page::class);
        $this->parameterBagInterface = $parameterBagInterface;
    }

    public function new(): ?Page
    {
        return new Page();
    }

    public function save(Page $page) : void
    {
        $this->entityManager->persist($page);
        $this->entityManager->flush();
    }
}