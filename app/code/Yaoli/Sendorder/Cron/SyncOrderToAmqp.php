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
    protected $objectManager;
    /**
     * construct
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * 发送数据到AMQP服务器
     */
    public function execute()
    {
        $_quenelist = $this->objectManager->create('Yaoli\Sendorder\Model\Quenelist')
            ->getCollection()
            ->addFieldToFilter('send_status', 0);

        $_data = array();

        foreach ($_quenelist as $quene)
        {
            try {
                $this->objectManager->get('\Yaoli\Sendorder\Helper\Data')->pushOrderData(unserialize($quene->getSendData()));
                $quene->setSendStatus(1);
                $quene->setSyncAt(time());
                $quene->save();
            } catch (\Exception $e) {
                throw new \Exception("Cannot Send Data to the RabbitMQ Exception {$quene->getIncrementId}");
            }
        }
    }
}