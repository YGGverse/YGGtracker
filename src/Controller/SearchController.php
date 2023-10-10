<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Service\UserService;
use App\Service\PageService;
use App\Service\TorrentService;

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
        PageService $pageService,
        TorrentService $torrentService
    ): Response
    {
        // Init user
        $user = $userService->init(
            $request->getClientIp()
        );

        $page = $request->query->get('page') ? (int) $request->query->get('page') : 1;

        switch ($request->query->get('type'))
        {
            case 'page':

            break;
            case 'torrent':

                $torrents = [];
                foreach ($torrentService->searchTorrents($request->query->get('query')) as $torrent)
                {
                    // Read file
                    if (!$file = $torrentService->readTorrentFileByTorrentId($torrent->getId()))
                    {
                        continue; // @TODO
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
}