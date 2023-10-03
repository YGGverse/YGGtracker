<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
{
    #[Route(
        '/{_locale}/page/submit',
        name: 'page_submit'
    )]
    public function submit(): Response
    {
        /*
        return $this->redirectToRoute('page', [
            'id' => $page->getId()
        ]);
        */
        return $this->render('default/page/submit.html.twig', [
            // @TODO
        ]);
    }

    #[Route(
        '/{_locale}/page/stars',
        name: 'page_stars'
    )]
    public function stars(): Response
    {
        // @TODO
    }

    #[Route(
        '/{_locale}/page/views',
        name: 'page_views'
    )]
    public function views(): Response
    {
        // @TODO
    }

    #[Route(
        '/{_locale}/page/downloads',
        name: 'page_downloads'
    )]
    public function downloads(): Response
    {
        // @TODO
    }

    #[Route(
        '/{_locale}/page/comments',
        name: 'page_comments'
    )]
    public function comments(): Response
    {
        // @TODO
    }

    #[Route(
        '/{_locale}/page/editions',
        name: 'page_editions'
    )]
    public function editions(): Response
    {
        // @TODO
    }
}