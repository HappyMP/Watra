<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260503192655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE watra_admin_user_administration_role (adminuser_id INT NOT NULL, administrationrole_id INT NOT NULL, PRIMARY KEY(adminuser_id, administrationrole_id))');
        $this->addSql('CREATE INDEX IDX_8DE498E239505EF ON watra_admin_user_administration_role (adminuser_id)');
        $this->addSql('CREATE INDEX IDX_8DE498E2AAEAA9C1 ON watra_admin_user_administration_role (administrationrole_id)');
        $this->addSql('ALTER TABLE watra_admin_user_administration_role ADD CONSTRAINT FK_8DE498E239505EF FOREIGN KEY (adminuser_id) REFERENCES sylius_admin_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watra_admin_user_administration_role ADD CONSTRAINT FK_8DE498E2AAEAA9C1 FOREIGN KEY (administrationrole_id) REFERENCES watra_administration_role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_product ADD city_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD isOnline BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD eventStatus VARCHAR(32) DEFAULT \'draft\' NOT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD eventType VARCHAR(32) DEFAULT \'other\' NOT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD defaultVenue_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product ADD CONSTRAINT FK_677B9B748BAC62AF FOREIGN KEY (city_id) REFERENCES watra_city (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sylius_product ADD CONSTRAINT FK_677B9B743F1CECD4 FOREIGN KEY (defaultVenue_id) REFERENCES watra_venue (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_677B9B748BAC62AF ON sylius_product (city_id)');
        $this->addSql('CREATE INDEX IDX_677B9B743F1CECD4 ON sylius_product (defaultVenue_id)');
        $this->addSql('ALTER TABLE sylius_product_variant ADD venue_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product_variant ADD startsAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product_variant ADD endsAt TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE sylius_product_variant ADD CONSTRAINT FK_A29B52340A73EBA FOREIGN KEY (venue_id) REFERENCES watra_venue (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A29B52340A73EBA ON sylius_product_variant (venue_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE watra_admin_user_administration_role DROP CONSTRAINT FK_8DE498E239505EF');
        $this->addSql('ALTER TABLE watra_admin_user_administration_role DROP CONSTRAINT FK_8DE498E2AAEAA9C1');
        $this->addSql('DROP TABLE watra_admin_user_administration_role');
        $this->addSql('ALTER TABLE sylius_product DROP CONSTRAINT FK_677B9B748BAC62AF');
        $this->addSql('ALTER TABLE sylius_product DROP CONSTRAINT FK_677B9B743F1CECD4');
        $this->addSql('DROP INDEX IDX_677B9B748BAC62AF');
        $this->addSql('DROP INDEX IDX_677B9B743F1CECD4');
        $this->addSql('ALTER TABLE sylius_product DROP city_id');
        $this->addSql('ALTER TABLE sylius_product DROP isOnline');
        $this->addSql('ALTER TABLE sylius_product DROP eventStatus');
        $this->addSql('ALTER TABLE sylius_product DROP eventType');
        $this->addSql('ALTER TABLE sylius_product DROP defaultVenue_id');
        $this->addSql('ALTER TABLE sylius_product_variant DROP CONSTRAINT FK_A29B52340A73EBA');
        $this->addSql('DROP INDEX IDX_A29B52340A73EBA');
        $this->addSql('ALTER TABLE sylius_product_variant DROP venue_id');
        $this->addSql('ALTER TABLE sylius_product_variant DROP startsAt');
        $this->addSql('ALTER TABLE sylius_product_variant DROP endsAt');
    }
}
