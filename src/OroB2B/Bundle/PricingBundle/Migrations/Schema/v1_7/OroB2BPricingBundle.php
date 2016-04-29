<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_7;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BPricingBundle implements Migration, OrderedMigrationInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * @inheritDoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BPriceListScheduleTable($schema);
        $this->createOroB2BCplActivationRuleTable($schema);
        $this->addOrob2BPriceListScheduleForeignKeys($schema);
        $this->addOrob2BCplActivationRuleForeignKeys($schema);

        $this->recreateOroB2BCmbPriceListToAccTable($schema);
        $this->recreateOroB2BCmbPriceListToAccGrTable($schema);
        $this->recreateOroB2BCmbPriceListToWsTable($schema);

        $this->addOrob2BCmbPriceListToAccGrForeignKeys($schema);
        $this->addOrob2BCmbPriceListToWsForeignKeys($schema);
        $this->addOrob2BCmbPriceListToAccForeignKeys($schema);

        $this->alterOroB2BPriceListTable($schema, $queries);
        $this->alterOroB2BPriceListCombinedTable($schema, $queries);

        $this->updatePriceListChangeTriggerTable($schema);

        $queries->addPostQuery(new UpdateCPLRelationsQuery('orob2b_cmb_price_list_to_acc'));
        $queries->addPostQuery(new UpdateCPLRelationsQuery('orob2b_cmb_plist_to_acc_gr'));
        $queries->addPostQuery(new UpdateCPLRelationsQuery('orob2b_cmb_price_list_to_ws'));
        $queries->addPostQuery(new UpdateCPLNameQuery());
    }

    /**
     * Create orob2b_price_list_schedule table
     *
     * @param Schema $schema
     */
    protected function createOroB2BPriceListScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deactivate_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['price_list_id'], 'IDX_C706756E5688DED7', []);
    }

    /**
     * Create orob2b_cpl_activation_rule table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCplActivationRuleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cpl_activation_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('activate_at', 'datetime', ['notnull' => false]);
        $table->addColumn('expire_at', 'datetime', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

     /**
     * Create orob2b_cpl_activation_rule table
     *
     * @param Schema $schema
     */
    protected function updatePriceListChangeTriggerTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_ch_trigger');
        $table->addColumn('is_force', 'boolean', ['notnull' => false]);
    }
    
    /**
     * Add orob2b_price_list_schedule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListScheduleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_schedule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cpl_activation_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCplActivationRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cpl_activation_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_cmb_price_list_to_acc table
     *
     * @param Schema $schema
     */
    protected function recreateOroB2BCmbPriceListToAccTable(Schema $schema)
    {
        $schema->dropTable('orob2b_cmb_price_list_to_acc');
        $table = $schema->createTable('orob2b_cmb_price_list_to_acc');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['website_id', 'account_id'], 'orob2b_cpl_to_acc_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cmb_plist_to_acc_gr table
     *
     * @param Schema $schema
     */
    protected function recreateOroB2BCmbPriceListToAccGrTable(Schema $schema)
    {
        $schema->dropTable('orob2b_cmb_plist_to_acc_gr');
        $table = $schema->createTable('orob2b_cmb_plist_to_acc_gr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_group_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['website_id', 'account_group_id'], 'orob2b_cpl_to_acc_gr_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_cmb_price_list_to_ws table
     *
     * @param Schema $schema
     */
    protected function recreateOroB2BCmbPriceListToWsTable(Schema $schema)
    {
        $schema->dropTable('orob2b_cmb_price_list_to_ws');
        $table = $schema->createTable('orob2b_cmb_price_list_to_ws');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['website_id'], 'orob2b_cpl_to_ws_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_cmb_plist_to_acc_gr foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPriceListToAccGrForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_plist_to_acc_gr');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_group'),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cmb_price_list_to_ws foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPriceListToWsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_price_list_to_ws');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_cmb_price_list_to_acc foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCmbPriceListToAccForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cmb_price_list_to_acc');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterOroB2BPriceListTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_list');
        $table->addColumn('contain_schedule', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_list SET contain_schedule = :contain_schedule',
                [
                    'contain_schedule' => false,
                ],
                [
                    'contain_schedule' => Type::BOOLEAN
                ]
            )
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterOroB2BPriceListCombinedTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_list_combined');
        $table->addColumn('is_prices_calculated', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_list_combined as cpl SET is_prices_calculated = 
                (SELECT p.id IS NOT NULL FROM orob2b_price_product_combined as p 
                WHERE p.combined_price_list_id = cpl.id LIMIT 1)'
            )
        );
    }
}
