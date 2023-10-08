<?php

namespace App\Service;

use App\Entity\Torrent;
use App\Entity\TorrentLocales;
use App\Entity\TorrentSensitive;
use App\Entity\TorrentBookmark;

use App\Repository\TorrentRepository;
use App\Repository\TorrentLocalesRepository;
use App\Repository\TorrentSensitiveRepository;
use App\Repository\TorrentBookmarkRepository;

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

    // Getters
    public function getTorrent(int $id): ?Torrent
    {
        return $this->entityManagerInterface
                    ->getRepository(Torrent::class)
                    ->findOneByIdField($id);
    }

    /// Locales
    public function getTorrentLocales(int $id): ?TorrentLocales
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->getTorrentLocales($id);
    }

    public function findLastTorrentLocales(int $torrentId): ?TorrentLocales
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->findLastTorrentLocales($torrentId);
    }

    public function findTorrentLocales(int $torrentId): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentLocales::class)
                    ->findTorrentLocales($torrentId);
    }

    /// Sensitive
    public function getTorrentSensitive(int $id): ?TorrentSensitive
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->getTorrentSensitive($id);
    }

    public function findLastTorrentSensitive(int $torrentId): ?TorrentSensitive
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->findLastTorrentSensitive($torrentId);
    }

    public function findTorrentSensitive(int $torrentId): array
    {
        return $this->entityManagerInterface
                    ->getRepository(TorrentSensitive::class)
                    ->findTorrentSensitive($torrentId);
    }

    /// Bookmark
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

    // Update
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

    // Delete
    public function deleteTorrentLocales(
        int $id
    ): ?TorrentLocales
    {
        $torrentLocales = $this->getTorrentLocales($id);

        $this->entityManagerInterface->remove($torrentLocales);
        $this->entityManagerInterface->flush();

        return $torrentLocales;
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

    public function deleteTorrentSensitive(
        int $id
    ): ?TorrentSensitive
    {
        $torrentSensitive = $this->getTorrentSensitive($id);

        $this->entityManagerInterface->remove($torrentSensitive);
        $this->entityManagerInterface->flush();

        return $torrentSensitive;
    }

    // Setters
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
}