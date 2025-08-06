<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            recipient_id INT DEFAULT NULL,
            opportunity_id INT DEFAULT NULL,
            message VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            is_read TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_BF5476CA92F3C70 (recipient_id),
            INDEX IDX_BF5476CA9A34590F (opportunity_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA92F3C70 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA9A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunity (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA92F3C70');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA9A34590F');
        $this->addSql('DROP TABLE notification');
    }
} 