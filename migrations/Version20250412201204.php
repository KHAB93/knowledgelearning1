<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250412201204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user and category tables';
    }

    public function up(Schema $schema): void
    {
        // Création de la table user
        $this->addSql(<<<'SQL'
            CREATE TABLE user (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                roles JSON NOT NULL,
                password VARCHAR(255) NOT NULL,
                 
                UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);


        // Création de la table messenger_messages
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (
                id BIGINT AUTO_INCREMENT NOT NULL,
                body LONGTEXT NOT NULL,
                headers LONGTEXT NOT NULL,
                queue_name VARCHAR(190) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_75EA56E0FB7336F0 (queue_name),
                INDEX IDX_75EA56E0E3BD61CE (available_at),
                INDEX IDX_75EA56E016BA31DB (delivered_at),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

            }
    public function down(Schema $schema): void
    {
        // Suppression des tables dans l'ordre inverse
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        
    }
}
