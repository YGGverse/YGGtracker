<?php

namespace App\Service;

use App\Entity\Torrent;
use App\Entity\TorrentLocales;
use App\Entity\TorrentSensitive;

use App\Repository\TorrentRepository;
use App\Repository\TorrentLocalesRepository;
use App\Repository\TorrentSensitiveRepository;

use Doctrine\ORM\EntityManagerInterface;

class TorrentService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBagInterface;

    public function __construct(
        EntityManagerInterface $entityManager,
    )
    {
        $this->entityManager = $entityManager;
    }

    public function decodeTorrentByFilepath(string $filepath): array
    {
        $decoder = new \BitTorrent\Decoder();

        return $decoder->decodeFile($filepath);
    }

    public function getTorrentFilenameByFilepath(string $filepath): string
    {
        $data = $this->decodeTorrentByFilepath($filepath);

        if (!empty($data['info']['name']))
        {
            return $data['info']['name'];
        }

        return $data['info']['name'];
    }

    public function getTorrentKeywordsByFilepath(string $filepath): string
    {
        $data = $this->decodeTorrentByFilepath($filepath);

        if (!empty($data['info']['name']))
        {
            return mb_strtolower(
                preg_replace(
                    '/[\s]+/',
                    ' ',
                    preg_replace(
                        '/[\W]+/',
                        ' ',
                        $data['info']['name']
                    )
                )
            );
        }

        return '';
    }

    public function submit(
        string $filepath,
        int $userId,
        int $added,
        array $locales,
        bool $sensitive,
        bool $approved
    ): ?Torrent
    {
        $torrent = $this->saveTorrent(
          $this->getTorrentFilenameByFilepath($filepath),
          $this->getTorrentKeywordsByFilepath($filepath)
        );

        if (!empty($locales))
        {
            $this->saveTorrentLocales(
                $torrent->getId(),
                $userId,
                $added,
                $locales,
                $approved
            );
        }

        $this->saveTorrentSensitive(
            $torrent->getId(),
            $userId,
            $added,
            $sensitive,
            $approved
        );

        return $torrent;
    }

    public function saveTorrent(
      string $filepath,
      string $keywords
    ): ?Torrent
    {
        $torrent = new Torrent();

        $torrent->setFilename($filepath);
        $torrent->setKeywords($keywords);

        $this->entityManager->persist($torrent);
        $this->entityManager->flush();

        return $torrent;
    }

    public function saveTorrentLocales(
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

        $this->entityManager->persist($torrentLocales);
        $this->entityManager->flush();

        return $torrentLocales;
    }

    public function saveTorrentSensitive(
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

        $this->entityManager->persist($torrentSensitive);
        $this->entityManager->flush();

        return $torrentSensitive;
    }
}