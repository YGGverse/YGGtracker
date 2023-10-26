<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231026163131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE torrent ADD COLUMN status BOOLEAN DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__torrent AS SELECT id, user_id, added, scraped, locales, sensitive, approved, md5file, keywords, seeders, peers, leechers FROM torrent');
        $this->addSql('DROP TABLE torrent');
        $this->addSql('CREATE TABLE torrent (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, added INTEGER NOT NULL, scraped INTEGER DEFAULT NULL, locales CLOB NOT NULL --(DC2Type:simple_array)
        , sensitive BOOLEAN NOT NULL, approved BOOLEAN NOT NULL, md5file VARCHAR(32) NOT NULL, keywords CLOB DEFAULT NULL --(DC2Type:simple_array)
        , seeders INTEGER DEFAULT NULL, peers INTEGER DEFAULT NULL, leechers INTEGER DEFAULT NULL)');
        $this->addSql('INSERT INTO torrent (id, user_id, added, scraped, locales, sensitive, approved, md5file, keywords, seeders, peers, leechers) SELECT id, user_id, added, scraped, locales, sensitive, approved, md5file, keywords, seeders, peers, leechers FROM __temp__torrent');
        $this->addSql('DROP TABLE __temp__torrent');
    }
}
