<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723095241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tabs_categories DROP FOREIGN KEY FK_4565E99A459BC0C4');
        $this->addSql('ALTER TABLE tabs_categories ADD CONSTRAINT FK_4565E99A459BC0C4 FOREIGN KEY (tabs_id) REFERENCES tabs (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tabs_categories DROP FOREIGN KEY FK_4565E99A459BC0C4');
        $this->addSql('ALTER TABLE tabs_categories ADD CONSTRAINT FK_4565E99A459BC0C4 FOREIGN KEY (tabs_id) REFERENCES tabs (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
