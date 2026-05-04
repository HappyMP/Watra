<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504203532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE watra_interest_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE watra_interest (id INT NOT NULL, customer_id INT NOT NULL, product_id INT NOT NULL, createdAt TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_96AC16C79395C3F3 ON watra_interest (customer_id)');
        $this->addSql('CREATE INDEX IDX_96AC16C74584665A ON watra_interest (product_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_interest_customer_product ON watra_interest (customer_id, product_id)');
        $this->addSql('COMMENT ON COLUMN watra_interest.createdAt IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE watra_interest ADD CONSTRAINT FK_96AC16C79395C3F3 FOREIGN KEY (customer_id) REFERENCES sylius_customer (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE watra_interest ADD CONSTRAINT FK_96AC16C74584665A FOREIGN KEY (product_id) REFERENCES sylius_product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE watra_interest_id_seq CASCADE');
        $this->addSql('ALTER TABLE watra_interest DROP CONSTRAINT FK_96AC16C79395C3F3');
        $this->addSql('ALTER TABLE watra_interest DROP CONSTRAINT FK_96AC16C74584665A');
        $this->addSql('DROP TABLE watra_interest');
    }
}
