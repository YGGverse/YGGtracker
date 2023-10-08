<?php

namespace App\Service;

use App\Entity\Torrent;
use App\Entity\TorrentLocales;
use App\Entity\TorrentSensitive;
use App\Entity\TorrentBookmark;
use App\Entity\TorrentDownloadFile;
use App\Entity\TorrentDownloadMagnet;

use App\Repository\TorrentRepository;
use App\Repository\TorrentLocalesRepository;
use App\Repository\TorrentSensitiveRepository;
use App\Repository\TorrentBookmarkRepository;
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
        int $id
    ): ?\Rhilip\Bencode\TorrentFile
    {
        return $this->readTorrentFileByFilepath(
            $this->getStorageFilepathById($id)
        );
    }

    public function generateTorrentKeywordsByTorrentFilepath(
        string $filepath,
        int $minLength = 3
    ): string
    {
        $keywords = [];

        foreach ($this->readTorrentFileByFilepath($filepath)->getFileList() as $file)
        {
            $words = explode(
                ' ',
                preg_replace(
                    '/[\s]+/',
                    ' ',
                    preg_replace(
                        '/[\W]+/',
                        ' ',
                        $file['path']
                    )
                )
            );

            foreach ($words as $key => $value)
            {
                if (mb_strlen($value) < $minLength)
                {
                    unset($words[$key]);
                }
            }

            $keywords = array_merge($keywords, $words);
        }

        return mb_strtolower(
            implode(
                ',',
                array_unique($keywords)
            )
        );
    }

    public function getStorageFilepathById(int $id): string
    {
        return sprintf(
            '%s/var/torrents/%s.torrent',
            $this->kernelInterface->getProjectDir(),
            implode('/', str_split($id))
        );
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
            $this->generateTorrentKeywordsByTorrentFilepath(
                $filepath
            ),
            $approved
        );

        $filesystem = new Filesystem();
        $filesystem->copy(
            $filepath,
            $this->getStorageFilepathById(
                $torrent->getId()
            )
        );

        if (!empty($locales))
        {
            $this->addTorrentLocales(
                $torrent->getId(),
                $userId,
                $added,
                $locales,
                $approved
            );
        }

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
    public function getTorrent(int $id): ?Torrent
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->getTorrent($id);
    }

    public function addTorrent(
        int $userId,
        int $added,
        string $keywords,
        bool $approved
    ): ?Torrent
    {
        $torrent = new Torrent();

        $torrent->setUserId($userId);
        $torrent->setAdded($added);
        $torrent->setKeywords($keywords);
        $torrent->setApproved($approved);

        $this->entityManagerInterface->persist($torrent);
        $this->entityManagerInterface->flush();

        return $torrent;
    }

    // Torrent locale
    public function getTorrentLocales(int $id): ?TorrentLocales
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->getTorrentLocales($id);
    }

    public function findLastTorrentLocalesByTorrentId(int $torrentId): ?TorrentLocales
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->findLastTorrentLocalesByTorrentId($torrentId);
    }

    public function findTorrentLocalesByTorrentId(int $torrentId): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->findTorrentLocalesByTorrentId($torrentId);
    }

    public function toggleTorrentLocalesApproved(
        int $id
    ): ?TorrentLocales
    {
        $torrentLocales = $this->getTorrentLocales($id);

        $torrentLocales->setApproved(
            !$torrentLocales->isApproved() // toggle current value
        );

        $this->entityManagerInterface->persist($torrentLocales);
        $this->entityManagerInterface->flush();

        return $torrentLocales;
    }

    public function deleteTorrentLocales(
        int $id
    ): ?TorrentLocales
    {
        $torrentLocales = $this->getTorrentLocales($id);

        $this->entityManagerInterface->remove($torrentLocales);
        $this->entityManagerInterface->flush();

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

        return $torrentLocales;
    }

    // Torrent sensitive
    public function getTorrentSensitive(int $id): ?TorrentSensitive
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->getTorrentSensitive($id);
    }

    public function findLastTorrentSensitiveByTorrentId(int $torrentId): ?TorrentSensitive
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->findLastTorrentSensitiveByTorrentId($torrentId);
    }

    public function findTorrentSensitiveByTorrentId(int $torrentId): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->findTorrentSensitiveByTorrentId($torrentId);
    }

    public function toggleTorrentSensitiveApproved(
        int $id
    ): ?TorrentSensitive
    {
        $torrentSensitive = $this->getTorrentSensitive($id);

        $torrentSensitive->setApproved(
            !$torrentSensitive->isApproved() // toggle current value
        );

        $this->entityManagerInterface->persist($torrentSensitive);
        $this->entityManagerInterface->flush();

        return $torrentSensitive;
    }

    public function deleteTorrentSensitive(
        int $id
    ): ?TorrentSensitive
    {
        $torrentSensitive = $this->getTorrentSensitive($id);

        $this->entityManagerInterface->remove($torrentSensitive);
        $this->entityManagerInterface->flush();

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

        return $torrentSensitive;
    }

    // Torrent bookmark
    public function findTorrentBookmark(
        int $torrentId,
        int $userId
    ): ?TorrentBookmark
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentBookmark::class)
                    ->findTorrentBookmark($torrentId, $userId);
    }

    public function findTorrentBookmarksTotalByTorrentId(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentBookmark::class)
                    ->findTorrentBookmarksTotalByTorrentId($torrentId);
    }

    public function toggleTorrentBookmark(
        int $torrentId,
        int $userId,
        int $added
    ): void
    {
        if ($torrentBookmark = $this->findTorrentBookmark($torrentId, $userId))
        {
            $this->entityManagerInterface->remove($torrentBookmark);
            $this->entityManagerInterface->flush();
        }

        else
        {
            $torrentBookmark = new TorrentBookmark();

            $torrentBookmark->setTorrentId($torrentId);
            $torrentBookmark->setUserId($userId);
            $torrentBookmark->setAdded($added);

            $this->entityManagerInterface->persist($torrentBookmark);
            $this->entityManagerInterface->flush();
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
                    ->findTorrentDownloadFile($torrentId, $userId);
    }

    public function findTorrentDownloadFilesTotalByTorrentId(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentDownloadFile::class)
                    ->findTorrentDownloadFilesTotalByTorrentId($torrentId);
    }

    public function registerTorrentDownloadFile(
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
                    ->findTorrentDownloadMagnet($torrentId, $userId);
    }

    public function findTorrentDownloadMagnetsTotalByTorrentId(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentDownloadMagnet::class)
                    ->findTorrentDownloadMagnetsTotalByTorrentId($torrentId);
    }

    public function registerTorrentDownloadMagnet(
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