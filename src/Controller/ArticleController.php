<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\ArticleService;
use App\Service\TorrentService;

class ArticleController extends AbstractController
{
    #[Route(
        '/{_locale}/article/{id}',
        name: 'article_info',
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
        UserService $userService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        return $this->render('default/article/info.html.twig', [
            'title' => 'test'
        ]);
    }

    #[Route(
        '/{_locale}/submit/article',
        name: 'article_submit',
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
        ArticleService $articleService,
        ArticleService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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
                    'minlength'   => $this->getParameter('app.article.title.length.min'),
                    'maxlength'   => $this->getParameter('app.article.title.length.max'),
                    'placeholder' => sprintf(
                        $translator->trans('Article title (%s-%s chars)'),
                        number_format($this->getParameter('app.article.title.length.min')),
                        number_format($this->getParameter('app.article.title.length.max'))
                    ),
                ]
            ],
            'description' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('description'),
                    'minlength'   => $this->getParameter('app.article.description.length.min'),
                    'maxlength'   => $this->getParameter('app.article.description.length.max'),
                    'placeholder' => sprintf(
                        $translator->trans('Article description (%s-%s chars)'),
                        number_format($this->getParameter('app.article.description.length.min')),
                        number_format($this->getParameter('app.article.description.length.max'))
                    ),
                ]
            ],
            'torrents' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'placeholder' => $translator->trans('Select torrent file')
                ]
            ],
            'sensitive' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('sensitive'),
                    'placeholder' => $translator->trans('Apply sensitive filters to publication'),
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
            if (mb_strlen($request->get('title')) < $this->getParameter('app.article.title.length.min') ||
                mb_strlen($request->get('title')) > $this->getParameter('app.article.title.length.max'))
            {
                $form['title']['error'][] = sprintf(
                    $translator->trans('Article title out of %s-%s chars'),
                    number_format($this->getParameter('app.article.title.length.min')),
                    number_format($this->getParameter('app.article.title.length.max'))
                );
            }

            /// Description
            if (mb_strlen($request->get('description')) < $this->getParameter('app.article.description.length.min') ||
                mb_strlen($request->get('description')) > $this->getParameter('app.article.description.length.max'))
            {
                $form['description']['error'][] = sprintf(
                    $translator->trans('Article description out of %s-%s chars'),
                    number_format($this->getParameter('app.article.description.length.min')),
                    number_format($this->getParameter('app.article.description.length.max'))
                );
            }

            /// Torrents
            $torrents = [];

            if ($files = $request->files->get('torrents'))
            {
                foreach ($files as $file)
                {
                    /// Torrent
                    if ($file = $request->files->get('torrent'))
                    {
                        //// Validate torrent file
                        if (filesize($file->getPathName()) > $this->getParameter('app.torrent.size.max'))
                        {
                            $form['torrents']['error'][] = $translator->trans('Torrent file out of size limit');

                            continue;
                        }

                        //// Validate torrent format
                        if (!$torrentService->readTorrentFileByFilepath($file->getPathName()))
                        {
                            $form['torrents']['error'][] = $translator->trans('Could not parse torrent file');

                            continue;
                        }
                    }

                    //// Content
                    $torrent = $torrentService->add(
                        $file->getPathName(),
                        $user->getId(),
                        time(),
                        [$request->get('locale')],
                        (bool) $request->get('sensitive'),
                        $user->isApproved()
                    );

                    $torrents[] = $torrent->getId();
                }
            }

            if (empty($form['locale']['error']) &&
                empty($form['title']['error']) &&
                empty($form['description']['error']) &&
                empty($form['torrents']['error'])
            )
            {
                $article = $articleService->submit(
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
                    'article_info',
                    [
                        '_locale' => $request->get('_locale'),
                        'id'      => $article->getId()
                    ]
                );
            }
        }

        return $this->render(
            'default/article/submit.html.twig',
            [
                'locales' => explode('|', $this->getParameter('app.locales')),
                'form'    => $form,
            ]
        );
    }

    private function initUser(
        Request $request,
        UserService $userService,
        ActivityService $activityService
    ): ?\App\Entity\User
    {
        // Init user
        if (!$user = $userService->findUserByAddress($request->getClientIp()))
        {
            $user = $userService->addUser(
                $request->getClientIp(),
                time(),
                $this->getParameter('app.locale'),
                explode('|', $this->getParameter('app.locales')),
                $activityService->getEventCodes(),
                $this->getParameter('app.theme'),
                $this->getParameter('app.sensitive'),
                $this->getParameter('app.yggdrasil'),
                $this->getParameter('app.approved')
            );

            // Add user join event
            $activityService->addEventUserAdd(
                $user->getId(),
                time()
            );
        }

        return $user;
    }
}