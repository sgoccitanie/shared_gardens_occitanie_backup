<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260714121700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses CHANGE city_id city_id INT DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL');
        //$this->addSql('CREATE UNIQUE INDEX UNIQ_D95DB16B65DA68E7 ON cities (postalcode)');
        $this->addSql('ALTER TABLE user CHANGE login login VARCHAR(30) NOT NULL, CHANGE firstname firstname VARCHAR(50) NOT NULL, CHANGE lastname lastname VARCHAR(50) NOT NULL');
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6495F37A13B ON user (token)');
        // $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        // $this->addSql('DROP INDEX IDX_75EA56E0E3BD61CE ON messenger_messages');
        // $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0 ON messenger_messages');
        //$this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('ALTER TABLE rememberme_token CHANGE class class VARCHAR(100) DEFAULT \'\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses CHANGE city_id city_id INT NOT NULL, CHANGE longitude longitude NUMERIC(10, 0) DEFAULT NULL, CHANGE latitude latitude NUMERIC(10, 7) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_D95DB16B65DA68E7 ON cities');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('ALTER TABLE rememberme_token CHANGE class class VARCHAR(100) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D6495F37A13B ON user');
        $this->addSql('ALTER TABLE user CHANGE login login VARCHAR(60) NOT NULL, CHANGE firstname firstname VARCHAR(255) NOT NULL, CHANGE lastname lastname VARCHAR(255) NOT NULL');
    }
}
