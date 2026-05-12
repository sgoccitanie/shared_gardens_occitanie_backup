<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260317164459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories_posts (posts_id INT NOT NULL, categories_id INT NOT NULL, INDEX IDX_8C5EAFB7D5E258C5 (posts_id), INDEX IDX_8C5EAFB7A21214B7 (categories_id), PRIMARY KEY(posts_id, categories_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE categories_posts ADD CONSTRAINT FK_8C5EAFB7D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories_posts ADD CONSTRAINT FK_8C5EAFB7A21214B7 FOREIGN KEY (categories_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_categories DROP FOREIGN KEY FK_A8C3AA46A21214B7');
        $this->addSql('ALTER TABLE posts_categories DROP FOREIGN KEY FK_A8C3AA46D5E258C5');
        $this->addSql('DROP TABLE posts_categories');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE posts_categories (posts_id INT NOT NULL, categories_id INT NOT NULL, INDEX IDX_A8C3AA46A21214B7 (categories_id), INDEX IDX_A8C3AA46D5E258C5 (posts_id), PRIMARY KEY(posts_id, categories_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE posts_categories ADD CONSTRAINT FK_A8C3AA46A21214B7 FOREIGN KEY (categories_id) REFERENCES categories (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_categories ADD CONSTRAINT FK_A8C3AA46D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories_posts DROP FOREIGN KEY FK_8C5EAFB7D5E258C5');
        $this->addSql('ALTER TABLE categories_posts DROP FOREIGN KEY FK_8C5EAFB7A21214B7');
        $this->addSql('DROP TABLE categories_posts');
    }
}
