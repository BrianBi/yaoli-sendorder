<?php
namespace Yaoli\Sendorder\Setup;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 14:35
 */

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /**
         * Create Table yaoli_sendorder_quene
         */
        $installer = $setup;
        $installer->startSetup();

        $table = $installer->getConnection()
            ->newTable($installer->getTable('yaoli_sendorder_quene'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'SendOrder Quene ID'
            )->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                11,
                ['unsigned' => true],
                'Order Entity ID'
            )->addColumn(
                'increment_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Order Increment Id'
            )->addColumn(
                'order_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                32,
                [],
                'Order Status'
            )->addColumn(
                'send_status',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['default' => 0],
                'Push To OA Status 0:pending 1:complete'
            )->addColumn(
                'send_data',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                [],
                'Push To OA Data'
            )->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Created At'
            )->addColumn(
                'synced_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false],
                'Synced At'
            )->addIndex(
                $installer->getIdxName('yaoli_sendorder_quene', ['id']),
                ['id']
            )->addIndex(
                $installer->getIdxName('yaoli_sendorder_quene', ['entity_id']),
                ['entity_id']
            )->addIndex(
                $installer->getIdxName('yaoli_sendorder_quene', ['increment_id']),
                ['increment_id']
            )->addIndex(
                $installer->getIdxName('yaoli_sendorder_quene', ['send_status']),
                ['send_status']
            )->setComment("Yaoli SendOrder Quene Table");

        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}