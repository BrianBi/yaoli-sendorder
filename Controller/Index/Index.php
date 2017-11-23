<?php
namespace Yaoli\Sendorder\Controller\Index;
use Magento\Framework\App\Action\Action;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 11:19
 */

class Index extends Action
{
    public function __construct(\Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['action']))
        {
            if ($params['action'] == 'run')
            {
                $_quenelist = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist')->getCollection()->addFieldToFilter('send_status', 0);

                if (count($_quenelist) > 0)
                {
                    foreach ($_quenelist as $quene)
                    {
                        try {
                            $this->_objectManager->create('\Yaoli\Sendorder\Helper\Data')->pushOrderdataByLib(unserialize($quene->getSendData()));
                            $quene->setSendStatus(1);
                            $quene->setSyncedAt(time());
                            $quene->save();
                        } catch (\Exception $e) {
                            //$this->logger->critical($e."Cannot Send Data to the RabbitMQ Exception {$quene->getIncrementId}");
                            throw new Exception("Cannot Send Data to the RabbitMQ Exception {$quene->getIncrementId}");
                        }
                    }
                }

                exit('Completed...');
            } else if ($params['action'] == 'delete') {
                if (isset($params['items']))
                {
                    if ($params['items'] !== 'all')
                    {
                        $_id = (int) $params['items'];
                        $_model = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist')->load($_id);
                        $_model->delete();
                    } else if ($params['items'] == 'all') {
                        $_quenelist = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist')->getCollection()->addFieldToFilter('send_status', 1);

                        if (count($_quenelist) > 0)
                        {
                            foreach ($_quenelist as $quene)
                            {
                                try {
                                    $quene->delete();
                                } catch (\Exception $e) {
                                    throw new Exception("Cannot Send Data to the RabbitMQ Exception {$quene->getIncrementId}");
                                }
                            }
                        }
                    }

                    exit('Delete Completed...');
                }
            }
        }

        $this->_redirect('/');
    }
}