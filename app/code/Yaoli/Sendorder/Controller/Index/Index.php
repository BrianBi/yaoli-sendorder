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
        echo 'yes';
        /*$_model = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist')->load(['entity_id' => 1]);
        echo $_model->getId();*/

        /*$_order = $this->_objectManager->create('Magento\Sales\Model\Order')->load(3);
        $_sendData   = serialize(
            [
                'entity_id' => $_order->getId(),
                'increment_id' => $_order->getIncrementId(),
                'order_status' => $_order->getStatus(),
                'created_at'   => new \DateTime()
            ]
        );

        $_model = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist');
        $_model->setEntityId($_order->getId());
        $_model->setIncrementId($_order->getIncrementId());
        $_model->setOrderStatus($_order->getStatus());
        $_model->setSendStatus(0);
        $_model->setSendData($_sendData);*/
        //$_model->setCreatedAt();
        /*$_model->save();

        $this->getResponse()->appendBody('Yaoli Sendorder');*/

        /*$_quenelist = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist')
            ->getCollection()
            ->addFieldToFilter('send_status', 0);

        $_data = array();

        foreach ($_quenelist as $quene)
        {
            $_data['order_data'] = $quene->getSendData();
            $quene->setSendStatus(1);
            $quene->setSyncAt(time());
            $quene->save();
        }

        $this->getHelpers()->pushOrderData($_data);*/
        //var_dump($this->_objectManager->get('\Yaoli\Sendorder\Helper\Data')->getSendorderWebId());
        //echo $this->_SendorderHelper->getSendorderEnable();

        /*$_model = $this->_objectManager->create('Yaoli\Sendorder\Model\Quenelist')->load(['entity_id' => 5]);
        echo $_model->getId();

        var_dump($_model->getId());*/
        /*$_order = $this->_objectManager->create('Magento\Sales\Model\Order')->load(3);

        $helper = $this->_objectManager->get('\Yaoli\Sendorder\Helper\Data');
        $data   = $helper->encapsulationOrderData($_order);

        $helper->pushOrderdataByLib($data);*/
    }
}