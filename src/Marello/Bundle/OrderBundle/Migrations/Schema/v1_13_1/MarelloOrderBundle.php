<?php

namespace Marello\Bundle\OrderBundle\Migrations\Schema\v1_13_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;

use Marello\Bundle\OrderBundle\Entity\Order;

class MarelloOrderBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('marello_order_order');
        $table->addColumn('locale_id', 'string', ['notnull' => false, 'length' => 255]);

        $queries->addQuery(
            new UpdateEntityConfigFieldValueQuery(
                Order::class,
                'salesChannel',
                'importexport',
                'full',
                false
            )
        );
    }
}
