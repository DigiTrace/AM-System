<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625104557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make systemaktion not nullable';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ams_Nutzer CHANGE fullname fullname VARCHAR(180) NOT NULL COLLATE `utf8_bin`');
        $this->addSql('ALTER TABLE ams_Objekt CHANGE systemaktion systemaktion TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ams_Nutzer CHANGE fullname fullname VARCHAR(180) NOT NULL COLLATE `utf8mb3_bin`');
        $this->addSql('ALTER TABLE ams_Objekt CHANGE systemaktion systemaktion TINYINT DEFAULT NULL');
    }
}
