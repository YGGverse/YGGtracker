<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\TorrentService;

class TorrentController extends AbstractController
{
    #[Route(
        '/{_locale}/torrent/{torrentId}',
        name: 'torrent_info',
        requirements:
        [
            'torrentId' => '\d+'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function info(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService,
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init file
        try
        {
            $file = \Rhilip\Bencode\TorrentFile::load(
                $torrentService->getStoragePathById(
                    $torrent->getId()
                )
            );
        }

        catch (ParseException $e)
        {
            throw $this->createNotFoundException();
        }

        /*
        if (!$torrent = $torrentService->getTorrentLocales($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }
        */

        return $this->render('default/torrent/info.html.twig', [
            'torrent' =>
            [
                'id'      => $torrent->getId(),
                'added'   => 0, // @TODO
                'locales' => $torrentService->findLastTorrentLocales($torrent->getId()),
                'pages'   => []
            ],
            'file' =>
            [
                'name'     => $file->getName(),
                'size'     => $file->getSize(),
                'count'    => $file->getFileCount(),
                'pieces'   => $file->getPieceLength(),
                'created'  => $file->getCreationDate(),
                'software' => $file->getCreatedBy(),
                'protocol' => $file->getProtocol(),
                'private'  => $file->isPrivate(),
                'source'   => $file->getSource(),
                'comment'  => $file->getComment(),
                'tree'     => $file->getFileTree(),
                'trackers' => $file->getAnnounceList(),
                'hash' =>
                [
                    'v1' => $file->getInfoHashV1(false),
                    'v2' => $file->getInfoHashV2(false)
                ],
                'magnet' => $file->getMagnetLink()
            ],
            'trackers' => explode('|', $this->getParameter('app.trackers')),
        ]);
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/edit/locales/{torrentLocalesId}',
        name: 'torrent_edit_locales',
        requirements:
        [
            'torrentId'        => '\d+',
            'torrentLocalesId' => '\d+',
        ],
        defaults:
        [
            'torrentLocalesId' => null,
        ],
        methods:
        [
            'GET',
            'POST'
        ]
    )]

    public function editLocales(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent locales
        $torrentLocalesValue = [];

        // Get from edition version requested
        if ($request->get('torrentLocalesId'))
        {
            if ($torrentLocales = $torrentService->getTorrentLocales($request->get('torrentLocalesId')))
            {
                foreach ($torrentLocales->getValue() as $value)
                {
                    $torrentLocalesValue[] = $value;
                }
            }

            else
            {
                throw $this->createNotFoundException();
            }
        }

        // Otherwise, get latest available
        else
        {
            if ($torrentLocales = $torrentService->findLastTorrentLocales($torrent->getId()))
            {
                foreach ($torrentLocales->getValue() as $value)
                {
                    $torrentLocalesValue[] = $value;
                }

                // Update active locale
                $request->attributes->set('torrentLocalesId', $torrentLocales->getId());
            }

            else
            {
                $torrentLocalesValue[] = $request->get('_locale');
            }
        }

        // Init edition history
        $editions = [];
        foreach ($torrentService->findTorrentLocales($torrent->getId()) as $torrentLocales)
        {
            $editions[] =
            [
                'id'       => $torrentLocales->getId(),
                'added'    => $torrentLocales->getAdded(),
                'approved' => $torrentLocales->isApproved(),
                'active'   => $torrentLocales->getId() == $request->get('torrentLocalesId'),
                'user'     =>
                [
                    'id' => $torrentLocales->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->get(
                            $torrentLocales->getUserId()
                        )->getAddress()
                    ),
                ]
            ];
        }

        // Init form
        $form =
        [
            'locales' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $request->get('locales') ? $request->get('locales') : $torrentLocalesValue,
                    'placeholder' => $translator->trans('Content language')
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            /// Locales
            $locales = [];
            if ($request->get('locales'))
            {
                foreach ((array) $request->get('locales') as $locale)
                {
                    if (in_array($locale, explode('|', $this->getParameter('app.locales'))))
                    {
                        $locales[] = $locale;
                    }
                }
            }

            //// At least one valid locale required
            if (!$locales)
            {
                $form['locales']['error'][] = $translator->trans('At least one locale required');
            }

            // Request is valid
            if (empty($form['locales']['error']))
            {
                // Save data
                $torrentService->addTorrentLocales(
                    $torrent->getId(),
                    $user->getId(),
                    time(),
                    $locales,
                    $user->isApproved()
                );

                // Redirect to info page created
                return $this->redirectToRoute(
                    'torrent_info',
                    [
                        '_locale'   => $request->get('_locale'),
                        'torrentId' => $torrent->getId()
                    ]
                );
            }
        }

        // Render form template
        return $this->render(
            'default/torrent/edit/locales.html.twig',
            [
                'torrentId'        => $torrent->getId(),
                'locales'          => explode('|', $this->getParameter('app.locales')),
                'editions'         => $editions,
                'form'             => $form,
            ]
        );
    }

    #[Route(
        '/{_locale}/submit/torrent',
        name: 'torrent_submit',
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
        TorrentService $torrentService
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
            'locales' =>
            [
                'error'       => [],
                'attribute' =>
                [
                    'value'       => $request->get('locales') ? $request->get('locales') : [$request->get('_locale')],
                    'placeholder' => $translator->trans('Content language')
                ]
            ],
            'torrent' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => null, // is local file, there is no values passed
                    'placeholder' => $translator->trans('Select torrent file')
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
            /// Locales
            $locales = [];
            if ($request->get('locales'))
            {
                foreach ((array) $request->get('locales') as $locale)
                {
                    if (in_array($locale, explode('|', $this->getParameter('app.locales'))))
                    {
                        $locales[] = $locale;
                    }
                }
            }

            //// At least one valid locale required
            if (!$locales)
            {
                $form['locales']['error'][] = $translator->trans('At least one locale required');
            }

            /// Torrent
            if ($file = $request->files->get('torrent'))
            {
                //// Validate torrent file
                if (filesize($file->getPathName()) > $this->getParameter('app.torrent.size.max'))
                {
                    $form['torrent']['error'][] = $translator->trans('Torrent file out of size limit');
                }

                if (empty($torrentService->getTorrentInfoNameByFilepath($file->getPathName())))
                {
                    $form['torrent']['error'][] = $translator->trans('Could not parse torrent file');
                }
            }

            else
            {
                $form['torrent']['error'][] = $translator->trans('Torrent file required');
            }

            // Request is valid
            if (empty($form['torrent']['error']) && empty($form['locales']['error']))
            {
                // Save data
                $torrent = $torrentService->add(
                    $file->getPathName(),
                    $user->getId(),
                    time(),
                    (array) $locales,
                    (bool) $request->get('sensitive'),
                    $user->isApproved()
                );

                // Redirect to info page created
                return $this->redirectToRoute(
                    'torrent_info',
                    [
                        '_locale'   => $request->get('_locale'),
                        'torrentId'  => $torrent->getId()
                    ]
                );
            }
        }

        // Render form template
        return $this->render(
            'default/torrent/submit.html.twig',
            [
                'locales' => explode('|', $this->getParameter('app.locales')),
                'form'    => $form,
            ]
        );
    }
}
