<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261115130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pages : option « contenu propre » (tabs.own_content)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tabs ADD own_content TINYINT(1) DEFAULT 0 NOT NULL');
        // Les pages qui ont déjà un contenu restent en mode « contenu propre »
        $this->addSql("UPDATE tabs SET own_content = 1 WHERE content IS NOT NULL AND TRIM(content) <> ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tabs DROP own_content');
    }
}
