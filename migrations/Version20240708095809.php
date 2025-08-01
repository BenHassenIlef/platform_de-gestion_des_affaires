<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240708095809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet ADD avis VARCHAR(50) NOT NULL, ADD cahier_de_charge VARCHAR(255) NOT NULL, ADD motif VARCHAR(255) NOT NULL, DROP cahier');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet ADD cahier JSON NOT NULL COMMENT \'(DC2Type:json)\', DROP avis, DROP cahier_de_charge, DROP motif');
    }
}
