<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\ArticleService;
use App\Service\TorrentService;
use App\Service\ActivityService;

class SearchController extends AbstractController
{
    #[Route(
        '/{_locale}/search',
        name: 'search_index',
        methods:
        [
            'GET'
        ]
    )]
    public function index(
        Request $request,
        UserService $userService,
        ArticleService $articleService,
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

        $article = $request->query->get('article') ? (int) $request->query->get('article') : 1;

        switch ($request->query->get('type'))
        {
            case 'article':

            break;

            case 'torrent':

                $total = 0; // @TODO pagination

                $torrents = [];
                foreach ($torrentService->searchTorrents($request->query->get('query')) as $torrent)
                {
                    // Apply locales filter
                    if ($lastTorrentLocales = $torrentService->findLastTorrentLocalesByTorrentIdApproved($torrent->getId()))
                    {
                        if (!count(
                            array_intersect(
                                $lastTorrentLocales->getValue(),
                                $user->getLocales()
                            )
                        )) {
                            $total--;
                            continue;
                        }
                    }

                    // Apply sensitive filters
                    if ($lastTorrentSensitive = $torrentService->findLastTorrentSensitiveByTorrentIdApproved($torrent->getId()))
                    {
                        if ($user->isSensitive() && $lastTorrentSensitive->isValue())
                        {
                            $total--;
                            continue;
                        }
                    }

                    // Read file
                    if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
                    {
                        $total--;
                        continue; // @TODO exception
                    }

                    // Generate keywords
                    $keywords = [];
                    $query = explode(' ', mb_strtolower($request->query->get('query')));
                    foreach (explode(',', $torrent->getKeywords()) as $keyword)
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

                return $this->render('default/search/torrent.html.twig', [
                    'query'    => $request->query->get('query'),
                    'torrents' => $torrents
                ]);

            break;

            default:

                throw $this->createNotFoundException();
        }
    }

    public function module(
        ?string $query,
        ?string $type
    ): Response
    {
        return $this->render('default/search/module.html.twig', [
            'query' => $query,
            'type'  => $type,
        ]);
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