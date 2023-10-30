<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231029184600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE torrent_poster (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, torrent_id INTEGER NOT NULL, user_id INTEGER NOT NULL, added INTEGER NOT NULL, approved BOOLEAN NOT NULL, md5file VARCHAR(32) NOT NULL)');
        $this->addSql('ALTER TABLE user ADD COLUMN posters BOOLEAN NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE torrent ADD COLUMN torrent_poster_id BOOLEAN NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE torrent_poster');
        $this->addSql('ALTER TABLE user DROP COLUMN posters');
        $this->addSql('ALTER TABLE torrent DROP COLUMN torrent_poster_id');
    }
}
