<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200611170310 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE source_document (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, media INT DEFAULT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, INDEX IDX_9B49BCA4953C1C61 (source_id), INDEX IDX_9B49BCA46A2CA10C (media), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE derivation_source (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_B1CAFC3E953C1C61 (source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source (id INT AUTO_INCREMENT NOT NULL, source_type_id INT DEFAULT NULL, image INT DEFAULT NULL, waveform INT DEFAULT NULL, licence_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, creation_date DATE DEFAULT NULL, publication_date DATETIME NOT NULL, INDEX IDX_5F8A7F738C9334FB (source_type_id), INDEX IDX_5F8A7F73C53D045F (image), UNIQUE INDEX UNIQ_5F8A7F7311133403 (waveform), INDEX IDX_5F8A7F7326EF07C9 (licence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE licence (id INT AUTO_INCREMENT NOT NULL, image INT DEFAULT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, INDEX IDX_1DAAE648C53D045F (image), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE author (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, image INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, has_contact_form TINYINT(1) NOT NULL, use_captcha TINYINT(1) NOT NULL, publication_date DATETIME NOT NULL, INDEX IDX_BDAFD8C8A76ED395 (user_id), INDEX IDX_BDAFD8C8C53D045F (image), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_author (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, author_id INT DEFAULT NULL, author_role_id INT DEFAULT NULL, is_valid TINYINT(1) NOT NULL, INDEX IDX_B50FD2E953C1C61 (source_id), INDEX IDX_B50FD2EF675F31B (author_id), INDEX IDX_B50FD2E9339BDEF (author_role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_composition (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, composition_id INT DEFAULT NULL, position INT DEFAULT 0 NOT NULL, INDEX IDX_300013E7953C1C61 (source_id), INDEX IDX_300013E787A2E12 (composition_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_info (id INT AUTO_INCREMENT NOT NULL, source_id INT DEFAULT NULL, info_key VARCHAR(255) NOT NULL, info_value LONGTEXT DEFAULT NULL, INDEX IDX_CA03B454953C1C61 (source_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE source_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE author_role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE source_document ADD CONSTRAINT FK_9B49BCA4953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE source_document ADD CONSTRAINT FK_9B49BCA46A2CA10C FOREIGN KEY (media) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE derivation_source ADD CONSTRAINT FK_B1CAFC3E953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F738C9334FB FOREIGN KEY (source_type_id) REFERENCES source_type (id)');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F73C53D045F FOREIGN KEY (image) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F7311133403 FOREIGN KEY (waveform) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE source ADD CONSTRAINT FK_5F8A7F7326EF07C9 FOREIGN KEY (licence_id) REFERENCES licence (id)');
        $this->addSql('ALTER TABLE licence ADD CONSTRAINT FK_1DAAE648C53D045F FOREIGN KEY (image) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE author ADD CONSTRAINT FK_BDAFD8C8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE author ADD CONSTRAINT FK_BDAFD8C8C53D045F FOREIGN KEY (image) REFERENCES media (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE source_author ADD CONSTRAINT FK_B50FD2E953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE source_author ADD CONSTRAINT FK_B50FD2EF675F31B FOREIGN KEY (author_id) REFERENCES author (id)');
        $this->addSql('ALTER TABLE source_author ADD CONSTRAINT FK_B50FD2E9339BDEF FOREIGN KEY (author_role_id) REFERENCES author_role (id)');
        $this->addSql('ALTER TABLE source_composition ADD CONSTRAINT FK_300013E7953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE source_composition ADD CONSTRAINT FK_300013E787A2E12 FOREIGN KEY (composition_id) REFERENCES source (id)');
        $this->addSql('ALTER TABLE source_info ADD CONSTRAINT FK_CA03B454953C1C61 FOREIGN KEY (source_id) REFERENCES source (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE source_document DROP FOREIGN KEY FK_9B49BCA4953C1C61');
        $this->addSql('ALTER TABLE derivation_source DROP FOREIGN KEY FK_B1CAFC3E953C1C61');
        $this->addSql('ALTER TABLE source_author DROP FOREIGN KEY FK_B50FD2E953C1C61');
        $this->addSql('ALTER TABLE source_composition DROP FOREIGN KEY FK_300013E7953C1C61');
        $this->addSql('ALTER TABLE source_composition DROP FOREIGN KEY FK_300013E787A2E12');
        $this->addSql('ALTER TABLE source_info DROP FOREIGN KEY FK_CA03B454953C1C61');
        $this->addSql('ALTER TABLE source DROP FOREIGN KEY FK_5F8A7F7326EF07C9');
        $this->addSql('ALTER TABLE source_author DROP FOREIGN KEY FK_B50FD2EF675F31B');
        $this->addSql('ALTER TABLE source DROP FOREIGN KEY FK_5F8A7F738C9334FB');
        $this->addSql('ALTER TABLE source_author DROP FOREIGN KEY FK_B50FD2E9339BDEF');
        $this->addSql('DROP TABLE source_document');
        $this->addSql('DROP TABLE derivation_source');
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE licence');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE source_author');
        $this->addSql('DROP TABLE source_composition');
        $this->addSql('DROP TABLE source_info');
        $this->addSql('DROP TABLE source_type');
        $this->addSql('DROP TABLE author_role');
    }
}
