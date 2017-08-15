<?php
namespace Yaoli\Sendorder\Model;
use Magento\Framework\Model\AbstractModel;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 15:56
 */

class Quenelist extends AbstractModel
{
    const SYNC_QUENE_ID = 'id';

    protected $_idFieldName = self::SYNC_QUENE_ID;

    /**
     * define resource model
     */
    protected function _construct()
    {
        $this->_init('Yaoli\Sendorder\Model\ResourceModel\Quenelist');
    }
}