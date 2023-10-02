<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{
    #[Route(
        '/{_locale}/',
        name: 'home_index'
    )]
    public function index(Request $request): Response
    {
        return $this->render('default/home/index.html.twig');
    }
}