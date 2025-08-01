<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240712113114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet ADD caution VARCHAR(255) DEFAULT NULL, ADD montant_caution VARCHAR(255) DEFAULT NULL, ADD etat_caution VARCHAR(255) DEFAULT NULL, DROP quotient, DROP montant_quotient, DROP etat_quotient');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet ADD quotient VARCHAR(255) DEFAULT NULL, ADD montant_quotient INT DEFAULT NULL, ADD etat_quotient VARCHAR(255) DEFAULT NULL, DROP caution, DROP montant_caution, DROP etat_caution');
    }
}
