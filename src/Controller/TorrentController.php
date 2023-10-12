<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\TorrentService;
use App\Service\ActivityService;

class TorrentController extends AbstractController
{
    // Torrent
    #[Route(
        '/{_locale}/torrent/{torrentId}',
        name: 'torrent_info',
        requirements:
        [
            'torrentId' => '\d+',
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
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Get contributors
        $contributors = [];
        foreach ($torrentService->getTorrentContributors($torrent) as $userId)
        {
            $contributors[$userId] = $userService->identicon(
                $userService->getUser(
                    $userId
                )->getAddress()
            );
        }

        // Get total activities
        $total = $activityService->findActivitiesTotalByTorrentId(
            $torrent->getId(),
            $user->getEvents()
        );

        // Init page
        $page = $request->get('page') ? (int) $request->get('page') : 1;

        // Render template
        return $this->render('default/torrent/info.html.twig',
        [
            'user' =>
            [
                'id'        => $user->getId(),
                'moderator' => $user->isModerator()
            ],
            'torrent' =>
            [
                'id'        => $torrent->getId(),
                'md5file'   => $torrent->getMd5File(),
                'added'     => $torrent->getAdded(),
                'scrape'    =>
                [
                    'seeders'   => (int) $torrent->getSeeders(),
                    'peers'     => (int) $torrent->getPeers(),
                    'leechers'  => (int) $torrent->getLeechers(),
                ],
                'locales'   => $torrent->getLocales(),
                'sensitive' => $torrent->isSensitive(),
                'approved'  => $torrent->isApproved(),
                'download'  =>
                [
                    'file' =>
                    [
                        'exist' => (bool) $torrentService->findTorrentDownloadFile(
                            $torrent->getId(),
                            $user->getId()
                        ),
                        'total' => $torrentService->findTorrentDownloadFilesTotalByTorrentId(
                            $torrent->getId()
                        )
                    ],
                    'magnet' =>
                    [
                        'exist' => (bool) $torrentService->findTorrentDownloadMagnet(
                            $torrent->getId(),
                            $user->getId()
                        ),
                        'total' => $torrentService->findTorrentDownloadMagnetsTotalByTorrentId(
                            $torrent->getId()
                        )
                    ]
                ],
                'star'  =>
                [
                    'exist' => (bool) $torrentService->findTorrentStar(
                        $torrent->getId(),
                        $user->getId()
                    ),
                    'total' => $torrentService->findTorrentStarsTotalByTorrentId(
                        $torrent->getId()
                    )
                ],
                'contributors' => $contributors
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
            ],
            'trackers' => explode('|', $this->getParameter('app.trackers')),
            'activities' => $activityService->findLastActivitiesByTorrentId(
                $torrent->getId(),
                $user->getEvents(),
                $this->getParameter('app.pagination'),
                ($page - 1) * $this->getParameter('app.pagination')
            ),
            'pagination' =>
            [
                'page'  => $page,
                'pages' => ceil($total / $this->getParameter('app.pagination')),
                'total' => $total
            ]
        ]);
    }

