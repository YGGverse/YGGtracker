<?php

namespace App\Service;

use App\Entity\Torrent;
use App\Entity\TorrentLocales;
use App\Entity\TorrentSensitive;
use App\Entity\TorrentStar;
use App\Entity\TorrentDownloadFile;
use App\Entity\TorrentDownloadMagnet;

use App\Repository\TorrentRepository;
use App\Repository\TorrentLocalesRepository;
use App\Repository\TorrentSensitiveRepository;
use App\Repository\TorrentStarRepository;
use App\Repository\TorrentDownloadFileRepository;
use App\Repository\TorrentDownloadMagnetRepository;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Filesystem\Filesystem;

use Doctrine\ORM\EntityManagerInterface;

class TorrentService
{
    private KernelInterface $kernelInterface;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        KernelInterface $kernelInterface,
        EntityManagerInterface $entityManagerInterface
    )
    {
        $this->kernelInterface = $kernelInterface;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    // Tools
    public function scrapeTorrentQueue(
        array $trackers = []
    ): void
    {
        // Init Scraper
        $scraper = new \Yggverse\Scrapeer\Scraper();

        if ($torrent = $this->getTorrentScrapeQueue())
        {
            // Init default values
            $seeders  = 0;
            $peers    = 0;
            $leechers = 0;

            // Get file
            if ($file = $this->readTorrentFileByTorrentId($torrent->getId()))
            {
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
            }

            // Update torrent scrape
            $torrent->setSeeders(
                $seeders
            );

            $torrent->setPeers(
                $peers
            );

            $torrent->setLeechers(
                $leechers
            );

            $torrent->setScraped(
                time()
            );

            // Save results to DB
            $this->entityManagerInterface->persist($torrent);
            $this->entityManagerInterface->flush();
        }
    }

    public function readTorrentFileByFilepath(
        string $filepath
    ): ?\Rhilip\Bencode\TorrentFile
    {
        try
        {
            return \Rhilip\Bencode\TorrentFile::load(
                $filepath
            );
        }

        catch (\Rhilip\Bencode\ParseException $error)
        {
            return null;
        }
    }

    public function readTorrentFileByTorrentId(
        int $torrentId
    ): ?\Rhilip\Bencode\TorrentFile
    {
        return $this->readTorrentFileByFilepath(
            $this->getStorageFilepathByTorrentId($torrentId)
        );
    }

    public function generateTorrentKeywordsByTorrentFilepath(
        string $filepath,
        int $minLength = 3
    ): array
    {
        $keywords = [];

        if ($file = $this->readTorrentFileByFilepath($filepath))
        {
            foreach ($file->getFileList() as $list)
            {
                $words = explode(
                    ' ',
                    preg_replace(
                        '/[\s]+/',
                        ' ',
                        preg_replace(
                            '/[\W_]+/u',
                            ' ',
                            $list['path']
                        )
                    )
                );

                foreach ($words as $key => $value)
                {
                    if (mb_strlen($value) < $minLength)
                    {
                        unset($words[$key]);
                    }

                    else
                    {
                        $words[$key] = mb_strtolower($value);
                    }
                }

                if ($hash = $file->getInfoHashV1(false))
                {
                    $keywords[] = $hash;
                }

                if ($hash = $file->getInfoHashV2(false))
                {
                    $keywords[] = $hash;
                }

                if ($name = $file->getName(false))
                {
                    $keywords[] = $name;
                }

                $keywords = array_merge($keywords, $words);
            }
        }

        return array_unique($keywords);
    }

    public function getStorageFilepathByTorrentId(int $torrentId): string
    {
        return sprintf(
            '%s/var/torrents/%s.torrent',
            $this->kernelInterface->getProjectDir(),
            implode('/', str_split($torrentId))
        );
    }

    public function getTorrentContributors(Torrent $torrent): array
    {
        $contributors = [];

        foreach ($this->findTorrentLocalesByTorrentId($torrent->getId()) as $torrentLocale)
        {
            $contributors[] = $torrentLocale->getUserId();
        }

        foreach ($this->findTorrentSensitiveByTorrentId($torrent->getId()) as $torrentSensitive)
        {
            $contributors[] = $torrentSensitive->getUserId();
        }

        $contributors[] = $torrent->getUserId();

        return array_unique($contributors);
    }

    public function add(
        string $filepath,
        int $userId,
        int $added,
        array $locales,
        bool $sensitive,
        bool $approved
    ): ?Torrent
    {
        $torrent = $this->addTorrent(
            $userId,
            $added,
            md5_file($filepath),
            $this->generateTorrentKeywordsByTorrentFilepath(
                $filepath
            ),
            $locales,
            $sensitive,
            $approved
        );

        $filesystem = new Filesystem();
        $filesystem->copy(
            $filepath,
            $this->getStorageFilepathByTorrentId(
                $torrent->getId()
            )
        );

        $this->addTorrentLocales(
            $torrent->getId(),
            $userId,
            $added,
            $locales,
            $approved
        );

        $this->addTorrentSensitive(
            $torrent->getId(),
            $userId,
            $added,
            $sensitive,
            $approved
        );

        return $torrent;
    }

    // Torrent
    public function getTorrent(int $torrentId): ?Torrent
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->find($torrentId);
    }

    public function addTorrent(
        int $userId,
        int $added,
        string $md5file,
        array $keywords,
        array $locales,
        bool $sensitive,
        bool $approved
    ): ?Torrent
    {
        $torrent = new Torrent();

        $torrent->setUserId($userId);
        $torrent->setAdded($added);
        $torrent->setMd5File($md5file);
        $torrent->setKeywords($keywords);
        $torrent->setLocales($locales);
        $torrent->setSensitive($sensitive);
        $torrent->setApproved($approved);

        $this->entityManagerInterface->persist($torrent);
        $this->entityManagerInterface->flush();

        return $torrent;
    }

    public function toggleTorrentApproved(
        int $torrentId
    ): ?Torrent
    {
        $torrent = $this->getTorrent($torrentId);

        $torrent->setApproved(
            !$torrent->isApproved() // toggle current value
        );

        $this->entityManagerInterface->persist($torrent);
        $this->entityManagerInterface->flush();

        $this->updateTorrentLocales(
            $torrent->getId()
        );

        $this->updateTorrentSensitive(
            $torrent->getId()
        );

        return $torrent;
    }

    public function getTorrentScrapeQueue(): ?Torrent
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findOneBy(
                        [],
                        [
                            'scraped' => 'ASC'
                        ]
                    );
    }

    public function findTorrents(
        array $keywords,
        array $locales,
        ?bool $sensitive,
        ?bool $approved,
        int $limit,
        int $offset
    ) : array
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findTorrents(
                        $keywords,
                        $locales,
                        $sensitive,
                        $approved,
                        $limit,
                        $offset
                    );
    }

    public function findTorrentsTotal(
        array $keywords,
        array $locales,
        ?bool $sensitive,
        ?bool $approved
    ) : int
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findTorrentsTotal(
                        $keywords,
                        $locales,
                        $sensitive,
                        $approved
                    );
    }

    public function findTorrentByMd5File(string $md5file) : ?Torrent
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findOneBy(
                        [
                            'md5file' => $md5file
                        ]
                    );
    }

    public function updateTorrentSensitive(
        int $torrentId,
    ): void
    {
        if ($torrent = $this->getTorrent($torrentId))
        {
            if ($torrentSensitive = $this->entityManagerInterface
                                         ->getRepository(TorrentSensitive::class)
                                         ->findOneBy(
                                             [
                                                 'torrentId' => $torrentId,
                                                 'approved'  => true,
                                             ],
                                             [
                                                 'id' => 'DESC'
                                             ]
            ))
            {
                $torrent->setSensitive(
                    $torrentSensitive->isValue()
                );

                $this->entityManagerInterface->persist($torrent);
                $this->entityManagerInterface->flush();
            }
        }
    }

    public function updateTorrentLocales(
        int $torrentId
    ): void
    {
        if ($torrent = $this->getTorrent($torrentId))
        {
            if ($torrentLocales = $this->entityManagerInterface
                                       ->getRepository(TorrentLocales::class)
                                       ->findOneBy(
                                        [
                                            'torrentId' => $torrentId,
                                            'approved'  => true,
                                        ],
                                        [
                                            'id' => 'DESC'
                                        ]
            ))
            {
                $torrent->setLocales($torrentLocales->getValue());

                $this->entityManagerInterface->persist($torrent);
                $this->entityManagerInterface->flush();
            }
        }
    }

    public function reindexTorrentKeywordsAll(): void
    {
        foreach ($this->entityManagerInterface
                      ->getRepository(Torrent::class)
                      ->findAll() as $torrent)
        {
            $torrent->setKeywords(
                $this->generateTorrentKeywordsByTorrentFilepath(
                    $this->getStorageFilepathByTorrentId(
                        $torrent->getId()
                    )
                )
            );

            $this->entityManagerInterface->persist($torrent);
            $this->entityManagerInterface->flush();
        }
    }

    public function setTorrentApprovedByTorrentId(
        int  $torrentId,
        bool $value
    ): void
    {
        if ($torrent = $this->getTorrent($torrentId))
        {
            $torrent->setApproved($value);

            $this->entityManagerInterface->persist($torrent);
            $this->entityManagerInterface->flush();
        }
    }

    public function setTorrentsApprovedByUserId(
        int $userId,
        bool $value
    ): void
    {
        foreach ($this->entityManagerInterface
                      ->getRepository(Torrent::class)
                      ->findBy(
                        [
                            'userId' => $userId
                        ]) as $torrent)
        {
            $torrent->setApproved(
                $value
            );

            $this->entityManagerInterface->persist($torrent);
            $this->entityManagerInterface->flush();
        }
    }

    // Torrent locale
    public function getTorrentLocales(
        int $torrentLocaleId
    ): ?TorrentLocales
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->find($torrentLocaleId);
    }

    public function findLastTorrentLocalesByTorrentId(
        int $torrentId
    ): ?TorrentLocales
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->findOneBy(
                        [
                            'torrentId' => $torrentId
                        ],
                        [
                            'id' => 'DESC'
                        ]
                    );
    }

    public function findTorrentLocalesByTorrentId(int $torrentId): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->findBy(
                        [
                            'torrentId' => $torrentId,
                        ],
                        [
                            'id' => 'DESC'
                        ]
                    );
    }

    public function toggleTorrentLocalesApproved(
        int $torrentLocalesId
    ): ?TorrentLocales
    {
        $torrentLocales = $this->getTorrentLocales($torrentLocalesId);

        $torrentLocales->setApproved(
            !$torrentLocales->isApproved() // toggle current value
        );

        $this->entityManagerInterface->persist($torrentLocales);
        $this->entityManagerInterface->flush();

        $this->updateTorrentLocales(
            $torrentLocales->getTorrentId()
        );

        return $torrentLocales;
    }

    public function deleteTorrentLocales(
        int $torrentLocalesId
    ): ?TorrentLocales
    {
        $torrentLocales = $this->getTorrentLocales($torrentLocalesId);

        $this->entityManagerInterface->remove($torrentLocales);
        $this->entityManagerInterface->flush();

        $this->updateTorrentLocales(
            $torrentLocales->getTorrentId()
        );

        return $torrentLocales;
    }

    public function addTorrentLocales(
        int $torrentId,
        int $userId,
        int $added,
        array $value,
        bool $approved
    ): ?TorrentLocales
    {
        $torrentLocales = new TorrentLocales();

        $torrentLocales->setTorrentId($torrentId);
        $torrentLocales->setUserId($userId);
        $torrentLocales->setAdded($added);
        $torrentLocales->setValue($value);
        $torrentLocales->setApproved($approved);

        $this->entityManagerInterface->persist($torrentLocales);
        $this->entityManagerInterface->flush();

        $this->updateTorrentLocales(
            $torrentId
        );

        return $torrentLocales;
    }

    public function setTorrentLocalesApprovedByUserId(
        int $userId,
        bool $value
    ): void
    {
        foreach ($this->entityManagerInterface
                      ->getRepository(TorrentLocales::class)
                      ->findBy(
                        [
                            'userId' => $userId
                        ]) as $torrentLocales)
        {
            $torrentLocales->setApproved(
                $value
            );

            $this->entityManagerInterface->persist($torrentLocales);
            $this->entityManagerInterface->flush();

            $this->updateTorrentLocales(
                $torrentLocales->getTorrentId(),
            );
        }
    }

    // Torrent sensitive
    public function getTorrentSensitive(
        int $torrentSensitiveId
    ): ?TorrentSensitive
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->find(
                        $torrentSensitiveId
                    );
    }

    public function findLastTorrentSensitiveByTorrentId(int $torrentId): ?TorrentSensitive
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->findOneBy(
                        [
                            'torrentId' => $torrentId
                        ],
                        [
                            'id' => 'DESC'
                        ]
                    );
    }

    public function findTorrentSensitiveByTorrentId(int $torrentId): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->findBy(
                        [
                            'torrentId' => $torrentId
                        ],
                        [
                            'id' => 'DESC'
                        ]
                    );
    }

    public function toggleTorrentSensitiveApproved(
        int $torrentSensitiveId
    ): ?TorrentSensitive
    {
        $torrentSensitive = $this->entityManagerInterface
                                 ->getRepository(TorrentSensitive::class)
                                 ->find($torrentSensitiveId);

        $torrentSensitive->setApproved(
            !$torrentSensitive->isApproved() // toggle current value
        );

        $this->entityManagerInterface->persist($torrentSensitive);
        $this->entityManagerInterface->flush();

        $this->updateTorrentSensitive(
            $torrentSensitive->getTorrentId()
        );

        return $torrentSensitive;
    }

    public function deleteTorrentSensitive(
        int $torrentSensitiveId
    ): ?TorrentSensitive
    {
        $torrentSensitive = $this->getTorrentSensitive(
            $torrentSensitiveId
        );

        $this->entityManagerInterface->remove($torrentSensitive);
        $this->entityManagerInterface->flush();

        $this->updateTorrentSensitive(
            $torrentSensitive->getTorrentId()
        );

        return $torrentSensitive;
    }

    public function addTorrentSensitive(
        int $torrentId,
        int $userId,
        int $added,
        bool $value,
        bool $approved
    ): ?TorrentSensitive
    {
        $torrentSensitive = new TorrentSensitive();

        $torrentSensitive->setTorrentId($torrentId);
        $torrentSensitive->setUserId($userId);
        $torrentSensitive->setAdded($added);
        $torrentSensitive->setValue($value);
        $torrentSensitive->setApproved($approved);

        $this->entityManagerInterface->persist($torrentSensitive);
        $this->entityManagerInterface->flush();

        $this->updateTorrentSensitive(
            $torrentId
        );

        return $torrentSensitive;
    }

    public function setTorrentSensitivesApprovedByUserId(
        int $userId,
        bool $value
    ): void
    {
        foreach ($this->entityManagerInterface
                      ->getRepository(TorrentSensitive::class)
                      ->findBy(
                        [
                            'userId' => $userId
                        ]) as $torrentSensitive)
        {
            $torrentSensitive->setApproved(
                $value
            );

            $this->entityManagerInterface->persist($torrentSensitive);
            $this->entityManagerInterface->flush();

            $this->updateTorrentSensitive(
                $torrentSensitive->getTorrentId(),
            );
        }
    }

    // Torrent star
    public function findTorrentStar(
        int $torrentId,
        int $userId
    ): ?TorrentStar
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentStar::class)
                    ->findOneBy(
                        [
                            'torrentId' => $torrentId,
                            'userId'    => $userId,
                        ]
                    );
    }

    public function findTorrentStarsTotalByTorrentId(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentStar::class)
                    ->findTorrentStarsTotalByTorrentId($torrentId);
    }

    public function toggleTorrentStar(
        int $torrentId,
        int $userId,
        int $added
    ): bool
    {
        if ($torrentStar = $this->findTorrentStar($torrentId, $userId))
        {
            $this->entityManagerInterface->remove($torrentStar);
            $this->entityManagerInterface->flush();

            return false;
        }

        else
        {
            $torrentStar = new TorrentStar();

            $torrentStar->setTorrentId($torrentId);
            $torrentStar->setUserId($userId);
            $torrentStar->setAdded($added);

            $this->entityManagerInterface->persist($torrentStar);
            $this->entityManagerInterface->flush();

            return true;
        }
    }

    // Torrent download file
    public function findTorrentDownloadFile(
        int $torrentId,
        int $userId
    ): ?TorrentDownloadFile
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentDownloadFile::class)
                    ->findOneBy(
                        [
                            'torrentId' => $torrentId,
                            'userId'    => $userId
                        ]
                    );
    }

    public function findTorrentDownloadFilesTotalByTorrentId(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentDownloadFile::class)
                    ->findTorrentDownloadFilesTotalByTorrentId($torrentId);
    }

    public function addTorrentDownloadFile(
        int $torrentId,
        int $userId,
        int $added
    ): void
    {
        if (!$this->findTorrentDownloadFile($torrentId, $userId))
        {
            $torrentDownloadFile = new TorrentDownloadFile();

            $torrentDownloadFile->setTorrentId($torrentId);
            $torrentDownloadFile->setUserId($userId);
            $torrentDownloadFile->setAdded($added);

            $this->entityManagerInterface->persist($torrentDownloadFile);
            $this->entityManagerInterface->flush();
        }
    }

    // Torrent download magnet
    public function findTorrentDownloadMagnet(
        int $torrentId,
        int $userId
    ): ?TorrentDownloadMagnet
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentDownloadMagnet::class)
                    ->findOneBy(
                        [
                            'torrentId' => $torrentId,
                            'userId'    => $userId
                        ]
                    );
    }

    public function findTorrentDownloadMagnetsTotalByTorrentId(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentDownloadMagnet::class)
                    ->findTorrentDownloadMagnetsTotalByTorrentId($torrentId);
    }

    public function addTorrentDownloadMagnet(
        int $torrentId,
        int $userId,
        int $added
    ): void
    {
        if (!$this->findTorrentDownloadMagnet($torrentId, $userId))
        {
            $torrentDownloadMagnet = new TorrentDownloadMagnet();

            $torrentDownloadMagnet->setTorrentId($torrentId);
            $torrentDownloadMagnet->setUserId($userId);
            $torrentDownloadMagnet->setAdded($added);

            $this->entityManagerInterface->persist($torrentDownloadMagnet);
            $this->entityManagerInterface->flush();
        }
    }
}