<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325094328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE postmeta CHANGE post_id post_id INT DEFAULT NULL');
        $this->addSql('UPDATE postmeta SET post_id = NULL WHERE post_id = 0');
        $this->addSql('ALTER TABLE postmeta CHANGE post_id post_id INT NOT NULL');
        $this->addSql('ALTER TABLE postmeta ADD CONSTRAINT FK_C3DE80BC4B89032C FOREIGN KEY (post_id) REFERENCES posts (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postmeta DROP FOREIGN KEY FK_C3DE80BC4B89032C');
        $this->addSql('DROP INDEX IDX_C3DE80BC4B89032C ON postmeta');
    }
}
