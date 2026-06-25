<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20250923114835 extends PreUpAssertionMigration
{
    protected const TABLE_NAME = 'campaign_lead_event_log';

    public function up(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->addColumn('date_queued', Types::DATETIME_MUTABLE)->setNotnull(false);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable($this->getPrefixedTableName());
        $table->dropColumn('date_queued');
    }

    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getPrefixedTableName())->hasColumn('date_queued'),
            "Table {$this->getPrefixedTableName()} already has 'date_queued' column"
        );
    }
}
