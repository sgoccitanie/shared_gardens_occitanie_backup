<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260121100053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE addresses (id INT AUTO_INCREMENT NOT NULL, city_id INT NOT NULL, street VARCHAR(255) NOT NULL, longitude NUMERIC(10, 0) DEFAULT NULL, latitude NUMERIC(10, 7) DEFAULT NULL, INDEX IDX_6FCA75168BAC62AF (city_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE association (id INT AUTO_INCREMENT NOT NULL, address_id INT DEFAULT NULL, founded_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', mantra VARCHAR(100) NOT NULL, banner VARCHAR(255) NOT NULL, is_lucrative TINYINT(1) NOT NULL, name VARCHAR(255) NOT NULL, acronyme VARCHAR(30) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(100) DEFAULT NULL, mobile VARCHAR(20) NOT NULL, email VARCHAR(255) DEFAULT NULL, update_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_FD8521CCF5B7AF75 (address_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categories_posts (categories_id INT NOT NULL, posts_id INT NOT NULL, INDEX IDX_8C5EAFB7A21214B7 (categories_id), INDEX IDX_8C5EAFB7D5E258C5 (posts_id), PRIMARY KEY(categories_id, posts_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cities (id INT AUTO_INCREMENT NOT NULL, country_id INT NOT NULL, name VARCHAR(255) NOT NULL, postalcode VARCHAR(10) NOT NULL, area_name VARCHAR(255) NOT NULL, dpt_name VARCHAR(255) NOT NULL, INDEX IDX_D95DB16BF92F3E70 (country_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE countries (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE files (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE keywords (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE links (id INT AUTO_INCREMENT NOT NULL, association_id INT DEFAULT NULL, name VARCHAR(100) NOT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_D182A118EFB9C8A5 (association_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_access_token (identifier CHAR(80) NOT NULL, client VARCHAR(32) NOT NULL, expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_identifier VARCHAR(128) DEFAULT NULL, scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\', revoked TINYINT(1) NOT NULL, INDEX IDX_454D9673C7440455 (client), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_authorization_code (identifier CHAR(80) NOT NULL, client VARCHAR(32) NOT NULL, expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', user_identifier VARCHAR(128) DEFAULT NULL, scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\', revoked TINYINT(1) NOT NULL, INDEX IDX_509FEF5FC7440455 (client), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_client (identifier VARCHAR(32) NOT NULL, name VARCHAR(128) NOT NULL, secret VARCHAR(128) DEFAULT NULL, redirect_uris TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_redirect_uri)\', grants TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_grant)\', scopes TEXT DEFAULT NULL COMMENT \'(DC2Type:oauth2_scope)\', active TINYINT(1) NOT NULL, allow_plain_text_pkce TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_refresh_token (identifier CHAR(80) NOT NULL, access_token CHAR(80) DEFAULT NULL, expiry DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', revoked TINYINT(1) NOT NULL, INDEX IDX_4DD90732B6A2DD68 (access_token), PRIMARY KEY(identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pages (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(50) NOT NULL, home TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE postmeta (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE postmeta_posts (postmeta_id INT NOT NULL, posts_id INT NOT NULL, INDEX IDX_8BE9D349F0EDA51F (postmeta_id), INDEX IDX_8BE9D349D5E258C5 (posts_id), PRIMARY KEY(postmeta_id, posts_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts (id INT AUTO_INCREMENT NOT NULL, tab_id INT DEFAULT NULL, user_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, slug VARCHAR(100) NOT NULL, posted_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', likes_counter INT DEFAULT NULL, status TINYINT(1) NOT NULL, comment_counter INT DEFAULT NULL, path VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_885DBAFA989D9B62 (slug), INDEX IDX_885DBAFA8D0C9323 (tab_id), INDEX IDX_885DBAFAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts_keywords (posts_id INT NOT NULL, keywords_id INT NOT NULL, INDEX IDX_70906D97D5E258C5 (posts_id), INDEX IDX_70906D976205D0B8 (keywords_id), PRIMARY KEY(posts_id, keywords_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE posts_files (posts_id INT NOT NULL, files_id INT NOT NULL, INDEX IDX_B4321B48D5E258C5 (posts_id), INDEX IDX_B4321B48A3E65B2F (files_id), PRIMARY KEY(posts_id, files_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE resources (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, author VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', isbn VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_EF66EBAEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subject_email (id INT AUTO_INCREMENT NOT NULL, association_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_F601BDACEFB9C8A5 (association_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tabs (id INT AUTO_INCREMENT NOT NULL, pages_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, news_feed TINYINT(1) NOT NULL, slug VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_12CB6063989D9B62 (slug), INDEX IDX_12CB6063401ADD27 (pages_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, user_asso_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, login VARCHAR(60) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, mobile VARCHAR(50) DEFAULT NULL, img VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_connection DATETIME DEFAULT NULL, is_verified TINYINT(1) NOT NULL, token VARCHAR(255) DEFAULT NULL, token_expirate_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_8D93D64921219822 (user_asso_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE videos (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', url VARCHAR(255) NOT NULL, origin VARCHAR(150) DEFAULT NULL, INDEX IDX_29AA6432A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rememberme_token (series VARCHAR(88) NOT NULL, value VARCHAR(88) NOT NULL, lastUsed DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', class VARCHAR(100) NOT NULL, username VARCHAR(200) NOT NULL, PRIMARY KEY(series)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE addresses ADD CONSTRAINT FK_6FCA75168BAC62AF FOREIGN KEY (city_id) REFERENCES cities (id)');
        $this->addSql('ALTER TABLE association ADD CONSTRAINT FK_FD8521CCF5B7AF75 FOREIGN KEY (address_id) REFERENCES addresses (id)');
        $this->addSql('ALTER TABLE categories_posts ADD CONSTRAINT FK_8C5EAFB7A21214B7 FOREIGN KEY (categories_id) REFERENCES categories (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories_posts ADD CONSTRAINT FK_8C5EAFB7D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cities ADD CONSTRAINT FK_D95DB16BF92F3E70 FOREIGN KEY (country_id) REFERENCES countries (id)');
        $this->addSql('ALTER TABLE links ADD CONSTRAINT FK_D182A118EFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id)');
        $this->addSql('ALTER TABLE oauth2_access_token ADD CONSTRAINT FK_454D9673C7440455 FOREIGN KEY (client) REFERENCES oauth2_client (identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oauth2_authorization_code ADD CONSTRAINT FK_509FEF5FC7440455 FOREIGN KEY (client) REFERENCES oauth2_client (identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oauth2_refresh_token ADD CONSTRAINT FK_4DD90732B6A2DD68 FOREIGN KEY (access_token) REFERENCES oauth2_access_token (identifier) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE postmeta_posts ADD CONSTRAINT FK_8BE9D349F0EDA51F FOREIGN KEY (postmeta_id) REFERENCES postmeta (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE postmeta_posts ADD CONSTRAINT FK_8BE9D349D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFA8D0C9323 FOREIGN KEY (tab_id) REFERENCES tabs (id)');
        $this->addSql('ALTER TABLE posts ADD CONSTRAINT FK_885DBAFAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE posts_keywords ADD CONSTRAINT FK_70906D97D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_keywords ADD CONSTRAINT FK_70906D976205D0B8 FOREIGN KEY (keywords_id) REFERENCES keywords (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_files ADD CONSTRAINT FK_B4321B48D5E258C5 FOREIGN KEY (posts_id) REFERENCES posts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE posts_files ADD CONSTRAINT FK_B4321B48A3E65B2F FOREIGN KEY (files_id) REFERENCES files (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_EF66EBAEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE subject_email ADD CONSTRAINT FK_F601BDACEFB9C8A5 FOREIGN KEY (association_id) REFERENCES association (id)');
        $this->addSql('ALTER TABLE tabs ADD CONSTRAINT FK_12CB6063401ADD27 FOREIGN KEY (pages_id) REFERENCES pages (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64921219822 FOREIGN KEY (user_asso_id) REFERENCES association (id)');
        $this->addSql('ALTER TABLE videos ADD CONSTRAINT FK_29AA6432A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE addresses DROP FOREIGN KEY FK_6FCA75168BAC62AF');
        $this->addSql('ALTER TABLE association DROP FOREIGN KEY FK_FD8521CCF5B7AF75');
        $this->addSql('ALTER TABLE categories_posts DROP FOREIGN KEY FK_8C5EAFB7A21214B7');
        $this->addSql('ALTER TABLE categories_posts DROP FOREIGN KEY FK_8C5EAFB7D5E258C5');
        $this->addSql('ALTER TABLE cities DROP FOREIGN KEY FK_D95DB16BF92F3E70');
        $this->addSql('ALTER TABLE links DROP FOREIGN KEY FK_D182A118EFB9C8A5');
        $this->addSql('ALTER TABLE oauth2_access_token DROP FOREIGN KEY FK_454D9673C7440455');
        $this->addSql('ALTER TABLE oauth2_authorization_code DROP FOREIGN KEY FK_509FEF5FC7440455');
        $this->addSql('ALTER TABLE oauth2_refresh_token DROP FOREIGN KEY FK_4DD90732B6A2DD68');
        $this->addSql('ALTER TABLE postmeta_posts DROP FOREIGN KEY FK_8BE9D349F0EDA51F');
        $this->addSql('ALTER TABLE postmeta_posts DROP FOREIGN KEY FK_8BE9D349D5E258C5');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFA8D0C9323');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_885DBAFAA76ED395');
        $this->addSql('ALTER TABLE posts_keywords DROP FOREIGN KEY FK_70906D97D5E258C5');
        $this->addSql('ALTER TABLE posts_keywords DROP FOREIGN KEY FK_70906D976205D0B8');
        $this->addSql('ALTER TABLE posts_files DROP FOREIGN KEY FK_B4321B48D5E258C5');
        $this->addSql('ALTER TABLE posts_files DROP FOREIGN KEY FK_B4321B48A3E65B2F');
        $this->addSql('ALTER TABLE resources DROP FOREIGN KEY FK_EF66EBAEA76ED395');
        $this->addSql('ALTER TABLE subject_email DROP FOREIGN KEY FK_F601BDACEFB9C8A5');
        $this->addSql('ALTER TABLE tabs DROP FOREIGN KEY FK_12CB6063401ADD27');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64921219822');
        $this->addSql('ALTER TABLE videos DROP FOREIGN KEY FK_29AA6432A76ED395');
        $this->addSql('DROP TABLE addresses');
        $this->addSql('DROP TABLE association');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE categories_posts');
        $this->addSql('DROP TABLE cities');
        $this->addSql('DROP TABLE countries');
        $this->addSql('DROP TABLE files');
        $this->addSql('DROP TABLE keywords');
        $this->addSql('DROP TABLE links');
        $this->addSql('DROP TABLE oauth2_access_token');
        $this->addSql('DROP TABLE oauth2_authorization_code');
        $this->addSql('DROP TABLE oauth2_client');
        $this->addSql('DROP TABLE oauth2_refresh_token');
        $this->addSql('DROP TABLE pages');
        $this->addSql('DROP TABLE postmeta');
        $this->addSql('DROP TABLE postmeta_posts');
        $this->addSql('DROP TABLE posts');
        $this->addSql('DROP TABLE posts_keywords');
        $this->addSql('DROP TABLE posts_files');
        $this->addSql('DROP TABLE resources');
        $this->addSql('DROP TABLE subject_email');
        $this->addSql('DROP TABLE tabs');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE videos');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE rememberme_token');
    }
}
