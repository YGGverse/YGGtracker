<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\ActivityService;

class SearchController extends AbstractController
{
    public function module(
        ?string $query,
        ?string $type
    ): Response
    {
        return $this->render(
            'default/search/module.html.twig',
            [
                'query' => urldecode($query),
            ]
        );
    }
}