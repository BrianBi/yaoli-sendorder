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
     * @var Yaoli\Sendorder\Model\ResourceModel\CollectionFactory
     */
    private $queneCollectionFactory;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * construct
     */
    public function __construct(
        \Yaoli\Sendorder\Model\ResourceModel\CollectionFactory $queneCollectionFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->queneCollectionFactory = $queneCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * 发送数据到AMQP服务器
     */
    public function execute()
    {
        $this->logger->critical("Cron Job Run!!!");
        $collection = $this->queneCollectionFactory->create();
        $collection->addFilter('send_status', 0);

        foreach ($collection as $quene)
        {
            try {
                $this->objectManager->get('\Yaoli\Sendorder\Helper\Data')->pushOrderdataByLib(unserialize($quene->getSendData()));
                $quene->setSendStatus(1);
                $quene->setSyncAt(time());
                $quene->save();
            } catch (\Exception $e) {
                $this->logger->critical($e."Cannot Send Data to the RabbitMQ Exception {$quene->getIncrementId}");
            }
        }

        return $this;
    }
}