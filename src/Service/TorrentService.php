<?php

namespace App\Service;

use App\Entity\Torrent;
use App\Entity\TorrentLocales;
use App\Entity\TorrentSensitive;
use App\Entity\TorrentPoster;
use App\Entity\TorrentStar;
use App\Entity\TorrentDownloadFile;
use App\Entity\TorrentDownloadMagnet;

use App\Repository\TorrentRepository;
use App\Repository\TorrentLocalesRepository;
use App\Repository\TorrentSensitiveRepository;
use App\Repository\TorrentPosterRepository;
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

    public function generateTorrentKeywordsByString(
        string $string,
        int    $wordLengthMin,
        int    $wordLengthMax,
    ): array
    {
        $words = explode(
            ' ',
            preg_replace(
                '/[\s]+/',
                ' ',
                preg_replace(
                    '/[\W_]+/u',
                    ' ',
                    $string
                )
            )
        );

        // Apply words filter
        foreach ((array) $words as $key => $value)
        {
            // Apply word length filter
            $length = mb_strlen($value);

            if ($length < $wordLengthMin || $length > $wordLengthMax)
            {
                unset($words[$key]);
            }

            else
            {
                // Apply case insensitive search conversion
                $words[$key] = mb_strtolower($value);
            }
        }

        // Build simple array
        $keywords = [];
        foreach ((array) $words as $word)
        {
            $keywords[] = $word;
        }

        // Return unique keywords
        return array_unique(
            $keywords
        );
    }

    public function generateTorrentKeywordsByTorrentFilepath(

        string $filepath,

        bool   $extractName,
        bool   $extractFilenames,
        bool   $extractInfoHashV1,
        bool   $extractInfoHashV2,
        bool   $extractSource,
        bool   $extractComment,

        int    $wordLengthMin,
        int    $wordLengthMax

    ): array
    {
        $keywords = [];

        if ($file = $this->readTorrentFileByFilepath($filepath))
        {
            if ($extractName)
            {
                if ($name = $file->getName(false))
                {
                    $keywords = array_merge(
                        $keywords,
                        $this->generateTorrentKeywordsByString(
                            $name,
                            $wordLengthMin,
                            $wordLengthMax
                        )
                    );
                }
            }

            if ($extractFilenames)
            {
                foreach ($file->getFileList() as $list)
                {
                    $keywords = array_merge(
                        $keywords,
                        $this->generateTorrentKeywordsByString(
                            $list['path'],
                            $wordLengthMin,
                            $wordLengthMax
                        )
                    );
                }
            }

            if ($extractSource)
            {
                if ($source = $file->getSource(false))
                {
                    $keywords = array_merge(
                        $keywords,
                        $this->generateTorrentKeywordsByString(
                            $source,
                            $wordLengthMin,
                            $wordLengthMax
                        )
                    );
                }
            }

            if ($extractComment)
            {
                if ($comment = $file->getComment(false))
                {
                    $keywords = array_merge(
                        $keywords,
                        $this->generateTorrentKeywordsByString(
                            $comment,
                            $wordLengthMin,
                            $wordLengthMax
                        )
                    );
                }
            }

            if ($extractInfoHashV1)
            {
                if ($hash = $file->getInfoHashV1(false))
                {
                    $keywords[] = $hash;
                }
            }

            if ($extractInfoHashV2)
            {
                if ($hash = $file->getInfoHashV2(false))
                {
                    $keywords[] = $hash;
                }
            }
        }

        return array_unique(
            $keywords
        );
    }

    public function getImageUriByTorrentPosterId(
        int    $torrentPosterId,
        int    $quality   = 100,
        int    $width     = 748,
        int    $height    = 0,
        float  $opacity   = 1,
        bool   $grayscale = false,
        string $format    = 'webp'
    ): string
    {
        $uri = sprintf(
            '/posters/%s.%s',
            implode('/', str_split($torrentPosterId)),
            $format
        );

        $filename = sprintf(
            '%s/public/posters/%s.%s',
            $this->kernelInterface->getProjectDir(),
            implode('/', str_split($torrentPosterId)),
            $format
        );

        if (file_exists($filename))
        {
            return $uri;
        }

        $path = explode('/', $filename);

        array_pop($path);

        @mkdir(implode('/', $path), 0755, true);

        $image = new \Imagick();

        $image->readImage(
            $this->getStorageFilepathByTorrentPosterId(
                $torrentPosterId
            )
        );

        $image->setImageFormat($format);
        $image->setImageCompressionQuality($quality);

        if ($width || $height)
        {
            $image->adaptiveResizeImage(
                $width,
                $height
            );
        }

        if ($grayscale)
        {
            $image->setImageType(
                \Imagick::IMGTYPE_GRAYSCALE
            );
        }

        if ($opacity)
        {
            $image->setImageOpacity(
                $opacity
            );
        }

        $image->writeImage(
            $filename
        );

        return $uri;
    }

    public function getStorageFilepathByTorrentPosterId(int $torrentPosterId): string
    {
        return sprintf(
            '%s/var/posters/%s.original',
            $this->kernelInterface->getProjectDir(),
            implode('/', str_split($torrentPosterId))
        );
    }

    public function getStorageFilepathByTorrentId(int $torrentId): string
    {
        return sprintf(
            '%s/var/torrents/%s.torrent',
            $this->kernelInterface->getProjectDir(),
            implode('/', str_split($torrentId))
        );
    }

    public function getFtpFilepathByFilename(string $filename): string
    {
        return sprintf(
            '%s/var/ftp/%s',
            $this->kernelInterface->getProjectDir(),
            $filename
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

    public function copyToFtpStorage(
        int $torrentId,
        string $filename
    ): void
    {
        $filesystem = new Filesystem();
        $filesystem->copy(
            $this->getStorageFilepathByTorrentId(
                $torrentId
            ),
            $this->getFtpFilepathByFilename(
                $filename
            )
        );
    }

    public function removeFromFtpStorage(
        string $filename
    ): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(
            $this->getFtpFilepathByFilename(
                $filename
            )
        );
    }

    public function add(

        string $filepath,

        bool   $extractName,
        bool   $extractFilenames,
        bool   $extractInfoHashV1,
        bool   $extractInfoHashV2,
        bool   $extractSource,
        bool   $extractComment,

        int    $wordLengthMin,
        int    $wordLengthMax,

        int    $userId,
        int    $added,
        array  $locales,
        bool   $sensitive,
        bool   $approved,
        bool   $status

    ): ?Torrent
    {
        $torrent = $this->addTorrent(
            $userId,
            $added,
            md5_file($filepath),
            $this->generateTorrentKeywordsByTorrentFilepath(
                $filepath,
                $extractName,
                $extractFilenames,
                $extractInfoHashV1,
                $extractInfoHashV2,
                $extractSource,
                $extractComment,
                $wordLengthMin,
                $wordLengthMax
            ),
            $locales,
            $sensitive,
            $approved,
            $status
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
        bool $approved,
        bool $status
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
        $torrent->setStatus($status);

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

    public function toggleTorrentStatus(
        int $torrentId
    ): ?Torrent
    {
        $torrent = $this->getTorrent($torrentId);

        $torrent->setStatus(
            !$torrent->isStatus() // toggle current value
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
        int   $userId,
        array $keywords,
        array $locales,
        ?bool $sensitive,
        ?bool $approved,
        ?bool $status,
        int   $limit,
        int   $offset
    ) : array
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findTorrents(
                        $userId,
                        $keywords,
                        $locales,
                        $sensitive,
                        $approved,
                        $status,
                        $limit,
                        $offset
                    );
    }

    public function findTorrentsTotal(
        int   $userId,
        array $keywords,
        array $locales,
        ?bool $sensitive,
        ?bool $approved,
        ?bool $status
    ) : int
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findTorrentsTotal(
                        $userId,
                        $keywords,
                        $locales,
                        $sensitive,
                        $approved,
                        $status
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

    public function updateTorrentScraped(
        int $torrentId,
        int $time
    ): void
    {
        if ($torrent = $this->getTorrent($torrentId))
        {
            $torrent->setScraped(
                $time
            );

            $this->entityManagerInterface->persist($torrent);
            $this->entityManagerInterface->flush();
        }
    }

    public function updateTorrentScrape(
        int $torrentId,
        int $seeders,
        int $peers,
        int $leechers
    ): void
    {
        if ($torrent = $this->getTorrent($torrentId))
        {
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

            $this->entityManagerInterface->persist($torrent);
            $this->entityManagerInterface->flush();
        }
    }

    public function reindexTorrentKeywordsAll(
        bool $extractName,
        bool $extractFilenames,
        bool $extractInfoHashV1,
        bool $extractInfoHashV2,
        bool $extractSource,
        bool $extractComment,
        int  $wordLengthMin,
        int  $wordLengthMax
    ): void
    {
        foreach ($this->entityManagerInterface
                      ->getRepository(Torrent::class)
                      ->findAll() as $torrent)
        {
            $torrent->setKeywords(
                $this->generateTorrentKeywordsByTorrentFilepath(
                    $this->getStorageFilepathByTorrentId(
                        $torrent->getId()
                    ),
                    $extractName,
                    $extractFilenames,
                    $extractInfoHashV1,
                    $extractInfoHashV2,
                    $extractSource,
                    $extractComment,
                    $wordLengthMin,
                    $wordLengthMax
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

    // Torrent poster
    public function getTorrentPoster(
        int $torrentPosterId
    ): ?TorrentPoster
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentPoster::class)
                    ->find(
                        $torrentPosterId
                    );
    }

    public function findTorrentPosterByMd5File(
        string $md5file
    ): ?Torrent
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentPoster::class)
                    ->findOneBy(
                        [
                            'md5file' => $md5file
                        ]
        );
    }

    public function findLastTorrentPosterByTorrentId(
        int $torrentId
    ): ?TorrentPoster
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentPoster::class)
                    ->findOneBy(
                        [
                            'torrentId' => $torrentId
                        ],
                        [
                            'id' => 'DESC'
                        ]
                    );
    }

    public function findTorrentPosterByTorrentId(
        int $torrentId
    ): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentPoster::class)
                    ->findBy(
                        [
                            'torrentId' => $torrentId
                        ],
                        [
                            'id' => 'DESC'
                        ]
                    );
    }

    public function toggleTorrentPosterApproved(
        int $torrentPosterId
    ): ?TorrentPoster
    {
        $torrentPoster = $this->entityManagerInterface
                              ->getRepository(TorrentPoster::class)
                              ->find($torrentPosterId);

        $torrentPoster->setApproved(
            !$torrentPoster->isApproved() // toggle current value
        );

        $this->entityManagerInterface->persist($torrentPoster);
        $this->entityManagerInterface->flush();

        $this->updateTorrentPoster(
            $torrentPoster->getTorrentId()
        );

        return $torrentSensitive;
    }

    public function deleteTorrentPoster(
        int $torrentPosterId
    ): ?TorrentPoster
    {
        // Remove torrent file from permanent storage
        $filesystem = new Filesystem();
        $filesystem->remove(
            $this->getStorageFilepathByTorrentPosterId(
                $torrentPosterId
            )
        );

        // Remove from DB
        $torrentPoster = $this->getTorrentPoster(
            $torrentPosterId
        );

        $this->entityManagerInterface->remove($torrentPoster);
        $this->entityManagerInterface->flush();

        // Update torrent
        $this->updateTorrentPoster(
            $torrentPoster->getTorrentId()
        );

        return $torrentSensitive;
    }

    public function addTorrentPoster(
        string $filename,
        int $torrentId,
        int $userId,
        int $added,
        bool $approved
    ): ?TorrentPoster
    {
        // Add new DB record
        $torrentPoster = new TorrentPoster();

        $torrentPoster->setTorrentId($torrentId);
        $torrentPoster->setUserId($userId);
        $torrentPoster->setAdded($added);
        $torrentPoster->setApproved($approved);
        $torrentPoster->setMd5file(
            md5_file($filename)
        );

        $this->entityManagerInterface->persist($torrentPoster);
        $this->entityManagerInterface->flush();

        // Save file in permanent storage
        $filesystem = new Filesystem();
        $filesystem->copy(
            $filename,
            $this->getStorageFilepathByTorrentPosterId(
                $torrentPoster->getId()
            )
        );

        // Update torrent info
        $this->updateTorrentPoster(
            $torrentId
        );

        return $torrentPoster;
    }

    public function setTorrentPostersApprovedByUserId(
        int $userId,
        bool $value
    ): void
    {
        foreach ($this->entityManagerInterface
                      ->getRepository(TorrentPoster::class)
                      ->findBy(
                        [
                            'userId' => $userId
                        ]) as $torrentPoster)
        {
            $torrentPoster->setApproved(
                $value
            );

            $this->entityManagerInterface->persist($torrentPoster);
            $this->entityManagerInterface->flush();

            $this->updateTorrentPoster(
                $torrentPoster->getTorrentId(),
            );
        }
    }

    public function updateTorrentPoster(
        int $torrentId,
    ): void
    {
        if ($torrent = $this->getTorrent($torrentId))
        {
            if ($torrentPoster = $this->entityManagerInterface
                                      ->getRepository(TorrentPoster::class)
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
                $torrent->setTorrentPosterId(
                    $torrentPoster->getId()
                );

                $this->entityManagerInterface->persist($torrent);
                $this->entityManagerInterface->flush();
            }
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