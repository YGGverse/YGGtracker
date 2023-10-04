<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\PageService;
use App\Service\TimeService;

class PageController extends AbstractController
{
    #[Route(
        '/{_locale}/page/submit',
        name: 'page_submit'
    )]
    public function submit(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        PageService $pageService,
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        if (!$user->isStatus())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Init form
        $form =
        [
            'locale' =>
            [
                'error'       => [],
                'value'       => $request->get('_locale'),
                'placeholder' => $translator->trans('Content language'),
            ],
            'title' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('title'),
                    'minlength'   => $this->getParameter('app.page.title.length.min'),
                    'maxlength'   => $this->getParameter('app.page.title.length.max'),
                    'placeholder' => sprintf(
                        $translator->trans('Page title text (%s-%s chars)'),
                        number_format($this->getParameter('app.page.title.length.min')),
                        number_format($this->getParameter('app.page.title.length.max'))
                    ),
                ]
            ],
            'description' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('description'),
                    'minlength'   => $this->getParameter('app.page.description.length.min'),
                    'maxlength'   => $this->getParameter('app.page.description.length.max'),
                    'placeholder' => sprintf(
                        $translator->trans('Page description text (%s-%s chars)'),
                        number_format($this->getParameter('app.page.description.length.min')),
                        number_format($this->getParameter('app.page.description.length.max'))
                    ),
                ]
            ],
            'torrents' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'placeholder' => $translator->trans('Select torrent files'),
                ]
            ],
            'sensitive' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('sensitive'),
                    'placeholder' => $translator->trans('Apply sensitive filters for this publication'),
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            // Init new
            $page = $pageService->new();

            /// Locale
            if (!in_array($request->get('locale'), explode('|', $this->getParameter('app.locales'))))
            {
                $form['locale']['error'][] = $translator->trans('Requested locale not supported');
            }

            else
            {
                // $request->get('locale')
            }

            /// Title
            if (mb_strlen($request->get('title')) < $this->getParameter('app.page.title.length.min') ||
                mb_strlen($request->get('title')) > $this->getParameter('app.page.title.length.max'))
            {
                $form['title']['error'][] = sprintf(
                    $translator->trans('Page title out of %s-%s chars'),
                    number_format($this->getParameter('app.page.title.length.min')),
                    number_format($this->getParameter('app.page.title.length.max'))
                );
            }

            else
            {
                // $request->get('title')
            }

            /// Description
            if (mb_strlen($request->get('description')) < $this->getParameter('app.page.description.length.min') ||
                mb_strlen($request->get('description')) > $this->getParameter('app.page.description.length.max'))
            {
                $form['description']['error'][] = sprintf(
                    $translator->trans('Page description out of %s-%s chars'),
                    number_format($this->getParameter('app.page.description.length.min')),
                    number_format($this->getParameter('app.page.description.length.max'))
                );
            }

            else
            {
                // $request->get('description')
            }

            /// Torrents
            $total = 0;

            if ($files = $request->files->get('torrents'))
            {
                foreach ($files as $file)
                {
                    //// Quantity
                    $total++;

                    //// File size
                    if (filesize($file->getPathName()) > $this->getParameter('app.page.torrent.size.max'))
                    {
                        $form['torrents']['error'][] = $translator->trans('Torrent file out of size limit');
                    }

                    //// Content
                    $decoder = new \BitTorrent\Decoder();
                    $decodedFile = $decoder->decodeFile(
                        $file->getPathName()
                    );

                    // var_dump($decodedFile['info']['name']);
                }
            }

            if ($total < $this->getParameter('app.page.torrent.quantity.min') ||
                $total > $this->getParameter('app.page.torrent.quantity.max'))
            {
                $form['torrents']['error'][] = sprintf(
                    $translator->trans('Torrents quantity out of %s-%s range'),
                    number_format($this->getParameter('app.page.torrent.quantity.min')),
                    number_format($this->getParameter('app.page.torrent.quantity.max'))
                );
            }


            if (empty($error))
            {
                // isset($request->get('sensitive'))
                // $pageService->save($page);
            }
        }

        return $this->render('default/page/submit.html.twig', [
            'locales' => explode('|', $this->getParameter('app.locales')),
            'form'    => $form,
        ]);
    }
}