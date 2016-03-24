<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BOrderBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->alterAddressTable($schema);
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterAddressTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_address');
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
    }
}
