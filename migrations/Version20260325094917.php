<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325094917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration vide (tout est déjà en place)';
    }

    public function up(Schema $schema): void
    {
        // Tout est déjà en place, rien à faire
        $this->addSql('SELECT 1');
    }

    public function down(Schema $schema): void
    {
        // Rien à annuler
        $this->addSql('SELECT 1');
    }
}
