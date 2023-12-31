<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230816213641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE episode_actor (episode_id INT NOT NULL, actor_id INT NOT NULL, INDEX IDX_7F7FA0AD362B62A0 (episode_id), INDEX IDX_7F7FA0AD10DAF24A (actor_id), PRIMARY KEY(episode_id, actor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE episode_actor ADD CONSTRAINT FK_7F7FA0AD362B62A0 FOREIGN KEY (episode_id) REFERENCES episode (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE episode_actor ADD CONSTRAINT FK_7F7FA0AD10DAF24A FOREIGN KEY (actor_id) REFERENCES actor (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE episode_actor DROP FOREIGN KEY FK_7F7FA0AD362B62A0');
        $this->addSql('ALTER TABLE episode_actor DROP FOREIGN KEY FK_7F7FA0AD10DAF24A');
        $this->addSql('DROP TABLE episode_actor');
    }
}
