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
    // Torrent
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

        // Read file
        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            throw $this->createNotFoundException();
        }

        // Render template
        return $this->render('default/torrent/info.html.twig', [
            'torrent' =>
            [
                'id'        => $torrent->getId(),
                'added'     => $torrent->getAdded(),
                'locales'   => $torrentService->findLastTorrentLocales($torrent->getId()),
                'sensitive' => $torrentService->findLastTorrentSensitive($torrent->getId())->isValue(),
                'pages'     => []
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
                // @TODO use download action to filter announcement URL
                // 'magnet' => $file->getMagnetLink()
            ],
            'trackers' => explode('|', $this->getParameter('app.trackers')),
        ]);
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
                    'placeholder' => $translator->trans('Apply sensitive filters to publication'),
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

                //// Validate torrent format
                if (!$torrentService->readTorrentFileByFilepath($file->getPathName()))
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

    // Torrent locales
    #[Route(
        '/{_locale}/torrent/{torrentId}/edit/locales/{torrentLocalesId}',
        name: 'torrent_locales_edit',
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
                'torrentId' => $torrent->getId(),
                'locales'   => explode('|', $this->getParameter('app.locales')),
                'editions'  => $editions,
                'form'      => $form,
                'session' =>
                [
                    'moderator' => $user->isModerator(),
                    'owner'     => $user->getId() === $torrentLocales->getUserId(),
                ]
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/approve/locales/{torrentLocalesId}',
        name: 'torrent_locales_approve',
        requirements:
        [
            'torrentId'        => '\d+',
            'torrentLocalesId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function approveLocales(
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent locales
        if (!$torrentLocales = $torrentService->getTorrentLocales($request->get('torrentLocalesId')))
        {
            throw $this->createNotFoundException();
        }

        // Check permissions
        if (!$user->isModerator())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Update approved
        $torrentService->toggleTorrentLocalesApproved(
            $torrentLocales->getId()
        );

        // Redirect to info page created
        return $this->redirectToRoute(
            'torrent_locales_edit',
            [
                '_locale'          => $request->get('_locale'),
                'torrentId'        => $torrent->getId(),
                'torrentLocalesId' => $torrentLocales->getId(),
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/delete/locales/{torrentLocalesId}',
        name: 'torrent_locales_delete',
        requirements:
        [
            'torrentId'        => '\d+',
            'torrentLocalesId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function deleteLocales(
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent locales
        if (!$torrentLocales = $torrentService->getTorrentLocales($request->get('torrentLocalesId')))
        {
            throw $this->createNotFoundException();
        }

        // Check permissions
        if (!($user->isModerator() || $user->getId() === $torrentLocales->getUserId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Update approved
        $torrentService->deleteTorrentLocales(
            $torrentLocales->getId()
        );

        // Redirect to info page created
        return $this->redirectToRoute(
            'torrent_locales_edit',
            [
                '_locale'          => $request->get('_locale'),
                'torrentId'        => $torrent->getId(),
                'torrentLocalesId' => $torrentLocales->getId(),
            ]
        );
    }

    // Torrent sensitive
    #[Route(
        '/{_locale}/torrent/{torrentId}/edit/sensitive/{torrentSensitiveId}',
        name: 'torrent_sensitive_edit',
        requirements:
        [
            'torrentId'        => '\d+',
            'torrentSensitiveId' => '\d+',
        ],
        defaults:
        [
            'torrentSensitiveId' => null,
        ],
        methods:
        [
            'GET',
            'POST'
        ]
    )]
    public function editSensitive(
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

        // Init sensitive value
        if ($request->get('torrentSensitiveId'))
        {
            if ($torrentSensitive = $torrentService->getTorrentSensitive($request->get('torrentSensitiveId')))
            {
                $sensitive =
                [
                    'id'    => $torrentSensitive->getId(),
                    'value' => $torrentSensitive->isValue(),
                ];
            }

            else
            {
                throw $this->createNotFoundException();
            }
        }
        else
        {
            if ($torrentSensitive = $torrentService->findLastTorrentSensitive($request->get('torrentId')))
            {
                $sensitive =
                [
                    'id'    => $torrentSensitive->getId(),
                    'value' => $torrentSensitive->isValue(),
                ];            }

            else
            {
                $sensitive =
                [
                    'id'    => null,
                    'value' => false,
                ];
            }
        }

        // Init edition history
        $editions = [];
        foreach ($torrentService->findTorrentSensitive($torrent->getId()) as $torrentSensitive)
        {
            $editions[] =
            [
                'id'       => $torrentSensitive->getId(),
                'added'    => $torrentSensitive->getAdded(),
                'approved' => $torrentSensitive->isApproved(),
                'active'   => $torrentSensitive->getId() == $sensitive['id'],
                'user'     =>
                [
                    'id' => $torrentSensitive->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->get(
                            $torrentSensitive->getUserId()
                        )->getAddress()
                    ),
                ]
            ];
        }

        // Init form
        $form =
        [
            'sensitive' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value'       => $sensitive['value'],
                    'placeholder' => $translator->trans('Apply sensitive filters to publication')
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            // Save data
            $torrentService->addTorrentSensitive(
                $torrent->getId(),
                $user->getId(),
                time(),
                $request->get('sensitive') === 'true',
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

        // Render form template
        return $this->render(
            'default/torrent/edit/sensitive.html.twig',
            [
                'torrentId' => $torrent->getId(),
                'editions'  => $editions,
                'form'      => $form,
                'session' =>
                [
                    'moderator' => $user->isModerator(),
                    'owner'     => $user->getId() === $torrentSensitive->getUserId(),
                ]
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/approve/sensitive/{torrentSensitiveId}',
        name: 'torrent_sensitive_approve',
        requirements:
        [
            'torrentId'          => '\d+',
            'torrentSensitiveId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function approveSensitive(
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent sensitive
        if (!$torrentSensitive = $torrentService->getTorrentSensitive($request->get('torrentSensitiveId')))
        {
            throw $this->createNotFoundException();
        }

        // Check permissions
        if (!$user->isModerator())
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Update approved
        $torrentService->toggleTorrentSensitiveApproved(
            $torrentSensitive->getId()
        );

        // Redirect to info page created
        return $this->redirectToRoute(
            'torrent_sensitive_edit',
            [
                '_locale'            => $request->get('_locale'),
                'torrentId'          => $torrent->getId(),
                'torrentSensitiveId' => $torrentSensitive->getId(),
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/delete/sensitive/{torrentSensitiveId}',
        name: 'torrent_sensitive_delete',
        requirements:
        [
            'torrentId'          => '\d+',
            'torrentSensitiveId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function deleteSensitive(
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent sensitive
        if (!$torrentSensitive = $torrentService->getTorrentSensitive($request->get('torrentSensitiveId')))
        {
            throw $this->createNotFoundException();
        }

        // Check permissions
        if (!($user->isModerator() || $user->getId() === $torrentSensitive->getUserId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Update approved
        $torrentService->deleteTorrentSensitive(
            $torrentSensitive->getId()
        );

        // Redirect to info page created
        return $this->redirectToRoute(
            'torrent_sensitive_edit',
            [
                '_locale'            => $request->get('_locale'),
                'torrentId'          => $torrent->getId(),
                'torrentSensitiveId' => $torrentSensitive->getId(),
            ]
        );
    }
}
