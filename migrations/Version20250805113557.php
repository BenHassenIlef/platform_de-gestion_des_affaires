<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250805113557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_opportunity (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, contact VARCHAR(255) NOT NULL, value INT NOT NULL, close_date DATE NOT NULL, description LONGTEXT DEFAULT NULL, decision VARCHAR(50) DEFAULT NULL, notified_at DATETIME NOT NULL, original_opportunity_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA9A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunity (id)');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_bf5476ca92f3c70 TO IDX_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE opportunity_file RENAME INDEX idx_opportunity_file_opportunity TO IDX_70DD4F2D9A34590F');
        $this->addSql('ALTER TABLE opportunity_file RENAME INDEX idx_opportunity_file_uploaded_by TO IDX_70DD4F2DA2B28FE8');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE admin_opportunity');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA9A34590F');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_bf5476cae92f8f78 TO IDX_BF5476CA92F3C70');
        $this->addSql('ALTER TABLE opportunity_file RENAME INDEX idx_70dd4f2d9a34590f TO IDX_OPPORTUNITY_FILE_OPPORTUNITY');
        $this->addSql('ALTER TABLE opportunity_file RENAME INDEX idx_70dd4f2da2b28fe8 TO IDX_OPPORTUNITY_FILE_UPLOADED_BY');
    }
}
