<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260325103009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE postmeta_posts DROP FOREIGN KEY FK_8BE9D349D5E258C5');
        $this->addSql('ALTER TABLE postmeta_posts DROP FOREIGN KEY FK_8BE9D349F0EDA51F');
        $this->addSql('DROP TABLE postmeta_posts');
        $this->addSql('ALTER TABLE postmeta CHANGE original_name original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE postmeta RENAME INDEX fk_c3de80bc4b89032c TO IDX_C3DE80BC4B89032C');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE postmeta_posts (postmeta_id INT NOT NULL, posts_id INT NOT NULL, INDEX IDX_8BE9D349F0EDA51F (postmeta_id), INDEX IDX_8BE9D349D5E258C5 (posts_id), PRIMARY KEY(postmeta_id, posts_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE postmeta_posts ADD CONSTRAINT FK_8BE9D349D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE postmeta_posts ADD CONSTRAINT FK_8BE9D349F0EDA51F FOREIGN KEY (postmeta_id) REFERENCES postmeta (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE postmeta CHANGE original_name original_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE postmeta RENAME INDEX idx_c3de80bc4b89032c TO FK_C3DE80BC4B89032C');
    }
}
