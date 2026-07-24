<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260723091005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Créer la nouvelle table de jointure
        $this->addSql('CREATE TABLE posts_tabs (posts_id INT NOT NULL, tabs_id INT NOT NULL, INDEX IDX_A89B59DAD5E258C5 (posts_id), INDEX IDX_A89B59DA459BC0C4 (tabs_id), PRIMARY KEY(posts_id, tabs_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE posts_tabs ADD CONSTRAINT FK_A89B59DAD5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_tabs ADD CONSTRAINT FK_A89B59DA459BC0C4 FOREIGN KEY (tabs_id) REFERENCES tabs (id) ON DELETE CASCADE');
        
        // 2. MIGRER LES DONNÉES existantes avant de supprimer tab_id
        $this->addSql('INSERT INTO posts_tabs (posts_id, tabs_id) SELECT id, tab_id FROM posts WHERE tab_id IS NOT NULL');
        
        // 3. Supprimer les anciennes structures
        $this->addSql('ALTER TABLE tabs DROP FOREIGN KEY FK_12CB6063401ADD27');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA8D0C9323');
        $this->addSql('DROP INDEX IDX_885DBAFA8D0C9323 ON posts');
        $this->addSql('DROP INDEX IDX_12CB6063401ADD27 ON tabs');
        
        // 4. Supprimer les colonnes obsolètes
        $this->addSql('ALTER TABLE posts DROP tab_id, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE tabs DROP pages_id');
        
        // 5. Supprimer la table pages (VERIFIE QU'ELLE EST INUTILE)
        $this->addSql('DROP TABLE pages');
    }

    public function down(Schema $schema): void
    {
        // Recréer la table pages (structure simple, à adapter si besoin)
        $this->addSql('CREATE TABLE pages (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Recréer la colonne pages_id sur tabs
        $this->addSql('ALTER TABLE tabs ADD pages_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tabs ADD CONSTRAINT FK_12CB6063401ADD27 FOREIGN KEY (pages_id) REFERENCES pages (id)');
        $this->addSql('CREATE INDEX IDX_12CB6063401ADD27 ON tabs (pages_id)');
        
        // Recréer la colonne tab_id sur posts
        $this->addSql('ALTER TABLE posts ADD tab_id INT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA8D0C9323 FOREIGN KEY (tab_id) REFERENCES tabs (id)');
        $this->addSql('CREATE INDEX IDX_885DBAFA8D0C9323 ON posts (tab_id)');
        
        // Restaurer les données (premier tab par post)
        $this->addSql('UPDATE posts p SET tab_id = (SELECT tabs_id FROM posts_tabs pt WHERE pt.posts_id = p.id LIMIT 1)');
        
        // Supprimer la table de jointure
        $this->addSql('DROP TABLE posts_tabs');
    }
}
