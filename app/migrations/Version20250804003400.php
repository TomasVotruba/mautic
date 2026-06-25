<?php

declare(strict_types=1);

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;
use Mautic\LeadBundle\Segment\OperatorOptions;

class Version20250804003400 extends AbstractMauticMigration
{
    private string $leadListsTable;

    public function preUp(Schema $schema): void
    {
        $this->leadListsTable = $this->prefix.'lead_lists';
    }

    public function up(Schema $schema): void
    {
        $sql                  = 'SELECT id, filters FROM '.$this->leadListsTable.' WHERE filters LIKE "%multiselect%"';
        $listsWithMultiselect = $this->connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($listsWithMultiselect as $listData) {
            $filters = unserialize($listData['filters'], ['allowed_classes' => false]);
            $changed = false;
            foreach ($filters as $index => $filter) {
                if ($filter['type'] !== 'multiselect') {
                    continue;
                }

                if ($filter['operator'] === OperatorOptions::INCLUDING_ANY) {
                    $filters[$index]['operator'] = OperatorOptions::INCLUDING_ALL;
                    $changed                     = true;
                } elseif ($filter['operator'] === OperatorOptions::EXCLUDING_ANY) {
                    $filters[$index]['operator'] = OperatorOptions::EXCLUDING_ALL;
                    $changed                     = true;
                }
            }

            if ($changed) {
                $this->addSql(
                    'UPDATE '.$this->leadListsTable.' SET filters = :filters WHERE id = :id',
                    [
                        'filters' => serialize($filters),
                        'id'      => $listData['id'],
                    ]
                );
            }
        }
    }

    public function preDown(Schema $schema): void
    {
        $this->leadListsTable = $this->prefix.'lead_lists';
    }

    public function down(Schema $schema): void
    {
        $sql                  = 'SELECT id, filters FROM '.$this->leadListsTable.' WHERE filters LIKE "%multiselect%"';
        $listsWithMultiselect = $this->connection->executeQuery($sql)->fetchAllAssociative();

        foreach ($listsWithMultiselect as $listData) {
            $filters = unserialize($listData['filters'], ['allowed_classes' => false]);
            $changed = false;
            foreach ($filters as $index => $filter) {
                if ($filter['type'] !== 'multiselect') {
                    continue;
                }

                if ($filter['operator'] === OperatorOptions::INCLUDING_ALL) {
                    $filters[$index]['operator'] = OperatorOptions::INCLUDING_ANY;
                    $changed                     = true;
                } elseif ($filter['operator'] === OperatorOptions::EXCLUDING_ALL) {
                    $filters[$index]['operator'] = OperatorOptions::EXCLUDING_ANY;
                    $changed                     = true;
                }
            }

            if ($changed) {
                $this->addSql(
                    'UPDATE '.$this->leadListsTable.' SET filters = :filters WHERE id = :id',
                    [
                        'filters' => serialize($filters),
                        'id'      => $listData['id'],
                    ]
                );
            }
        }
    }
}
