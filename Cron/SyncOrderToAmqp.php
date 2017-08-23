<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19
 * Time: 14:49
 */
namespace Yaoli\Sendorder\Cron;

class SyncOrderToAmqp
{
    /**
     * construct
     */
    public function __construct()
    {

    }

    /**
     * 发送数据到AMQP服务器
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $queneCollection = $objectManager->create('Yaoli\Sendorder\Model\Quenelist');

        $collection = $queneCollection->getCollection()->addFieldToFilter('send_status', 0);

        if (count($collection) > 0)
        {
            foreach ($collection as $quene)
            {
                try {
                    $objectManager->get('\Yaoli\Sendorder\Helper\Data')->pushOrderdataByLib(unserialize($quene->getSendData()));
                    $quene->setSendStatus(1);
                    $quene->setSyncedAt(time());
                    $quene->save();
                } catch (\Exception $e) {
                    throw new Exception("Cannot Send Data to the RabbitMQ Exception {$quene->getIncrementId}");
                }
            }
        }

        return $this;
    }
}