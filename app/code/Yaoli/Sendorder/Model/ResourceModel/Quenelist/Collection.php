<?php
namespace Yaoli\Sendorder\Model\ResourceModel\Quenelist;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 16:08
 */
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = \Yaoli\Sendorder\Model\Quenelist::SYNC_QUENE_ID;

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Yaoli\Sendorder\Model\Quenelist', 'Yaoli\Sendorder\Model\ResourceModel\Quenelist');
    }
}