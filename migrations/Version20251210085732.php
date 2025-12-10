<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251210085732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDEB03A8386 ON booking (created_by_id)');
        $this->addSql('ALTER TABLE payment ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DB03A8386 ON payment (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEB03A8386');
        $this->addSql('DROP INDEX IDX_E00CEDDEB03A8386 ON booking');
        $this->addSql('ALTER TABLE booking DROP created_by_id');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DB03A8386');
        $this->addSql('DROP INDEX IDX_6D28840DB03A8386 ON payment');
        $this->addSql('ALTER TABLE payment DROP created_by_id');
    }
}
