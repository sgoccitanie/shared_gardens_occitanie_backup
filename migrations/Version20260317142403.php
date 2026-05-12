<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317142403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pages ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pages ADD CONSTRAINT FK_2074E57512469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('CREATE INDEX IDX_2074E57512469DE2 ON pages (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pages DROP FOREIGN KEY FK_2074E57512469DE2');
        $this->addSql('DROP INDEX IDX_2074E57512469DE2 ON pages');
        $this->addSql('ALTER TABLE pages DROP category_id');
    }
}
