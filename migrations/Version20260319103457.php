<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260319103457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inital migration for production database.';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Datentraeger ADD CONSTRAINT FK_EE20D1E829439E58 FOREIGN KEY (barcode_id) REFERENCES ams_Objekt (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Fall CHANGE ist_aktiv ist_aktiv TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_DED1DA32CF10D4F5 ON ams_Fall (case_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Historie_Objekt CHANGE status_id status_id INT NOT NULL, CHANGE systemaktion systemaktion TINYINT(1) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Historie_Objekt ADD CONSTRAINT FK_5ECC311F29439E58 FOREIGN KEY (barcode_id) REFERENCES ams_Objekt (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5ECC311F29439E58 ON ams_Historie_Objekt (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Historie_Objekt RENAME INDEX fk_5ecc311f290b48b TO IDX_5ECC311F290B48B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_image_objekt RENAME INDEX idx_c9eaad35661e9d88 TO IDX_C9EAAD3529439E58
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Nutzer CHANGE fullname fullname VARCHAR(180) NOT NULL COLLATE `utf8_bin`, CHANGE roles roles JSON NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Objekt CHANGE kategorie_id kategorie_id INT NOT NULL, CHANGE status_id status_id INT NOT NULL, CHANGE systemaktion systemaktion TINYINT(1) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Objekt RENAME INDEX fk_51dbeb97290b48b TO IDX_51DBEB97290B48B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_ZuordnungImageToHDD RENAME INDEX idx_859fda9cc243a19e TO IDX_27BBA113C53D045F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_ZuordnungImageToHDD RENAME INDEX idx_859fda9cf2cb4868 TO IDX_27BBA113F2CB4868
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Datentraeger DROP FOREIGN KEY FK_EE20D1E829439E58
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_image_objekt RENAME INDEX idx_c9eaad3529439e58 TO IDX_C9EAAD35661E9D88
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_ZuordnungImageToHDD RENAME INDEX idx_27bba113f2cb4868 TO IDX_859FDA9CF2CB4868
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_ZuordnungImageToHDD RENAME INDEX idx_27bba113c53d045f TO IDX_859FDA9CC243A19E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Objekt CHANGE kategorie_id kategorie_id SMALLINT NOT NULL, CHANGE status_id status_id SMALLINT NOT NULL, CHANGE systemaktion systemaktion TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Objekt RENAME INDEX idx_51dbeb97290b48b TO FK_51DBEB97290B48B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Nutzer CHANGE fullname fullname VARCHAR(180) NOT NULL, CHANGE roles roles JSON NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Historie_Objekt DROP FOREIGN KEY FK_5ECC311F29439E58
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_5ECC311F29439E58 ON ams_Historie_Objekt
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Historie_Objekt CHANGE status_id status_id SMALLINT NOT NULL, CHANGE systemaktion systemaktion TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Historie_Objekt RENAME INDEX idx_5ecc311f290b48b TO FK_5ECC311F290B48B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_DED1DA32CF10D4F5 ON ams_Fall
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE ams_Fall CHANGE ist_aktiv ist_aktiv TINYINT(1) DEFAULT 1 NOT NULL
        SQL);
    }
}
