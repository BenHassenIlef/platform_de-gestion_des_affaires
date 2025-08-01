<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240712081603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet ADD offre_technique VARCHAR(255) DEFAULT NULL, ADD offre_administrative VARCHAR(255) DEFAULT NULL, ADD partie_financiere VARCHAR(255) DEFAULT NULL, ADD quotient VARCHAR(255) DEFAULT NULL, ADD montant_quotient INT DEFAULT NULL, ADD etat_quotient VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet DROP offre_technique, DROP offre_administrative, DROP partie_financiere, DROP quotient, DROP montant_quotient, DROP etat_quotient');
    }
}
