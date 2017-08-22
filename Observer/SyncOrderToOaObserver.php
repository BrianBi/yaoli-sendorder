<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 15:34
 */

namespace Yaoli\Sendorder\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Yaoli\Sendorder\Helper\Data as sendOrderHelper;

class SyncOrderToOaObserver implements ObserverInterface
{
    protected $_objectManager;

    protected $logger;

    protected $_sendorderHelper;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Psr\Log\LoggerInterface $logger,
        sendOrderHelper $_sendorderHelper
    )
    {
        $this->_sendorderHelper = $_sendorderHelper;
        $this->_objectManager   = $objectManager;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $_order = $observer->getOrder();

        if(!$_order->getId()) return $this;

        $queneCollection = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist');

        $_queneModels = $queneCollection->getCollection()->addFieldToFilter('increment_id', $_order->getIncrementId());

        $_sendData    = serialize($this->_sendorderHelper->encapsulationOrderData($_order));

        if (count($_queneModels) > 0)
        {
            foreach ($_queneModels as $_queneModel)
            {
                if ($_queneModel->getId())
                {
                    try {
                        $_queneModel->setOrderStatus($_order->getStatus());
                        $_queneModel->setSendData($_sendData);
                        $_queneModel->save();
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                } else {
                    continue;
                }
            }

            return $this;
        }

        try {
            $queneCollection->setEntityId($_order->getId());
            $queneCollection->setIncrementId($_order->getIncrementId());
            $queneCollection->setOrderStatus($_order->getStatus());
            $queneCollection->setSendStatus(0);
            $queneCollection->setSendData($_sendData);
            $queneCollection->save();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this;
    }
}