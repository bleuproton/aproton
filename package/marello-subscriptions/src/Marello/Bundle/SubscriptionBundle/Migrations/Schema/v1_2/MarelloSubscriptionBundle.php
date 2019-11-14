<?php

namespace Marello\Bundle\SubscriptionBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MarelloSubscriptionBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('marello_subscription');
        if ($table->hasForeignKey('FK_D411FA7F9395C3F3')) {
            $table->removeForeignKey('FK_D411FA7F9395C3F3');
        }
        $table->addForeignKeyConstraint(
            $schema->getTable('marello_customer_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
