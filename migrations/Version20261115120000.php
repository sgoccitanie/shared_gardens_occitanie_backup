<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Le contenu des pages (tabs.content) devient facultatif';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tabs CHANGE content content LONGTEXT DEFAULT NULL');
        $this->addSql("UPDATE tabs SET content = NULL WHERE TRIM(content) = ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE tabs SET content = '' WHERE content IS NULL");
        $this->addSql('ALTER TABLE tabs CHANGE content content LONGTEXT NOT NULL');
    }
}
