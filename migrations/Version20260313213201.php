<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313213201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conseil_mois (conseil_id INT NOT NULL, mois_id INT NOT NULL, INDEX IDX_5591B2C3668A3E03 (conseil_id), INDEX IDX_5591B2C3FA0749B8 (mois_id), PRIMARY KEY (conseil_id, mois_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE conseil_mois ADD CONSTRAINT FK_5591B2C3668A3E03 FOREIGN KEY (conseil_id) REFERENCES conseil (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE conseil_mois ADD CONSTRAINT FK_5591B2C3FA0749B8 FOREIGN KEY (mois_id) REFERENCES mois (id) ON DELETE CASCADE');
        //$this->addSql('ALTER TABLE conseil DROP mois');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conseil_mois DROP FOREIGN KEY FK_5591B2C3668A3E03');
        $this->addSql('ALTER TABLE conseil_mois DROP FOREIGN KEY FK_5591B2C3FA0749B8');
        $this->addSql('DROP TABLE conseil_mois');
        $this->addSql('ALTER TABLE conseil ADD mois JSON NOT NULL');
    }
}
