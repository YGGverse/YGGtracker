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
}