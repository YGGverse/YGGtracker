<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\PageService;
use App\Service\TorrentService;
use App\Service\TimeService;

class PageController extends AbstractController
{
    #[Route(
        '/{_locale}/page/{id}',
        name: 'page_info',
        requirements:
        [
            'id' => '\d+'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function info(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        return $this->render('default/page/info.html.twig', [
            'title' => 'test'
        ]);
    }

    #[Route(
        '/{_locale}/submit/page',
        name: 'page_submit',
        methods:
        [
            'GET',
            'POST'
        ]
    )]
    public function submit(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        PageService $pageService,
        PageService $torrentService
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
                'attribute' =>
                [
                    'value'       => $request->get('_locale'),
                    'placeholder' => $translator->trans('Content language')
                ]
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
                        $translator->trans('Page title (%s-%s chars)'),
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
                        $translator->trans('Page description (%s-%s chars)'),
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
                    'placeholder' => sprintf(
                        $translator->trans('Append %s-%s torrent files'),
                        $this->getParameter('app.page.torrent.file.quantity.min'),
                        $this->getParameter('app.page.torrent.file.quantity.max')
                    )
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
            /// Locale
            if (!in_array($request->get('locale'), explode('|', $this->getParameter('app.locales'))))
            {
                $form['locale']['error'][] = $translator->trans('Requested locale not supported');
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

            /// Torrents
            $total = 0;
            $torrents = [];

            if ($files = $request->files->get('torrents'))
            {
                foreach ($files as $file)
                {
                    //// Quantity
                    $total++;

                    //// File size
                    if (filesize($file->getPathName()) > $this->getParameter('app.torrent.size.max'))
                    {
                        $form['torrents']['error'][] = $translator->trans('Torrent file out of size limit');

                        continue;
                    }

                    if (empty($torrentService->getTorrentFilenameByFilepath($file->getPathName())))
                    {
                        $form['torrent']['error'][] = $translator->trans('Could not parse torrent file');

                        continue;
                    }

                    //// Content
                    $torrent = $torrentService->submit(
                        $file->getPathName(),
                        $user->getId(),
                        time(),
                        (array) $locales,
                        (bool) $request->get('sensitive'),
                        $user->isApproved()
                    );

                    $torrents[] = $torrent->getId();
                }
            }

            if ($total < $this->getParameter('app.page.torrent.file.quantity.min') ||
                $total > $this->getParameter('app.page.torrent.file.quantity.max'))
            {
                $form['torrents']['error'][] = sprintf(
                    $translator->trans('Torrents quantity out of %s-%s range'),
                    number_format($this->getParameter('app.page.torrent.file.quantity.min')),
                    number_format($this->getParameter('app.page.torrent.file.quantity.max'))
                );
            }


            if (empty($form['locale']['error']) &&
                empty($form['title']['error']) &&
                empty($form['description']['error']) &&
                empty($form['torrents']['error'])
            )
            {
                $page = $pageService->submit(
                    $user->getId(),
                    time(),
                    (string) $request->get('locale'),
                    (string) $request->get('title'),
                    (string) $request->get('description'),
                    (array)  $torrents,
                    (bool) $request->get('sensitive'),
                    $user->isApproved()
                );

                // Redirect
                return $this->redirectToRoute(
                    'page_info',
                    [
                        '_locale' => $request->get('_locale'),
                        'id'      => $page->getId()
                    ]
                );
            }
        }

        return $this->render(
            'default/page/submit.html.twig',
            [
                'locales' => explode('|', $this->getParameter('app.locales')),
                'form'    => $form,
            ]
        );
    }
}