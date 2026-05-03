<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503122213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE watra_administration_role_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE watra_city_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE watra_city_translation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE watra_venue_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE watra_venue_translation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE watra_administration_role (id INT NOT NULL, name VARCHAR(128) NOT NULL, permissions JSON NOT NULL, isSuperAdmin BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE watra_city (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE watra_city_translation (id INT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(128) NOT NULL, locale VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A81A292C2C2AC5D3 ON watra_city_translation (translatable_id)');
        $this->addSql('CREATE UNIQUE INDEX watra_city_translation_uniq_trans ON watra_city_translation (translatable_id, locale)');
        $this->addSql('CREATE TABLE watra_venue (id INT NOT NULL, city_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_462B8C858BAC62AF ON watra_venue (city_id)');
        $this->addSql('CREATE TABLE watra_venue_translation (id INT NOT NULL, translatable_id INT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(512) NOT NULL, locale VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_757ECA932C2AC5D3 ON watra_venue_translation (translatable_id)');
        $this->addSql('CREATE UNIQUE INDEX watra_venue_translation_uniq_trans ON watra_venue_translation (translatable_id, locale)');
        $this->addSql('ALTER TABLE watra_city_translation ADD CONSTRAINT FK_A81A292C2C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES watra_city (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watra_venue ADD CONSTRAINT FK_462B8C858BAC62AF FOREIGN KEY (city_id) REFERENCES watra_city (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watra_venue_translation ADD CONSTRAINT FK_757ECA932C2AC5D3 FOREIGN KEY (translatable_id) REFERENCES watra_venue (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE watra_administration_role_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE watra_city_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE watra_city_translation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE watra_venue_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE watra_venue_translation_id_seq CASCADE');
        $this->addSql('ALTER TABLE watra_city_translation DROP CONSTRAINT FK_A81A292C2C2AC5D3');
        $this->addSql('ALTER TABLE watra_venue DROP CONSTRAINT FK_462B8C858BAC62AF');
        $this->addSql('ALTER TABLE watra_venue_translation DROP CONSTRAINT FK_757ECA932C2AC5D3');
        $this->addSql('DROP TABLE watra_administration_role');
        $this->addSql('DROP TABLE watra_city');
        $this->addSql('DROP TABLE watra_city_translation');
        $this->addSql('DROP TABLE watra_venue');
        $this->addSql('DROP TABLE watra_venue_translation');
    }
}
