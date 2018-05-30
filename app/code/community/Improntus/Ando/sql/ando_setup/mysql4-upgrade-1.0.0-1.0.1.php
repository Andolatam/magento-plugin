<?php
/** @var Mage_Eav_Model_Entity_Setup $setup */
$setup = $this;
$setup->startSetup();

$sales_quote_address = $setup->getTable('sales/quote_address');

$setup->getConnection()
    ->addColumn($sales_quote_address, 'altura', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'comment' => 'Altura de calle'
    ));

$sales_order_address = $setup->getTable('sales/order_address');
$setup->getConnection()
    ->addColumn($sales_order_address, 'altura', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'comment' => 'Altura de calle'
    ));

$setup->getConnection()
    ->addColumn($sales_quote_address, 'observaciones', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Observaciones'
    ));

$sales_order_address = $setup->getTable('sales/order_address');
$setup->getConnection()
    ->addColumn($sales_order_address, 'observaciones', array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'comment' => 'Observaciones'
    ));

$sales_quote = $setup->getTable('sales/quote');

$setup->getConnection()
    ->addColumn($sales_quote, 'ando_quote_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'comment' => 'Quote Id de cotizacion ando'
    ));

$sales_order = $setup->getTable('sales/order');
$setup->getConnection()
    ->addColumn($sales_order, 'ando_quote_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'comment' => 'Quote Id de cotizacion ando'
    ));

$setup->endSetup();