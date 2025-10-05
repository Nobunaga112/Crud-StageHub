<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251005084021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipment ADD equipment_type VARCHAR(255) NOT NULL, ADD rent_date DATE NOT NULL, ADD payment DOUBLE PRECISION NOT NULL, DROP equipment, DROP rent');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipment ADD rent VARCHAR(255) NOT NULL, DROP rent_date, DROP payment, CHANGE equipment_type equipment VARCHAR(255) NOT NULL');
    }
}
