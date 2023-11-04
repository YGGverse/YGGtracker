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
            '_locale'   => '%app.locales%',
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

        // Sensitive filter
        if (!$user->isModerator() && $user->isSensitive())
        {
            throw $this->createNotFoundException();
        }

        // Access filter
        if (!$user->isModerator() && $user->getId() != $torrent->getUserId() &&
           (!$torrent->isStatus() || !$torrent->isApproved()))
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

        // Create trackers list
        $appTrackers = explode('|', $this->getParameter('app.trackers'));
        $allTrackers = [];

        foreach ($appTrackers as $tracker)
        {
            $allTrackers[$tracker] = true;
        }

        foreach ($file->getAnnounceList() as $announce)
        {
            foreach ($announce as $tracker)
            {
                $allTrackers[$tracker] = !($user->isYggdrasil() && !in_array($tracker, $appTrackers));
            }
        }

        // Init page
        $page = $request->get('page') ? (int) $request->get('page') : 1;

        // Poster
        if ($user->isPosters() && $torrent->getTorrentPosterId())
        {
            $torrentPoster = $torrentService->getTorrentPoster(
                $torrent->getTorrentPosterId()
            );

            $poster = [
                'position' => $torrentPoster->getPosition(),
                'url'      => $request->getScheme() . '://' .
                              $request->getHttpHost() .
                              $request->getBasePath() .
                              $torrentService->getImageUriByTorrentPosterId(
                                  $torrentPoster->getId()
                              )
            ];
        }

        else
        {
            $poster = false;
        }

        // Render template
        return $this->render('default/torrent/info.html.twig',
        [
            'session' =>
            [
                'user'      => $user,
                'id'        => $user->getId(),
                'moderator' => $user->isModerator(),
                'owner'     => $user->getId() === $torrent->getUserId(),
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
                'keywords'   => $torrent->getKeywords(),
                'locales'    => $torrent->getLocales(),
                'categories' => $torrent->getCategories(),
                'sensitive'  => $torrent->isSensitive(),
                'approved'   => $torrent->isApproved(),
                'status'     => $torrent->isStatus(),
                'download'   =>
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
                'star' =>
                [
                    'exist' => (bool) $torrentService->findTorrentStar(
                        $torrent->getId(),
                        $user->getId()
                    ),
                    'total' => $torrentService->findTorrentStarsTotalByTorrentId(
                        $torrent->getId()
                    )
                ],
                'contributors' => $contributors,
                'poster' => $poster
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
              //'trackers' => $file->getAnnounceList(),
                'hash' =>
                [
                    'v1' => $file->getInfoHashV1(false),
                    'v2' => $file->getInfoHashV2(false)
                ],
            ],
            'trackers'   => $allTrackers,
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
        requirements:
        [
            '_locale' => '%app.locales%'
        ],
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
        $query = $request->get('query') ? explode(' ', urldecode($request->get('query'))) : [];
        $page  = $request->get('page') ? (int) $request->get('page') : 1;

        // Get total torrents
        $total = $torrentService->findTorrentsTotal(
            $user->getId(),
            $query,
            $user->getLocales(),
            $user->getCategories(),
            $user->isSensitive() ? false : null,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
        );

        $torrents = [];
        foreach ($torrentService->findTorrents(
            $user->getId(),
            $query,
            $user->getLocales(),
            $user->getCategories(),
            $user->isSensitive() ? false : null,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
            $this->getParameter('app.pagination'),
            ($page - 1) * $this->getParameter('app.pagination')
        ) as $torrent)
        {
            // Read file
            if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
            {
                throw $this->createNotFoundException(); // @TODO exception
            }

            // Generate keywords by extension
            $keywords = [];

            foreach ($file->getFileList() as $item)
            {
                if ($keyword = pathinfo($item['path'], PATHINFO_EXTENSION))
                {
                    $keyword = mb_strtolower($keyword);

                    if (isset($keywords[$keyword]))
                    {
                        $keywords[$keyword] = $keywords[$keyword] + (int) $item['size'];
                    }

                    else
                    {
                        $keywords[$keyword] = 0;
                    }
                }
            }

            arsort($keywords);

            // Poster
            if ($user->isPosters() && $torrent->getTorrentPosterId())
            {
                $torrentPoster = $torrentService->getTorrentPoster(
                    $torrent->getTorrentPosterId()
                );

                $poster = [
                    'position' => $torrentPoster->getPosition(),
                    'url'      => $request->getScheme() . '://' .
                                  $request->getHttpHost() .
                                  $request->getBasePath() .
                                  $torrentService->getImageUriByTorrentPosterId(
                                      $torrentPoster->getId()
                                  )
                ];
            }

            else
            {
                $poster = false;
            }

            // Push torrent
            $torrents[] =
            [
                'id'        => $torrent->getId(),
                'added'     => $torrent->getAdded(),
                'approved'  => $torrent->isApproved(),
                'sensitive' => $torrent->isSensitive(),
                'status'    => $torrent->isStatus(),
                'file'   =>
                [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'hash' =>
                    [
                        'v1' => $file->getInfoHashV1(false),
                        'v2' => $file->getInfoHashV2(false)
                    ],
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
                'poster' => $poster
            ];
        }

        return $this->render('default/torrent/list.html.twig', [
            'query'    => $request->get('query') ? urldecode($request->get('query')) : '',
            'torrents' => $torrents,
            'pagination' =>
            [
                'page'  => $page,
                'pages' => ceil($total / $this->getParameter('app.pagination')),
                'total' => $total
            ]
        ]);
    }

    #[Route(
        '/{_locale}',
        name: 'torrent_recent',
        requirements:
        [
            '_locale' => '%app.locales%'
        ],
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
            $user->getId(),
            [],
            $user->getLocales(),
            $user->getCategories(),
            $user->isSensitive() ? false : null,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
        );

        // Create torrents list
        $torrents = [];
        foreach ($torrentService->findTorrents(
            $user->getId(),
            [],
            $user->getLocales(),
            $user->getCategories(),
            $user->isSensitive() ? false : null,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
            $this->getParameter('app.pagination'),
            ($page - 1) * $this->getParameter('app.pagination')
        ) as $torrent)
        {
            // Read file
            if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
            {
                throw $this->createNotFoundException(); // @TODO exception
            }

            // Generate keywords by extension
            $keywords = [];

            foreach ($file->getFileList() as $item)
            {
                if ($keyword = pathinfo($item['path'], PATHINFO_EXTENSION))
                {
                    $keyword = mb_strtolower($keyword);

                    if (isset($keywords[$keyword]))
                    {
                        $keywords[$keyword] = $keywords[$keyword] + (int) $item['size'];
                    }

                    else
                    {
                        $keywords[$keyword] = 0;
                    }
                }
            }

            arsort($keywords);

            // Poster
            if ($user->isPosters() && $torrent->getTorrentPosterId())
            {
                $torrentPoster = $torrentService->getTorrentPoster(
                    $torrent->getTorrentPosterId()
                );

                $poster = [
                    'position' => $torrentPoster->getPosition(),
                    'url'      => $request->getScheme() . '://' .
                                  $request->getHttpHost() .
                                  $request->getBasePath() .
                                  $torrentService->getImageUriByTorrentPosterId(
                                      $torrentPoster->getId()
                                  )
                ];
            }

            else
            {
                $poster = false;
            }

            // Push torrent
            $torrents[] =
            [
                'id'        => $torrent->getId(),
                'added'     => $torrent->getAdded(),
                'approved'  => $torrent->isApproved(),
                'sensitive' => $torrent->isSensitive(),
                'status'    => $torrent->isStatus(),
                'file'   =>
                [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'hash' =>
                    [
                        'v1' => $file->getInfoHashV1(false),
                        'v2' => $file->getInfoHashV2(false)
                    ],
                ],
                'scrape' =>
                [
                    'seeders'   => (int) $torrent->getSeeders(),
                    'peers'     => (int) $torrent->getPeers(),
                    'leechers'  => (int) $torrent->getLeechers(),
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
                'poster' => $poster
            ];
        }

        return $this->render('default/torrent/list.html.twig', [
            'torrents'   => $torrents,
            'pagination' =>
            [
                'page'  => $page,
                'pages' => ceil($total / $this->getParameter('app.pagination')),
                'total' => $total
            ]
        ]);
    }

    #[Route(
        '/{_locale}/rss/torrents',
        name: 'rss_torrents_recent',
        requirements: [
            '_locale' => '%app.locales%'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function rssRecent(
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
        $query = $request->get('query') ? explode(' ', urldecode($request->get('query'))) : [];
        $page  = $request->get('page') ? (int) $request->get('page') : 1;

        // Get total torrents
        $total = $torrentService->findTorrentsTotal(
            $user->getId(),
            $query,
            $user->getLocales(),
            $user->getCategories(),
            $user->isSensitive() ? false : null,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
        );

        // Create torrents list
        $torrents = [];
        foreach ($torrentService->findTorrents(
            $user->getId(),
            $query,
            $user->getLocales(),
            $user->getCategories(),
            $user->isSensitive() ? false : null,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
            $this->getParameter('app.pagination'),
            ($page - 1) * $this->getParameter('app.pagination')
        ) as $torrent)
        {
            // Read file
            if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
            {
                throw $this->createNotFoundException(); // @TODO exception
            }

            $torrents[] =
            [
                'id'    => $torrent->getId(),
                'added' => $torrent->getAdded(),
                'file'  =>
                [
                    'name' => $file->getName(),
                ],
                'user' =>
                [
                    'id'   => $torrent->getUserId(),
                ],
            ];
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'default/torrent/list.rss.twig',
            [
                'torrents' => $torrents
            ],
            $response
        );
    }

    // #25
    // https://github.com/YGGverse/YGGtracker/issues/25
    #[Route(
        '/api/torrents',
        methods:
        [
            'GET'
        ]
    )]
    public function jsonRecent(
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
        $query  = $request->get('query') ?
                  explode(' ', urldecode($request->get('query'))) : [];

        $page   = $request->get('page') ?
                  (int) $request->get('page') : 1;

        $filter = $request->get('filter') ?
                  true : false;

        if ($request->get('locales'))
        {
            $locales = explode('|', $request->get('locales'));
        }

        else
        {
            $locales = $user->getLocales();
        }

        if ($request->get('categories'))
        {
            $categories = explode('|', $request->get('categories'));
        }

        else
        {
            $categories = $user->getCategories();
        }

        switch ($request->get('sensitive'))
        {
            case 'true':
                $sensitive = true;
            break;
            case 'false':
                $sensitive = false;
            break;
            default:
                $sensitive = $user->isSensitive() ? false : null;
        }

        switch ($request->get('yggdrasil'))
        {
            case 'true':
                $yggdrasil = true;
            break;
            case 'false':
                $yggdrasil = false;
            break;
            default:
                $yggdrasil = $user->isYggdrasil();
        }

        // Init trackers
        $trackers = explode('|', $this->getParameter('app.trackers'));

        // Get total torrents
        $total = $torrentService->findTorrentsTotal(
            $filter ? 0 : $user->getId(),
            $query,
            $locales,
            $categories,
            $sensitive,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
        );

        // Create torrents list
        $torrents = [];
        foreach ($torrentService->findTorrents(
            $filter ? 0 : $user->getId(),
            $query,
            $locales,
            $categories,
            $sensitive,
            !$user->isModerator() ? true : null,
            !$user->isModerator() ? true : null,
            $this->getParameter('app.pagination'),
            ($page - 1) * $this->getParameter('app.pagination')
        ) as $torrent)
        {
            // Read file
            if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
            {
                throw $this->createNotFoundException(); // @TODO exception
            }

            // Apply yggdrasil filters
            $file = $this->filterYggdrasil($file, $yggdrasil);

            $torrents[] =
            [
                'torrent' =>
                [
                    'id'         => $torrent->getId(),
                    'added'      => $torrent->getAdded(),
                    'locales'    => $torrent->getLocales(),
                    'categories' => $torrent->getCategories(),
                    'sensitive'  => $torrent->isSensitive(),
                    'file' =>
                    [
                        'name' => $file->getName(),
                        'size' => $file->getSize(),
                        'url'  => $this->generateUrl(
                            'torrent_file',
                            [
                                'torrentId' => $torrent->getId()
                            ],
                            false
                        )
                    ],
                    'magnet' =>
                    [
                        'url' => $this->generateUrl(
                            'torrent_magnet',
                            [
                                'torrentId' => $torrent->getId()
                            ],
                            false
                        ),
                      //'urn' => $file->getMagnetLink()
                    ],
                    'scrape' =>
                    [
                        'seeders'   => (int) $torrent->getSeeders(),
                        'peers'     => (int) $torrent->getPeers(),
                        'leechers'  => (int) $torrent->getLeechers(),
                    ],
                    'url' => $this->generateUrl(
                        'torrent_info',
                        [
                            '_locale'   => $user->getLocale(),
                            'torrentId' => $torrent->getId(),
                        ],
                        false
                    )
                ],
            ];
        }

        return $this->json(
            [
                'version' => time(),
                'tracker' =>
                [
                    'name'    => $this->getParameter('app.name'),
                    'version' => $this->getParameter('app.version'),
                    'url'     => $this->generateUrl(
                        'torrent_recent',
                        [
                            '_locale'   => $user->getLocale()
                        ],
                        false
                    )
                ],
                'torrents' => $torrents
            ]
        );
    }

    // Forms
    #[Route(
        '/{_locale}/submit',
        name: 'torrent_submit',
        requirements:
        [
            '_locale' => '%app.locales%'
        ],
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
            'categories' =>
            [
                'error' => [],
                'attribute' =>
                [
                    'value' => $request->get('categories') ? $request->get('categories') : [],
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

            /// Categories
            $categories = [];
            if ($request->get('categories'))
            {
                foreach ((array) $request->get('categories') as $locale)
                {
                    if (in_array($locale, explode('|', $this->getParameter('app.categories'))))
                    {
                        $categories[] = $locale;
                    }
                }
            }

            //// At least one valid locale required
            if (!$categories)
            {
                $form['categories']['error'][] = $translator->trans('At least one category required');
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
            if (empty($form['torrent']['error']) && empty($form['locales']['error']) && empty($form['categories']['error']))
            {
                // Save data
                $torrent = $torrentService->add(

                    $file->getPathName(),

                    (bool) $this->getParameter('app.index.torrent.name.enabled'),
                    (bool) $this->getParameter('app.index.torrent.filenames.enabled'),
                    (bool) $this->getParameter('app.index.torrent.hash.v1.enabled'),
                    (bool) $this->getParameter('app.index.torrent.hash.v2.enabled'),
                    (bool) $this->getParameter('app.index.torrent.source.enabled'),
                    (bool) $this->getParameter('app.index.torrent.comment.enabled'),
                    (int)  $this->getParameter('app.index.word.length.min'),
                    (int)  $this->getParameter('app.index.word.length.max'),

                    $user->getId(),
                    time(),
                    (array) $locales,
                    (array) $categories,
                    (bool) $request->get('sensitive'),
                    $user->isApproved(),
                    $user->isStatus()
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
                'locales'    => explode('|', $this->getParameter('app.locales')),
                'categories' => explode('|', $this->getParameter('app.categories')),
                'form'       => $form,
            ]
        );
    }

    // Torrent moderation
    #[Route(
        '/{_locale}/torrent/{torrentId}/approve/toggle',
        name: 'torrent_approve_toggle',
        requirements:
        [
            '_locale'   => '%app.locales%',
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

    #[Route(
        '/{_locale}/torrent/{torrentId}/status/toggle',
        name: 'torrent_status_toggle',
        requirements:
        [
            '_locale'   => '%app.locales%',
            'torrentId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function status(
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
        if (!($user->isModerator() || $user->getId() == $torrent->getUserId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Register activity event
        if (!$torrent->isStatus())
        {
            $activityService->addEventTorrentStatusAdd(
                $user->getId(),
                $torrent->getId(),
                time()
            );
        }

        else
        {
            $activityService->addEventTorrentStatusDelete(
                $user->getId(),
                $torrent->getId(),
                time()
            );
        }

        // Update status
        $torrentService->toggleTorrentStatus(
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
            '_locale'          => '%app.locales%',
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
                    'value' => $request->get('locales') ? $request->get('locales') : $torrentLocalesCurrent['value'],
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
            '_locale'          => '%app.locales%',
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
            '_locale'          => '%app.locales%',
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

    // Torrent categories
    #[Route(
        '/{_locale}/torrent/{torrentId}/edit/categories/{torrentCategoriesId}',
        name: 'torrent_categories_edit',
        requirements:
        [
            '_locale'             => '%app.locales%',
            'torrentId'           => '\d+',
            'torrentCategoriesId' => '\d+',
        ],
        defaults:
        [
            'torrentCategoriesId' => null,
        ],
        methods:
        [
            'GET',
            'POST'
        ]
    )]
    public function editCategories(
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

        // Init torrent categories
        $torrentCategoriesCurrent = [
            'userId' => null,
            'value'  => []
        ];

        // Get from edition version requested
        if ($request->get('torrentCategoriesId'))
        {
            if ($torrentCategories = $torrentService->getTorrentCategories($request->get('torrentCategoriesId')))
            {
                $torrentCategoriesCurrent['userId'] = $torrentCategories->getUserId();

                foreach ($torrentCategories->getValue() as $value)
                {
                    $torrentCategoriesCurrent['value'][] = $value;
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
            if ($torrentCategories = $torrentService->findLastTorrentCategoriesByTorrentId($torrent->getId()))
            {
                $torrentCategoriesCurrent['userId'] = $torrentCategories->getUserId();

                foreach ($torrentCategories->getValue() as $value)
                {
                    $torrentCategoriesCurrent['value'][] = $value;
                }

                // Update active categories
                $request->attributes->set('torrentCategoriesId', $torrentCategories->getId());
            }
        }

        // Init edition history
        $editions = [];
        foreach ($torrentService->findTorrentCategoriesByTorrentId($torrent->getId()) as $torrentCategoriesEdition)
        {
            $editions[] =
            [
                'id'       => $torrentCategoriesEdition->getId(),
                'added'    => $torrentCategoriesEdition->getAdded(),
                'approved' => $torrentCategoriesEdition->isApproved(),
                'active'   => $torrentCategoriesEdition->getId() == $request->get('torrentCategoriesId'),
                'user'     =>
                [
                    'id' => $torrentCategoriesEdition->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->getUser(
                            $torrentCategoriesEdition->getUserId()
                        )->getAddress()
                    ),
                ]
            ];
        }

        // Init form
        $form =
        [
            'categories' =>
            [
                'error'     => [],
                'attribute' =>
                [
                    'value' => $request->get('categories') ? $request->get('categories') : $torrentCategoriesCurrent['value'],
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            /// Categories
            $categories = [];
            if ($request->get('categories'))
            {
                foreach ((array) $request->get('categories') as $category)
                {
                    if (in_array($category, explode('|', $this->getParameter('app.categories'))))
                    {
                        $categories[] = $category;
                    }
                }
            }

            //// At least one valid category required
            if (!$categories)
            {
                $form['categories']['error'][] = $translator->trans('At least one category required');
            }

            // Request is valid
            if (empty($form['categories']['error']))
            {
                // Save data
                $torrentCategories = $torrentService->addTorrentCategories(
                    $torrent->getId(),
                    $user->getId(),
                    time(),
                    $categories,
                    $user->isApproved()
                );

                // Register activity event
                $activityService->addEventTorrentCategoriesAdd(
                    $user->getId(),
                    $torrent->getId(),
                    time(),
                    $torrentCategories->getId()
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
            'default/torrent/edit/categories.html.twig',
            [
                'torrentId'  => $torrent->getId(),
                'categories' => explode('|', $this->getParameter('app.categories')),
                'editions'   => $editions,
                'form'       => $form,
                'session'    =>
                [
                    'moderator' => $user->isModerator(),
                    'owner'     => $torrentCategoriesCurrent['userId'] === $user->getId(),
                ]
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/approve/categories/{torrentCategoriesId}',
        name: 'torrent_categories_approve',
        requirements:
        [
            '_locale'             => '%app.locales%',
            'torrentId'           => '\d+',
            'torrentCategoriesId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function approveCategories(
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

        // Init torrent categories
        if (!$torrentCategories = $torrentService->getTorrentCategories($request->get('torrentCategoriesId')))
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
        if (!$torrentCategories->isApproved())
        {
            $activityService->addEventTorrentCategoriesApproveAdd(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentCategories->getId()
            );
        }

        else
        {
            $activityService->addEventTorrentCategoriesApproveDelete(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentCategories->getId()
            );
        }

        // Update approved
        $torrentService->toggleTorrentCategoriesApproved(
            $torrentCategories->getId()
        );

        // Redirect back to form
        return $this->redirectToRoute(
            'torrent_categories_edit',
            [
                '_locale'             => $request->get('_locale'),
                'torrentId'           => $torrent->getId(),
                'torrentCategoriesId' => $torrentCategories->getId(),
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/delete/categories/{torrentCategoriesId}',
        name: 'torrent_categories_delete',
        requirements:
        [
            '_locale'             => '%app.locales%',
            'torrentId'           => '\d+',
            'torrentCategoriesId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function deleteCategories(
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

        // Init torrent categories
        if (!$torrentCategories = $torrentService->getTorrentCategories($request->get('torrentCategoriesId')))
        {
            throw $this->createNotFoundException();
        }

        // Check permissions
        if (!($user->isModerator() || $user->getId() === $torrentCategories->getUserId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Add activity event
        $activityService->addEventTorrentCategoriesDelete(
            $user->getId(),
            $torrent->getId(),
            time(),
            $torrentCategories->getId()
        );

        // Update approved
        $torrentService->deleteTorrentCategories(
            $torrentCategories->getId()
        );

        // Redirect back to form
        return $this->redirectToRoute(
            'torrent_categories_edit',
            [
                '_locale'             => $request->get('_locale'),
                'torrentId'           => $torrent->getId(),
                'torrentCategoriesId' => $torrentCategories->getId(),
            ]
        );
    }

    // Torrent sensitive
    #[Route(
        '/{_locale}/torrent/{torrentId}/edit/sensitive/{torrentSensitiveId}',
        name: 'torrent_sensitive_edit',
        requirements:
        [
            '_locale'            => '%app.locales%',
            'torrentId'          => '\d+',
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
                    'value' => $torrentSensitiveCurrent['value'],
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
            '_locale'            => '%app.locales%',
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
            '_locale'            => '%app.locales%',
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

    // Torrent poster
    #[Route(
        '/{_locale}/torrent/{torrentId}/edit/poster/{torrentPosterId}',
        name: 'torrent_poster_edit',
        requirements:
        [
            '_locale'         => '%app.locales%',
            'torrentId'       => '\d+',
            'torrentPosterId' => '\d+',
        ],
        defaults:
        [
            'torrentPosterId' => null,
        ],
        methods:
        [
            'GET',
            'POST'
        ]
    )]
    public function editPoster(
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

        // Init poster value
        if ($request->get('torrentPosterId'))
        {
            if ($torrentPoster = $torrentService->getTorrentPoster($request->get('torrentPosterId')))
            {
                $torrentPosterCurrent =
                [
                    'id'     => $torrentPoster->getId(),
                    'userId' => $torrentPoster->getUserId(),
                    'value'  => 'src' // @TODO
                ];
            }

            else
            {
                throw $this->createNotFoundException();
            }
        }
        else
        {
            if ($torrentPoster = $torrentService->findLastTorrentPosterByTorrentId($torrent->getId()))
            {
                $torrentPosterCurrent =
                [
                    'id'     => $torrentPoster->getId(),
                    'userId' => $torrentPoster->getUserId(),
                    'value'  => 'src' // @TODO
                ];
            }

            else
            {
                $torrentPosterCurrent =
                [
                    'id'     => null,
                    'userId' => null,
                    'value'  => false,
                ];
            }
        }

        // Init position
        $position = in_array(
            $request->get('position'),
            [
                'center',
                'top',
                'bottom'
            ]
        ) ? $request->get('position') : 'center';

        // Init edition history
        $editions = [];
        foreach ($torrentService->findTorrentPosterByTorrentId($torrent->getId()) as $torrentPosterEdition)
        {
            $editions[] =
            [
                'id'       => $torrentPosterEdition->getId(),
                'added'    => $torrentPosterEdition->getAdded(),
                'position' => $torrentPosterEdition->getPosition(),
                'approved' => $torrentPosterEdition->isApproved(),
                'active'   => $torrentPosterEdition->getId() == $torrentPosterCurrent['id'],
                'user'     =>
                [
                    'id' => $torrentPosterEdition->getUserId(),
                    'identicon' => $userService->identicon(
                        $userService->getUser(
                            $torrentPosterEdition->getUserId()
                        )->getAddress()
                    ),
                ],
                'poster'   =>
                    $request->getScheme() . '://' .
                    $request->getHttpHost() .
                    $request->getBasePath() .
                    $torrentService->getImageUriByTorrentPosterId(
                        $torrentPosterEdition->getId()
                    )
            ];
        }

        // Init form
        $form =
        [
            'poster' =>
            [
                'error' => []
            ],
            'position' =>
            [
                'error' => [],
                'attribute' =>
                [
                    'value' => $position
                ]
            ]
        ];

        // Process request
        if ($request->isMethod('post'))
        {
            if ($request->get('id') && $torrentService->getTorrentPoster($request->get('id')))
            {
                $filename = $torrentService->getStorageFilepathByTorrentPosterId(
                    $request->get('id')
                );
            }

            else if ($file = $request->files->get('poster'))
            {
                //// Validate poster file
                if (filesize($file->getPathName()) > $this->getParameter('app.torrent.poster.size.max'))
                {
                    $form['poster']['error'][] = $translator->trans('Poster file out of size limit');
                }

                //// Validate image format
                if (!@getimagesize($file->getPathName()))
                {
                    $form['poster']['error'][] = $translator->trans('Image file not supported');
                }

                $filename = $file->getPathName();
            }

            else
            {
                $form['poster']['error'][] = $translator->trans('Poster file required');

                $filename = false;
            }

            // Request is valid
            if (empty($form['poster']['error']))
            {
                // Save data
                $torrentPoster = $torrentService->addTorrentPoster(
                    $filename,
                    $position,
                    $torrent->getId(),
                    $user->getId(),
                    time(),
                    $user->isApproved()
                );

                // Add activity event
                $activityService->addEventTorrentPosterAdd(
                    $user->getId(),
                    $torrent->getId(),
                    time(),
                    $torrentPoster->getId()
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
            'default/torrent/edit/poster.html.twig',
            [
                'torrentId' => $torrent->getId(),
                'editions'  => $editions,
                'form'      => $form,
                'session' =>
                [
                    'moderator' => $user->isModerator(),
                    'owner'     => $torrentPosterCurrent['userId'] === $user->getId(),
                ]
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/approve/poster/{torrentPosterId}',
        name: 'torrent_poster_approve',
        requirements:
        [
            '_locale'         => '%app.locales%',
            'torrentId'       => '\d+',
            'torrentPosterId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function approvePoster(
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

        // Init torrent poster
        if (!$torrentPoster = $torrentService->getTorrentPoster($request->get('torrentPosterId')))
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
        if (!$torrentPoster->isApproved())
        {
            $activityService->addEventTorrentPosterApproveAdd(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentPoster->getId()
            );
        }

        else
        {
            $activityService->addEventTorrentPosterApproveDelete(
                $user->getId(),
                $torrent->getId(),
                time(),
                $torrentPoster->getId()
            );
        }

        // Update approved
        $torrentService->toggleTorrentPosterApproved(
            $torrentPoster->getId()
        );

        // Redirect
        return $this->redirectToRoute(
            'torrent_poster_edit',
            [
                '_locale'         => $request->get('_locale'),
                'torrentId'       => $torrent->getId(),
                'torrentPosterId' => $torrentPoster->getId(),
            ]
        );
    }

    #[Route(
        '/{_locale}/torrent/{torrentId}/delete/poster/{torrentPosterId}',
        name: 'torrent_poster_delete',
        requirements:
        [
            '_locale'         => '%app.locales%',
            'torrentId'       => '\d+',
            'torrentPosterId' => '\d+',
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function deletePoster(
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

        // Init torrent poster
        if (!$torrentPoster = $torrentService->getTorrentPoster($request->get('torrentPosterId')))
        {
            throw $this->createNotFoundException();
        }

        // Check permissions
        if (!($user->isModerator() || $user->getId() === $torrentPoster->getUserId()))
        {
            // @TODO
            throw new \Exception(
                $translator->trans('Access denied')
            );
        }

        // Add activity event
        $activityService->addEventTorrentPosterDelete(
            $user->getId(),
            $torrent->getId(),
            time(),
            $torrentPoster->getId()
        );

        // Update approved
        $torrentService->deleteTorrentPoster(
            $torrentPoster->getId()
        );

        // Redirect
        return $this->redirectToRoute(
            'torrent_poster_edit',
            [
                '_locale'         => $request->get('_locale'),
                'torrentId'       => $torrent->getId(),
                'torrentPosterId' => $torrentPoster->getId(),
            ]
        );
    }

    // Torrent star
    #[Route(
        '/{_locale}/torrent/{torrentId}/star/toggle',
        name: 'torrent_star_toggle',
        requirements:
        [
            '_locale'   => '%app.locales%',
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

        // Block crawler requests
        if (in_array($request->getClientIp(), explode('|', $this->getParameter('app.crawlers'))))
        {
            throw $this->createNotFoundException();
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
        '/torrent/{torrentId}/file',
        name: 'torrent_file',
        requirements:
        [
            'torrentId' => '\d+'
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

        // Block crawler requests
        if (in_array($request->getClientIp(), explode('|', $this->getParameter('app.crawlers'))))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            throw $this->createNotFoundException();
        }

        // Access filter
        if (!$user->isModerator() && $user->getId() != $torrent->getUserId() &&
           (!$torrent->isStatus() || !$torrent->isApproved()))
        {
            throw $this->createNotFoundException();
        }

        // Register download
        $torrentService->addTorrentDownloadFile(
            $torrent->getId(),
            $user->getId(),
            time()
        );

        // Request scrape
        $torrentService->updateTorrentScraped(
            $torrent->getId(),
            0
        );

        // Register download event
        $activityService->addEventTorrentDownloadFileAdd(
            $user->getId(),
            time(),
            $torrent->getId()
        );

        // Apply filters
        $file = $this->filterYggdrasil(
            $file,
            $user->isYggdrasil()
        );

        // Get data
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
                'attachment; filename="%s [%s#%s].torrent";',
                $file->getName(),
                $this->getParameter('app.name'),
                $torrent->getId()
            )
        );

        $response->sendHeaders();

        // Return file content
        return $response->setContent($data);
    }

    // Torrent download wanted file
    #[Route(
        '/torrent/{torrentId}/file/wanted',
        name: 'torrent_file_wanted',
        requirements:
        [
            'torrentId' => '\d+'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function downloadFileWanted(
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

        // Block crawler requests
        if (in_array($request->getClientIp(), explode('|', $this->getParameter('app.crawlers'))))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            throw $this->createNotFoundException();
        }

        // Access filter
        if (!$user->isModerator() && $user->getId() != $torrent->getUserId() &&
           (!$torrent->isStatus() || !$torrent->isApproved()))
        {
            throw $this->createNotFoundException();
        }

        // Register download
        $torrentService->addTorrentDownloadFile(
            $torrent->getId(),
            $user->getId(),
            time()
        );

        // Request scrape
        $torrentService->updateTorrentScraped(
            $torrent->getId(),
            0
        );

        // Register download event
        $activityService->addEventTorrentDownloadFileAdd(
            $user->getId(),
            time(),
            $torrent->getId()
        );

        // Apply filters
        $file = $this->filterYggdrasil(
            $file,
            false // wanted file downloading with original trackers
        );

        // Get data
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
                'attachment; filename="%s [wanted#%s].torrent";',
                $file->getName(),
                $torrent->getId()
            )
        );

        $response->sendHeaders();

        // Return file content
        return $response->setContent($data);
    }

    // Torrent download magnet
    #[Route(
        '/torrent/{torrentId}/magnet',
        name: 'torrent_magnet',
        requirements:
        [
            'torrentId' => '\d+'
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

        // Block crawler requests
        if (in_array($request->getClientIp(), explode('|', $this->getParameter('app.crawlers'))))
        {
            throw $this->createNotFoundException();
        }

        // Init torrent
        if (!$torrent = $torrentService->getTorrent($request->get('torrentId')))
        {
            throw $this->createNotFoundException();
        }

        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            throw $this->createNotFoundException();
        }

        // Access filter
        if (!$user->isModerator() && $user->getId() != $torrent->getUserId() &&
           (!$torrent->isStatus() || !$torrent->isApproved()))
        {
            throw $this->createNotFoundException();
        }

        // Register download
        $torrentService->addTorrentDownloadMagnet(
            $torrent->getId(),
            $user->getId(),
            time()
        );

        // Request scrape
        $torrentService->updateTorrentScraped(
            $torrent->getId(),
            0
        );

        // Register download event
        $activityService->addEventTorrentDownloadMagnetAdd(
            $user->getId(),
            time(),
            $torrent->getId()
        );

        // Apply filters
        $file = $this->filterYggdrasil(
            $file,
            $user->isYggdrasil()
        );

        // Return magnet link
        return $this->redirect(
            $file->getMagnetLink()
        );
    }

    // Tools
    #[Route(
        '/crontab/torrent/scrape/{key}',
        requirements: [
            'key' => '%app.key%'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function scrape(
        Request $request,
        TranslatorInterface $translator,
        TorrentService $torrentService,
        ActivityService $activityService
    ): Response
    {
        // Init Scraper
        $scraper = new \Yggverse\Scrapeer\Scraper();

        // Get next torrent in scrape queue
        if (!$torrent = $torrentService->getTorrentScrapeQueue())
        {
            throw $this->createNotFoundException();
        }

        // Get file
        if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
        {
            throw $this->createNotFoundException();
        }

        // Filter yggdrasil trackers
        $file = $this->filterYggdrasil($file, true);

        // Get trackers list
        $trackers = [];

        if ($announce = $file->getAnnounce())
        {
            $trackers[] = $announce;
        }

        if ($announceList = $file->getAnnounceList())
        {
            if (isset($announceList[0]))
            {
                foreach ($announceList[0] as $value)
                {
                    $trackers[] = $value;
                }
            }

            if (isset($announceList[1]))
            {
                foreach ($announceList[1] as $value)
                {
                    $trackers[] = $value;
                }
            }
        }

        $trackers = array_unique($trackers);

        // Get info hashes
        $hashes = [];

        if ($hash = $file->getInfoHashV1(false))
        {
            $hashes[] = $hash;
        }

        if ($hash = $file->getInfoHashV2(false))
        {
            $hashes[] = $hash;
        }

        // Get scrape
        $seeders  = 0;
        $peers    = 0;
        $leechers = 0;

        if ($hashes && $trackers)
        {
            // Update scrape info
            if ($results = $scraper->scrape($hashes, $trackers, null, 1))
            {
                foreach ($results as $result)
                {
                    if (isset($result['seeders']))
                    {
                        $seeders = $seeders + (int) $result['seeders'];
                    }

                    if (isset($result['completed']))
                    {
                        $peers = $peers + (int) $result['completed'];
                    }

                    if (isset($result['leechers']))
                    {
                        $leechers = $leechers + (int) $result['leechers'];
                    }
                }
            }
        }

        // Register activity event only on previous status changed
        if ($leechers && !$seeders &&
            $leechers != (int) $torrent->getLeechers() && $seeders != (int) $torrent->getSeeders())
        {
            $activityService->addEventTorrentWantedAdd(
                $torrent->getUserId(), // just required field, let's relate with author, because we don't know which exactly user requires for seeders from crontab @TODO
                time(),
                $torrent->getId()
            );
        }

        // Update DB
        $torrentService->updateTorrentScrape(
            $torrent->getId(),
            $seeders,
            $peers,
            $leechers
        );

        // Update torrent wanted storage if enabled
        if ($this->getParameter('app.torrent.wanted.ftp.enabled') === '1')
        {
            // Add wanted file
            if ($leechers && !$seeders)
            {
                if ($this->getParameter('app.torrent.wanted.ftp.approved') === '0' ||
                   ($this->getParameter('app.torrent.wanted.ftp.approved') === '1' && $torrent->isApproved()))
                {
                    /// All
                    $torrentService->copyToFtpStorage(
                        $torrent->getId(),
                        sprintf(
                            '%s/torrents/wanted/all/wanted#%s.torrent',
                            $this->getParameter('app.torrent.wanted.ftp.folder'),
                            $torrent->getId()
                        )
                    );

                    /// Sensitive
                    if ($torrent->isSensitive())
                    {
                        $torrentService->copyToFtpStorage(
                            $torrent->getId(),
                            sprintf(
                                '%s/torrents/wanted/sensitive/yes/wanted#%s.torrent',
                                $this->getParameter('app.torrent.wanted.ftp.folder'),
                                $torrent->getId()
                            )
                        );
                    }

                    else
                    {
                        $torrentService->copyToFtpStorage(
                            $torrent->getId(),
                            sprintf(
                                '%s/torrents/wanted/sensitive/no/wanted#%s.torrent',
                                $this->getParameter('app.torrent.wanted.ftp.folder'),
                                $torrent->getId()
                            )
                        );
                    }

                    /// Locals
                    foreach ($torrent->getLocales() as $locale)
                    {
                        $torrentService->copyToFtpStorage(
                            $torrent->getId(),
                            sprintf(
                                '%s/torrents/wanted/locale/%s/wanted#%s.torrent',
                                $this->getParameter('app.torrent.wanted.ftp.folder'),
                                $locale,
                                $torrent->getId()
                            )
                        );
                    }
                }
            }

            // Remove not wanted files
            else
            {
                /// All
                $torrentService->removeFromFtpStorage(
                    sprintf(
                        '%s/torrents/wanted/all/wanted#%s.torrent',
                        $this->getParameter('app.torrent.wanted.ftp.folder'),
                        $torrent->getId()
                    )
                );

                /// Sensitive
                $torrentService->removeFromFtpStorage(
                    sprintf(
                        '%s/torrents/wanted/sensitive/yes/wanted#%s.torrent',
                        $this->getParameter('app.torrent.wanted.ftp.folder'),
                        $torrent->getId()
                    )
                );

                $torrentService->removeFromFtpStorage(
                    sprintf(
                        '%s/torrents/wanted/sensitive/no/wanted#%s.torrent',
                        $this->getParameter('app.torrent.wanted.ftp.folder'),
                        $torrent->getId()
                    )
                );

                /// Locals
                foreach (explode('|', $this->getParameter('app.locales')) as $locale)
                {
                    $torrentService->removeFromFtpStorage(
                        sprintf(
                            '%s/torrents/wanted/locale/%s/wanted#%s.torrent',
                            $this->getParameter('app.torrent.wanted.ftp.folder'),
                            $locale,
                            $torrent->getId()
                        )
                    );
                }
            }
        }

        // Render response
        return new Response(); // @TODO
    }

    #[Route(
        '/tool/torrent/reindex/{key}',
        requirements: [
            'key' => '%app.key%'
        ],
        methods:
        [
            'GET'
        ]
    )]
    public function reindex(
        TorrentService $torrentService
    ): Response
    {
        // Reindex keywords
        $torrentService->reindexTorrentKeywordsAll(
            (bool) $this->getParameter('app.index.torrent.name.enabled'),
            (bool) $this->getParameter('app.index.torrent.filenames.enabled'),
            (bool) $this->getParameter('app.index.torrent.hash.v1.enabled'),
            (bool) $this->getParameter('app.index.torrent.hash.v2.enabled'),
            (bool) $this->getParameter('app.index.torrent.source.enabled'),
            (bool) $this->getParameter('app.index.torrent.comment.enabled'),
            (int)  $this->getParameter('app.index.word.length.min'),
            (int)  $this->getParameter('app.index.word.length.max')
        );

        // Render response
        return new Response(); // @TODO
    }

    #[Route(
        '/sitemap.xml',
        methods:
        [
            'GET'
        ]
    )]
    public function sitemap(
        TorrentService $torrentService
    ): Response
    {
        $locale  = $this->getParameter('app.locale');
        $locales = explode('|', $this->getParameter('app.locales'));

        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');

        return $this->render(
            'default/torrent/sitemap.xml.twig',
            [
                'locale'   => $locale,
                'locales'  => $locales,
                'torrents' => $torrentService->findTorrents(
                    0,           // no user session init, pass 0
                    [],          // without keywords filter
                    $locales,    // all system locales
                    $categories, // all system locales
                    null,        // all sensitive levels
                    true,        // approved only
                    true,        // enabled only
                    1000,        // @TODO limit
                    0            // offset
                )
            ],
            $response
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
                $this->getParameter('app.posters'),
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

    private function filterYggdrasil(
        ?\Rhilip\Bencode\TorrentFile $file, bool $yggdrasil, string $regex = '/^0{0,1}[2-3][a-f0-9]{0,2}:/'
    ):  ?\Rhilip\Bencode\TorrentFile
    {
        // Init trackers registry
        $allTrackers = [];

        // Get app trackers
        $appTrackers = explode('|', $this->getParameter('app.trackers'));

        // Append app trackers
        foreach ($appTrackers as $appTracker)
        {
            $allTrackers[] = $appTracker;
        }

        // Get original file announcements
        $announceList = $file->getAnnounceList();

        // Append original file announcements
        foreach ($announceList as $announce)
        {
            if (is_array($announce))
            {
                foreach ($announce as $value)
                {
                    $allTrackers[] = $value;
                }
            }

            else
            {
                $allTrackers[] = $value;
            }
        }

        // Remove duplicates
        $allTrackers = array_unique($allTrackers);

        // Yggdrasil-only mode
        if ($yggdrasil)
        {
            // Replace announce URL with first application tracker if original does not match Yggdrasil condition
            if (!preg_match($regex, str_replace(['[',']'], false, parse_url($value, PHP_URL_HOST))))
            {
                $file->setAnnounce(
                    $appTrackers[0]
                );
            }

            // Remove non-Yggdrasil trackers from announcement list
            foreach ($allTrackers as $key => $value)
            {
                // trackers
                if (!preg_match($regex, str_replace(['[',']'], false, parse_url($value, PHP_URL_HOST))))
                {
                    unset($allTrackers[$key]);
                }
            }
        }

        // Format announce list
        $trackers = [];

        foreach ($allTrackers as $value)
        {
            $trackers[] = [$value];
        }

        // Update announce list
        $file->setAnnounceList(
            $trackers
        );

        // Return filtered file
        return $file;
    }
}
