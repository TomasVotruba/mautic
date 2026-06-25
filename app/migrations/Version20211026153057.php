<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;

final class Version20211026153057 extends PreUpAssertionMigration
{

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE INDEX {$this->getIndexName()} ON {$this->getTableName()} (`lead_id`, `date_added`)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP INDEX {$this->getIndexName()} ON {$this->getTableName()}");
    }
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(
            fn (Schema $schema) => $schema->getTable($this->getTableName())->hasIndex($this->getIndexName()),
            "Index {$this->getIndexName()} already exists"
        );
    }

    private function getTableName(): string
    {
        return "{$this->prefix}lead_frequencyrules";
    }

    private function getIndexName(): string
    {
        return "{$this->prefix}idx_frequency_date_added";
    }
}
