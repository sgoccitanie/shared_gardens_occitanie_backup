<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723094035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories_posts (categories_id INT NOT NULL, posts_id INT NOT NULL, INDEX IDX_8C5EAFB7A21214B7 (categories_id), INDEX IDX_8C5EAFB7D5E258C5 (posts_id), PRIMARY KEY(categories_id, posts_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tabs_categories (tabs_id INT NOT NULL, categories_id INT NOT NULL, INDEX IDX_4565E99A459BC0C4 (tabs_id), INDEX IDX_4565E99AA21214B7 (categories_id), PRIMARY KEY(tabs_id, categories_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categories_posts ADD CONSTRAINT FK_8C5EAFB7A21214B7 FOREIGN KEY (categories_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories_posts ADD CONSTRAINT FK_8C5EAFB7D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tabs_categories ADD CONSTRAINT FK_4565E99A459BC0C4 FOREIGN KEY (tabs_id) REFERENCES tabs (id)');
        $this->addSql('ALTER TABLE tabs_categories ADD CONSTRAINT FK_4565E99AA21214B7 FOREIGN KEY (categories_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tabs DROP FOREIGN KEY FK_12CB606312469DE2');
        $this->addSql('DROP INDEX IDX_12CB606312469DE2 ON tabs');
        $this->addSql('ALTER TABLE tabs DROP category_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categories_posts DROP FOREIGN KEY FK_8C5EAFB7A21214B7');
        $this->addSql('ALTER TABLE categories_posts DROP FOREIGN KEY FK_8C5EAFB7D5E258C5');
        $this->addSql('ALTER TABLE tabs_categories DROP FOREIGN KEY FK_4565E99A459BC0C4');
        $this->addSql('ALTER TABLE tabs_categories DROP FOREIGN KEY FK_4565E99AA21214B7');
        $this->addSql('DROP TABLE categories_posts');
        $this->addSql('DROP TABLE tabs_categories');
        $this->addSql('ALTER TABLE tabs ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tabs ADD CONSTRAINT FK_12CB606312469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_12CB606312469DE2 ON tabs (category_id)');
    }
}
