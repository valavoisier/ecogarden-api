<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260313215032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;

        // 1. Récupérer tous les conseils avec leur JSON
        $conseils = $conn->fetchAllAssociative('SELECT id, mois FROM conseil');

        foreach ($conseils as $conseil) {
            if (!$conseil['mois']) {
                continue;
            }

            $moisArray = json_decode($conseil['mois'], true);

            foreach ($moisArray as $numero) {
                // 2. Récupérer l'id du mois correspondant
                $moisId = $conn->fetchOne(
                    'SELECT id FROM mois WHERE numero = ?',
                    [$numero]
                );

                if ($moisId) {
                    // 3. Insérer dans la table pivot
                    $conn->insert('conseil_mois', [
                        'conseil_id' => $conseil['id'],
                        'mois_id' => $moisId,
                    ]);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conseil ADD mois JSON NOT NULL');
    }
}
