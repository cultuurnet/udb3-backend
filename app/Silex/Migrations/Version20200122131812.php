<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200122131812 extends AbstractMigration
{
    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE event_permission_readmodel MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE organizer_permission_readmodel MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE place_permission_readmodel MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE saved_searches_sapi2 MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE saved_searches_sapi3 MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user_roles MODIFY COLUMN user_id VARCHAR(255) NOT NULL');
    }


    public function down(Schema $schema)
    {
    }
}