    #[Route(
        '/{_locale}/search',
        name: 'torrent_search',
        methods:
        [
            'GET'
        ]
    )]
    public function search(
        Request $request,
        UserService $userService,
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        // Init request
        $query = $request->get('query') ? explode(' ', $request->get('query')) : [];
        $page  = $request->get('page') ? (int) $request->get('page') : 1;

        // Get total torrents
        $total = $torrentService->findTorrentsTotal(
            $query,
            $user->getLocales(),
            $user->isSensitive() ? false : null, // hide on sensitive mode enabled or show all
            $user->isModerator() ? null : true, // show approved content only for regular users
        );

        $torrents = [];
        foreach ($torrentService->findTorrents(
            $query,
            $user->getLocales(),
            $user->isSensitive() ? false : null, // hide on sensitive mode enabled or show all
            $user->isModerator() ? null : true, // show approved content only for regular users
            $this->getParameter('app.pagination'),
            ($page - 1) * $this->getParameter('app.pagination')
        ) as $torrent)
        {
            // Read file
            if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
            {
                throw $this->createNotFoundException(); // @TODO exception
            }

            // Generate keywords
            $keywords = [];
            foreach ($torrent->getKeywords() as $keyword)
            {
                if (in_array($keyword, $query))
                {
                    $keywords[] = urlencode($keyword);
                }
            }

            $torrents[] =
            [
                'id'     => $torrent->getId(),
                'added'  => $torrent->getAdded(),
                'file'   =>
                [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                ],
                'scrape' =>
                [
                    'seeders'   => (int) $torrent->getSeeders(),
                    'peers'     => (int) $torrent->getPeers(),
                    'leechers'  => (int) $torrent->getLeechers(),
                ],
                'user' =>
                [
                    'id'        => $torrent->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->getUser(
                            $torrent->getUserId()
                        )->getAddress()
                    )
                ],
                'keywords' => $keywords,
                'download'  =>
                [
                    'file' =>
                    [
                        'exist' => (bool) $torrentService->findTorrentDownloadFile(
                            $torrent->getId(),
                            $user->getId()
                        ),
                        'total' => $torrentService->findTorrentDownloadFilesTotalByTorrentId(
                            $torrent->getId()
                        )
                    ],
                    'magnet' =>
                    [
                        'exist' => (bool) $torrentService->findTorrentDownloadMagnet(
                            $torrent->getId(),
                            $user->getId()
                        ),
                        'total' => $torrentService->findTorrentDownloadMagnetsTotalByTorrentId(
                            $torrent->getId()
                        )
                    ]
                ],
                'star'  =>
                [
                    'exist' => (bool) $torrentService->findTorrentStar(
                        $torrent->getId(),
                        $user->getId()
                    ),
                    'total' => $torrentService->findTorrentStarsTotalByTorrentId(
                        $torrent->getId()
                    )
                ],
            ];
        }

        return $this->render('default/torrent/list.html.twig', [
            'query'    => $request->query->get('query'),
            'torrents' => $torrents
        ]);
    }

    #[Route(
        '/{_locale}',
        name: 'torrent_recent',
        methods:
        [
            'GET'
        ]
    )]
    public function recent(
        Request $request,
        UserService $userService,
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        // Init page
        $page = $request->get('page') ? (int) $request->get('page') : 1;

        // Get total torrents
        $total = $torrentService->findTorrentsTotal(
            [],
            $user->getLocales(),
            $user->isSensitive() ? false : null, // hide on sensitive mode enabled or show all
            $user->isModerator() ? null : true, // show approved content only for regular users
        );

        // Create torrents list
        $torrents = [];
        foreach ($torrentService->findTorrents(
            [],
            $user->getLocales(),
            $user->isSensitive() ? false : null, // hide on sensitive mode enabled or show all
            $user->isModerator() ? null : true, // show approved content only for regular users
            $this->getParameter('app.pagination'),
            ($page - 1) * $this->getParameter('app.pagination')
        ) as $torrent)
        {
            // Read file
            if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
            {
                throw $this->createNotFoundException(); // @TODO exception
            }

            // Generate keywords
            $keywords = [];
            $query = explode(' ', mb_strtolower($request->query->get('query')));
            foreach ($torrent->getKeywords() as $keyword)
            {
                if (in_array($keyword, $query))
                {
                    $keywords[] = urlencode($keyword);
                }
            }

            $torrents[] =
            [
                'id'     => $torrent->getId(),
                'added'  => $torrent->getAdded(),
                'file'   =>
                [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                ],
                'scrape' =>
                [
                    'seeders'   => (int) $torrent->getSeeders(),
                    'peers'     => (int) $torrent->getPeers(),
                    'leechers'  => (int) $torrent->getLeechers(),
                ],
                'user' =>
                [
                    'id'        => $torrent->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->getUser(
                            $torrent->getUserId()
                        )->getAddress()
                    )
                ],
                'keywords' => $keywords,
                'download'  =>
                [
                    'file' =>
                    [
                        'exist' => (bool) $torrentService->findTorrentDownloadFile(
                            $torrent->getId(),
                            $user->getId()
                        ),
                        'total' => $torrentService->findTorrentDownloadFilesTotalByTorrentId(
                            $torrent->getId()
                        )
                    ],
                    'magnet' =>
                    [
                        'exist' => (bool) $torrentService->findTorrentDownloadMagnet(
                            $torrent->getId(),
                            $user->getId()
                        ),
                        'total' => $torrentService->findTorrentDownloadMagnetsTotalByTorrentId(
                            $torrent->getId()
                        )
                    ]
                ],
                'star'  =>
                [
                    'exist' => (bool) $torrentService->findTorrentStar(
                        $torrent->getId(),
                        $user->getId()
                    ),
                    'total' => $torrentService->findTorrentStarsTotalByTorrentId(
                        $torrent->getId()
                    )
                ],
            ];
        }

        return $this->render('default/torrent/list.html.twig', [
            'query'    => $request->query->get('query'),
            'torrents' => $torrents
        ]);
    }

    // Forms
    #[Route(
        '/{_locale}/submit',
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
        TorrentService $torrentService,
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
            'locales' =>
            [
                'error' => [],
                'attribute' =>
                [
                    'value' => $request->get('locales') ? $request->get('locales') : [$request->get('_locale')],
                ]
            ],
            'torrent' =>
            [
                'error' => [],
            ],
            'sensitive' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value' => $request->get('sensitive'),
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

                //// Check for duplicates
                if ($torrentService->findTorrentByMd5File(md5_file($file->getPathName())))
                {
                    $form['torrent']['error'][] = $translator->trans('Torrent file already exists');
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

                // Add activity event
                $activityService->addEventTorrentAdd(
                    $user->getId(),
                    time(),
                    $torrent->getId()
                );

                // Redirect to info page
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


    #[Route(
        '/{_locale}/torrent/{torrentId}/approve/toggle',
        name: 'torrent_approve_toggle',
        requirements:
        [
            'torrentId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function approve(
        Request $request,
        UserService $userService,
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
        );

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
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

        // Register activity event
        if (!$torrent->isApproved())
        {
            $activityService->addEventTorrentApproveAdd(
                $user->getId(),
                $torrent->getId(),
                time()
            );
        }

        else
        {
            $activityService->addEventTorrentApproveDelete(
                $user->getId(),
                $torrent->getId(),
                time()
            );
        }

        // Update approved
        $torrentService->toggleTorrentApproved(
            $torrent->getId()
        );

        // Redirect back to form
        return $this->redirectToRoute(
            'torrent_info',
            [
                '_locale'   => $request->get('_locale'),
                'torrentId' => $torrent->getId()
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
        TorrentService $torrentService,
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent locales
        $torrentLocalesCurrent = [
            'userId' => null,
            'value'  => []
        ];

        // Get from edition version requested
        if ($request->get('torrentLocalesId'))
        {
            if ($torrentLocales = $torrentService->getTorrentLocales($request->get('torrentLocalesId')))
            {
                $torrentLocalesCurrent['userId'] = $torrentLocales->getUserId();

                foreach ($torrentLocales->getValue() as $value)
                {
                    $torrentLocalesCurrent['value'][] = $value;
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
            if ($torrentLocales = $torrentService->findLastTorrentLocalesByTorrentId($torrent->getId()))
            {
                $torrentLocalesCurrent['userId'] = $torrentLocales->getUserId();

                foreach ($torrentLocales->getValue() as $value)
                {
                    $torrentLocalesCurrent['value'][] = $value;
                }

                // Update active locale
                $request->attributes->set('torrentLocalesId', $torrentLocales->getId());
            }

            else
            {
                $torrentLocalesCurrent['value'][] = $request->get('_locale');
            }
        }

        // Init edition history
        $editions = [];
        foreach ($torrentService->findTorrentLocalesByTorrentId($torrent->getId()) as $torrentLocalesEdition)
        {
            $editions[] =
            [
                'id'       => $torrentLocalesEdition->getId(),
                'added'    => $torrentLocalesEdition->getAdded(),
                'approved' => $torrentLocalesEdition->isApproved(),
                'active'   => $torrentLocalesEdition->getId() == $request->get('torrentLocalesId'),
                'user'     =>
                [
                    'id' => $torrentLocalesEdition->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->getUser(
                            $torrentLocalesEdition->getUserId()
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
                    'value'       => $request->get('locales') ? $request->get('locales') : $torrentLocalesCurrent['value'],
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
                $torrentLocales = $torrentService->addTorrentLocales(
                    $torrent->getId(),
                    $user->getId(),
                    time(),
                    $locales,
                    $user->isApproved()
                );

                // Register activity event
                $activityService->addEventTorrentLocalesAdd(
                    $user->getId(),
                    $torrent->getId(),
                    time(),
                    $torrentLocales->getId()
                );

                // Redirect to info page
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
                    'owner'     => $torrentLocalesCurrent['userId'] === $user->getId(),
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
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Register activity event
        if (!$torrentLocales->isApproved())
        {
            $activityService->addEventTorrentLocalesApproveAdd(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentLocales->getId()
            );
        }

        else
        {
            $activityService->addEventTorrentLocalesApproveDelete(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentLocales->getId()
            );
        }

        // Update approved
        $torrentService->toggleTorrentLocalesApproved(
            $torrentLocales->getId()
        );

        // Redirect back to form
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
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Add activity event
        $activityService->addEventTorrentLocalesDelete(
            $user->getId(),
            $torrent->getId(),
            time(),
            $torrentLocales->getId()
        );

        // Update approved
        $torrentService->deleteTorrentLocales(
            $torrentLocales->getId()
        );

        // Redirect back to form
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
        TorrentService $torrentService,
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
                $torrentSensitiveCurrent =
                [
                    'id'     => $torrentSensitive->getId(),
                    'userId' => $torrentSensitive->getUserId(),
                    'value'  => $torrentSensitive->isValue(),
                ];
            }

            else
            {
                throw $this->createNotFoundException();
            }
        }
        else
        {
            if ($torrentSensitive = $torrentService->findLastTorrentSensitiveByTorrentId($torrent->getId()))
            {
                $torrentSensitiveCurrent =
                [
                    'id'     => $torrentSensitive->getId(),
                    'userId' => $torrentSensitive->getUserId(),
                    'value'  => $torrentSensitive->isValue(),
                ];
            }

            else
            {
                $torrentSensitiveCurrent =
                [
                    'id'     => null,
                    'userId' => null,
                    'value'  => false,
                ];
            }
        }

        // Init edition history
        $editions = [];
        foreach ($torrentService->findTorrentSensitiveByTorrentId($torrent->getId()) as $torrentSensitiveEdition)
        {
            $editions[] =
            [
                'id'       => $torrentSensitiveEdition->getId(),
                'added'    => $torrentSensitiveEdition->getAdded(),
                'approved' => $torrentSensitiveEdition->isApproved(),
                'active'   => $torrentSensitiveEdition->getId() == $torrentSensitiveCurrent['id'],
                'user'     =>
                [
                    'id' => $torrentSensitiveEdition->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->getUser(
                            $torrentSensitiveEdition->getUserId()
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
                    'value'       => $torrentSensitiveCurrent['value'],
                    'placeholder' => $translator->trans('Apply sensitive filters to publication')
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            // Save data
            $torrentSensitive = $torrentService->addTorrentSensitive(
                $torrent->getId(),
                $user->getId(),
                time(),
                $request->get('sensitive') === 'true',
                $user->isApproved()
            );

            // Add activity event
            $activityService->addEventTorrentSensitiveAdd(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentSensitive->getId()
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
                    'owner'     => $torrentSensitiveCurrent['userId'] === $user->getId(),
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
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Add activity event
        if (!$torrentSensitive->isApproved())
        {
            $activityService->addEventTorrentSensitiveApproveAdd(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentSensitive->getId()
            );
        }

        else
        {
            $activityService->addEventTorrentSensitiveApproveDelete(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentSensitive->getId()
            );
        }

        // Update approved
        $torrentService->toggleTorrentSensitiveApproved(
            $torrentSensitive->getId()
        );

        // Redirect
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
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init user
        $user = $this->initUser(
            $request,
            $userService,
            $activityService
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

        // Add activity event
        $activityService->addEventTorrentSensitiveDelete(
            $user->getId(),
            $torrent->getId(),
            time(),
            $torrentSensitive->getId()
        );

        // Update approved
        $torrentService->deleteTorrentSensitive(
            $torrentSensitive->getId()
        );

        // Redirect
        return $this->redirectToRoute(
            'torrent_sensitive_edit',
            [
                '_locale'            => $request->get('_locale'),
                'torrentId'          => $torrent->getId(),
                'torrentSensitiveId' => $torrentSensitive->getId(),
            ]
        );
    }

    // Torrent star
    #[Route(
        '/{_locale}/torrent/{torrentId}/star/toggle',
        name: 'torrent_star_toggle',
        requirements:
        [
            'torrentId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function toggleStar(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService,
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        // Update
        $value = $torrentService->toggleTorrentStar(
            $torrent->getId(),
            $user->getId(),
            time()
        );

        // Register activity event
        if ($value)
        {
            $activityService->addEventTorrentStarAdd(
                $user->getId(),
                time(),
                $torrent->getId()
            );
        }

        else
        {
            $activityService->addEventTorrentStarDelete(
                $user->getId(),
                time(),
                $torrent->getId()
            );
        }

        // Redirect
        return $this->redirectToRoute(
            'torrent_info',
            [
                '_locale'   => $request->get('_locale'),
                'torrentId' => $torrent->getId()
            ]
        );
    }

    // Torrent download file
    #[Route(
        '/{_locale}/torrent/{torrentId}/download/file',
        name: 'torrent_download_file',
        requirements:
        [
            'torrentId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function downloadFile(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService,
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('File not found')
            );
        }

        // Register download
        $torrentService->addTorrentDownloadFile(
            $torrent->getId(),
            $user->getId(),
            time()
        );

        // Register download event
        $activityService->addEventTorrentDownloadFileAdd(
            $user->getId(),
            time(),
            $torrent->getId()
        );

        // Filter trackers
        if ($user->isYggdrasil())
        {
            $file->setAnnounceList(
                [
                    explode('|', $this->getParameter('app.trackers'))
                ]
            );
        }

        $data = $file->dumpToString();

        // Set headers
        $response = new Response();

        $response->headers->set(
            'Content-type',
            'application/x-bittorrent'
        );

        $response->headers->set(
            'Content-length',
            strlen($data)
        );

        $response->headers->set(
            'Content-Disposition',
            sprintf(
                'attachment; filename="%s.%s.torrent";',
                mb_strtolower(
                    $this->getParameter('app.name')
                ),
                mb_strtolower(
                    $file->getName()
                )
            )
        );

        $response->sendHeaders();

        // Return file content
        return $response->setContent($data);
    }

    // Torrent download magnet
    #[Route(
        '/{_locale}/torrent/{torrentId}/download/magnet',
        name: 'torrent_download_magnet',
        requirements:
        [
            'torrentId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function getMagnet(
        Request $request,
        TranslatorInterface $translator,
        UserService $userService,
        TorrentService $torrentService,
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

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('File not found')
            );
        }

        // Register download
        $torrentService->addTorrentDownloadMagnet(
            $torrent->getId(),
            $user->getId(),
            time()
        );

        // Register download event
        $activityService->addEventTorrentDownloadMagnetAdd(
            $user->getId(),
            time(),
            $torrent->getId()
        );

        // Filter trackers
        if ($user->isYggdrasil())
        {
            $file->setAnnounceList(
                [
                    explode('|', $this->getParameter('app.trackers'))
                ]
            );
        }

        // Return magnet link
        return $this->redirect(
            $file->getMagnetLink()
        );
    }

    // Tools
    #[Route(
        '/crontab/torrent/scrape',
        methods:
        [
            'GET'
        ]
    )]
    public function scrape(
        Request $request,
        TranslatorInterface $translator,
        TorrentService $torrentService,
    ): Response
    {
        $torrentService->scrapeTorrentQueue(
            explode('|', $this->getParameter('app.trackers'))
        );

        // Render response
        return new Response(); // @TODO
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
