<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231103235504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE torrent_categories (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, torrent_id INTEGER NOT NULL, user_id INTEGER NOT NULL, added INTEGER NOT NULL, value CLOB NOT NULL --(DC2Type:simple_array)
        , approved BOOLEAN NOT NULL)');
        $this->addSql('ALTER TABLE user ADD COLUMN categories CLOB DEFAULT "other"');
        $this->addSql('ALTER TABLE torrent ADD COLUMN categories CLOB DEFAULT "other"');
        $this->addSql('UPDATE user SET categories = "movie,series,tv,animation,music,game,audiobook,podcast,book,archive,picture,software,other"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE torrent_categories');
        $this->addSql('ALTER TABLE user DROP COLUMN categories');
        $this->addSql('ALTER TABLE torrent DROP COLUMN categories');
    }
}
