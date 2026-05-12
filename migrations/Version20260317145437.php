<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317145437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tabs ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tabs ADD CONSTRAINT FK_12CB606312469DE2 FOREIGN KEY (category_id) REFERENCES categories (id)');
        $this->addSql('CREATE INDEX IDX_12CB606312469DE2 ON tabs (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tabs DROP FOREIGN KEY FK_12CB606312469DE2');
        $this->addSql('DROP INDEX IDX_12CB606312469DE2 ON tabs');
        $this->addSql('ALTER TABLE tabs DROP category_id');
    }
}
