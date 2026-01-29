<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251219150950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE asset (barcode_id VARCHAR(9) NOT NULL, nutzer_id INT NOT NULL, reserviert_von INT DEFAULT NULL, fall_id INT DEFAULT NULL, standort VARCHAR(9) DEFAULT NULL, name LONGTEXT NOT NULL, kategorie_id INT NOT NULL, status_id INT NOT NULL, systemaktion TINYINT(1) DEFAULT NULL, verwendung LONGTEXT DEFAULT NULL, notiz LONGTEXT DEFAULT NULL, storage_override TINYINT(1) DEFAULT NULL, zeitstempel DATETIME NOT NULL, zeitstempelderumsetzung DATETIME NOT NULL, INDEX IDX_2AF5A5C2D6287FB (nutzer_id), INDEX IDX_2AF5A5C1DDBE3D0 (reserviert_von), INDEX IDX_2AF5A5C290B48B (fall_id), INDEX IDX_2AF5A5C7DEEAE9 (standort), PRIMARY KEY(barcode_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE asset_asset (image VARCHAR(9) NOT NULL, hdd VARCHAR(9) NOT NULL, INDEX IDX_97F4741BC53D045F (image), INDEX IDX_97F4741BF2CB4868 (hdd), PRIMARY KEY(image, hdd)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE asset_blob (barcode_id VARCHAR(9) NOT NULL, bild LONGTEXT DEFAULT NULL, bild_pfad VARCHAR(255) DEFAULT NULL, PRIMARY KEY(barcode_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE asset_history (historie_id INT AUTO_INCREMENT NOT NULL, barcode_id VARCHAR(9) NOT NULL, nutzer_id INT NOT NULL, reserviert_von INT DEFAULT NULL, fall_id INT DEFAULT NULL, standort VARCHAR(9) DEFAULT NULL, status_id INT NOT NULL, systemaktion TINYINT(1) DEFAULT NULL, verwendung LONGTEXT DEFAULT NULL, zeitstempel DATETIME NOT NULL, zeitstempelderumsetzung DATETIME NOT NULL, INDEX IDX_4454311D29439E58 (barcode_id), INDEX IDX_4454311D2D6287FB (nutzer_id), INDEX IDX_4454311D1DDBE3D0 (reserviert_von), INDEX IDX_4454311D290B48B (fall_id), INDEX IDX_4454311D7DEEAE9 (standort), PRIMARY KEY(historie_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE asset_history_asset (historie_id INT NOT NULL, barcode_id VARCHAR(9) NOT NULL, INDEX IDX_98628B5A779817B8 (historie_id), INDEX IDX_98628B5A29439E58 (barcode_id), PRIMARY KEY(historie_id, barcode_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE drive (barcode_id VARCHAR(9) NOT NULL, formfaktor LONGTEXT DEFAULT NULL, bauart LONGTEXT DEFAULT NULL, groesse INT DEFAULT NULL, hersteller LONGTEXT DEFAULT NULL, modell LONGTEXT DEFAULT NULL, sn LONGTEXT DEFAULT NULL, pd LONGTEXT DEFAULT NULL, anschluss LONGTEXT DEFAULT NULL, PRIMARY KEY(barcode_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C2D6287FB FOREIGN KEY (nutzer_id) REFERENCES ams_Nutzer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C1DDBE3D0 FOREIGN KEY (reserviert_von) REFERENCES ams_Nutzer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C290B48B FOREIGN KEY (fall_id) REFERENCES ams_Fall (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C7DEEAE9 FOREIGN KEY (standort) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_asset ADD CONSTRAINT FK_97F4741BC53D045F FOREIGN KEY (image) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_asset ADD CONSTRAINT FK_97F4741BF2CB4868 FOREIGN KEY (hdd) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_blob ADD CONSTRAINT FK_541B600429439E58 FOREIGN KEY (barcode_id) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history ADD CONSTRAINT FK_4454311D29439E58 FOREIGN KEY (barcode_id) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history ADD CONSTRAINT FK_4454311D2D6287FB FOREIGN KEY (nutzer_id) REFERENCES ams_Nutzer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history ADD CONSTRAINT FK_4454311D1DDBE3D0 FOREIGN KEY (reserviert_von) REFERENCES ams_Nutzer (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history ADD CONSTRAINT FK_4454311D290B48B FOREIGN KEY (fall_id) REFERENCES ams_Fall (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history ADD CONSTRAINT FK_4454311D7DEEAE9 FOREIGN KEY (standort) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history_asset ADD CONSTRAINT FK_98628B5A779817B8 FOREIGN KEY (historie_id) REFERENCES asset_history (historie_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history_asset ADD CONSTRAINT FK_98628B5A29439E58 FOREIGN KEY (barcode_id) REFERENCES asset (barcode_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE drive ADD CONSTRAINT FK_681DF58F29439E58 FOREIGN KEY (barcode_id) REFERENCES asset (barcode_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C2D6287FB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C1DDBE3D0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C290B48B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C7DEEAE9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_asset DROP FOREIGN KEY FK_97F4741BC53D045F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_asset DROP FOREIGN KEY FK_97F4741BF2CB4868
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_blob DROP FOREIGN KEY FK_541B600429439E58
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history DROP FOREIGN KEY FK_4454311D29439E58
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history DROP FOREIGN KEY FK_4454311D2D6287FB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history DROP FOREIGN KEY FK_4454311D1DDBE3D0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history DROP FOREIGN KEY FK_4454311D290B48B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history DROP FOREIGN KEY FK_4454311D7DEEAE9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history_asset DROP FOREIGN KEY FK_98628B5A779817B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE asset_history_asset DROP FOREIGN KEY FK_98628B5A29439E58
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE drive DROP FOREIGN KEY FK_681DF58F29439E58
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset_asset
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset_blob
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset_history
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE asset_history_asset
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE drive
        SQL);
    }
}
