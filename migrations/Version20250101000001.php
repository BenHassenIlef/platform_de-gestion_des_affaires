<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250101000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create opportunity_file table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE opportunity_file (
            id INT AUTO_INCREMENT NOT NULL,
            opportunity_id INT NOT NULL,
            uploaded_by_id INT DEFAULT NULL,
            filename VARCHAR(255) NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT NOT NULL,
            uploaded_at DATETIME NOT NULL,
            INDEX IDX_OPPORTUNITY_FILE_OPPORTUNITY (opportunity_id),
            INDEX IDX_OPPORTUNITY_FILE_UPLOADED_BY (uploaded_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE opportunity_file ADD CONSTRAINT FK_OPPORTUNITY_FILE_OPPORTUNITY FOREIGN KEY (opportunity_id) REFERENCES opportunity (id)');
        $this->addSql('ALTER TABLE opportunity_file ADD CONSTRAINT FK_OPPORTUNITY_FILE_UPLOADED_BY FOREIGN KEY (uploaded_by_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE opportunity_file DROP FOREIGN KEY FK_OPPORTUNITY_FILE_OPPORTUNITY');
        $this->addSql('ALTER TABLE opportunity_file DROP FOREIGN KEY FK_OPPORTUNITY_FILE_UPLOADED_BY');
        $this->addSql('DROP TABLE opportunity_file');
    }
} 