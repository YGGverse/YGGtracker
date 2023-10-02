<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends AbstractController
{
    #[Route(
        '/{_locale}/search',
        name: 'search_index'
    )]
    public function index(Request $request): Response
    {
        $query = $request->query->get('query');

        return $this->render('default/search/index.html.twig', [
            'query' => $query
        ]);
    }

    public function module(string $query = ''): Response
    {
        return $this->render('default/search/module.html.twig', [
            'query' => $query,
        ]);
    }
}