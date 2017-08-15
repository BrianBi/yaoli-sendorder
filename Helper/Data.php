<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/19
 * Time: 15:29
 */
namespace Yaoli\Sendorder\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Yaoli\Sendorder\Model\RabbitMQ;

class Data extends AbstractHelper
{
    protected $_objectManager;

    protected $storeManager;

    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_objectManager = $objectManager;
        $this->storeManager   = $storeManager;
        parent::__construct($context);
    }

    /**
     * get sendorder enable
     * @return string
     */
    public function getSendorderEnable()
    {
        return $this->scopeConfig->getValue('sendorder/general/enable');
    }

    /**
     * get sendorder webid
     * @return string
     */
    public function getSendorderWebId()
    {
        return $this->scopeConfig->getValue('sendorder/general/webid');
    }

    /**
     * get sendorder channel
     * @return string
     */
    public function getSendorderChannel()
    {
        return $this->scopeConfig->getValue('sendorder/general/channel');
    }

    /**
     * get sendorder job_timeout
     * @return string
     */
    public function getSendorderJobTimeout()
    {
        return $this->scopeConfig->getValue('sendorder/general/job_timeout');
    }

    /**
     * get sendorder routingkey
     * @return string
     */
    public function getSendorderRoutingkey()
    {
        return $this->scopeConfig->getValue('sendorder/general/routingkey');
    }

    /**
     * get sendorder quenue_name
     * @return string
     */
    public function getSendorderQuenueName()
    {
        return $this->scopeConfig->getValue('sendorder/general/quenue_name');
    }

    /**
     * get sendorder host
     * @return string
     */
    public function getSendorderAmqphost()
    {
        return $this->scopeConfig->getValue('sendorder/connection/host');
    }

    /**
     * @return string
     */
    public function getSendorderAmqpport()
    {
        return $this->scopeConfig->getValue('sendorder/connection/port');
    }

    /**
     * get sendorder vhost
     * @return string
     */
    public function getSendorderAmqpvhost()
    {
        return $this->scopeConfig->getValue('sendorder/connection/vhost');
    }

    /**
     * get sendorder user
     * @return string
     */
    public function getSendorderAmqpuser()
    {
        return $this->scopeConfig->getValue('sendorder/connection/user');
    }

    /**
     * get sendorder password
     * @return string
     */
    public function getSendorderAmqppassword()
    {
        return $this->scopeConfig->getValue('sendorder/connection/password');
    }

    /**
     * get sendorder login
     * @return string
     */
    public function getSendorderAmqplogin()
    {
        return $this->scopeConfig->getValue('sendorder/connection/login');
    }

    /**
     * get store scope
     * @return string
     */
    protected function getStoreScope()
    {
        return \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
    }

    /**
     * push data by phplibamqp
     */
    public function pushOrderdataByLib($_data)
    {
        $_url = 'amqp://flkmvjaa:7x7ssCOMjJx6_BCuVyYVotu6H065nSgV@orangutan.rmq.cloudamqp.com/flkmvjaa';

        $amqp = RabbitMQ::create('M2AMQP', $_url);

        $result = $amqp->publish($_data);

        return $result;
    }

    public function pushOrderData($_data)
    {
        $conn_args = array(
            'host'  => $this->getSendorderAmqphost(),
            'port'  => $this->getSendorderAmqpport(),
            'login' => $this->getSendorderAmqplogin(),
            'password' => $this->getSendorderAmqppassword(),
            'vhost'    => $this->getSendorderAmqpvhost()
        );

        $e_name = $this->getSendorderQuenueName();
        $k_route= $this->getSendorderRoutingkey();

        $conn = new \AMQPConnection($conn_args);

        if (!$conn->connect()) {
            die("Cannot connect to the broker!\n");
        }

        $channel = new \AMQPChannel($conn);


        $ex = new \AMQPExchange($channel);
        $ex->setName($e_name);

        $channel->startTransaction();

        foreach ($_data as $_msg)
        {
            $ex->publish($_msg, $k_route);
        }

        $channel->commitTransaction();

        $conn->disconnect();
    }

    /**
     * Order Data encapsulation
     * @param \Magento\Sales\Model\Order $_order
     */
    public function encapsulationOrderData($_order)
    {
        if (!$_order->getId()) return;

        $_payment = $_order->getPayment();

        $_data = array();

        $_data['web_id']   = $this->getSendorderWebId();
        $_data['order_id'] = $_order->getIncrementId();
        $_data['action']   = $_order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE ? 1 : 0;

        if ($_order->getBusinessAccount())
            $_data['pay_account'] = $_order->getBusinessAccount();
        else
            $_data['pay_account'] = '';

        $_data['customer_email']  = $_order->getCustomerEmail();

        $_data['order_add_webid'] = $this->getSendorderWebId();

        $_data['total_money'] = $_payment->getData('amount_paid');
        $_data['currency']    = $_order->getOrderCurrencyCode();

        $_data['pay_method'] = $this->getOrderPaymentdSyncOaId($_order);

        /* @var $_data['transaction_id'] 根据不同支付方式获取 */
        if (!$_payment->getData('adyen_psp_reference'))
            $_data['transaction_id'] = $_payment->getData('last_trans_id');
        else
            $_data['transaction_id'] = $_payment->getData('adyen_psp_reference');

        if (!$_data['transaction_id'])
        {
            $_orderComments = $this->checkTransactionId($_order);
            if (is_array($_orderComments))
            {
                $_data['pay_method']     = $_orderComments['pay_method'];
                $_data['total_money']    = $_orderComments['total_money'];
                $_data['transaction_id'] = $_orderComments['transaction_id'];
                $_data['currency']       = $_orderComments['currency'];
            }
        }

        $_data['Language'] = $this->storeManager->getStore()->getCode();

        $_data['telephone'] = $this->getCustomerInfo($_order)['telephone'];

        $_data['order_add_webid'] = $this->getSendorderWebId();

        /* If Order Form Mudles */
        //$orderFromData = Mage::helper('orderfrom')->getOrderFromInfo($order->getId());
        $_data['is_mobile'] = '';
        $_data['buy_ip']    = $_order->getRemoteIp();
        $_data['browser']   = '';
        $_data['http_referer'] = $this->_objectManager->get('Magento\Framework\Session\Storage')->getData('user_http_referer_log');

        return array_merge($_data, $this->getOrderAllItems($_order));
    }

    /**
     * Get Order Items
     * @param \Magento\Sales\Model\Order $_order
     * @return array
     */
    protected function getOrderAllItems($_order)
    {
        $_data       = array();
        $_orderItmes = array();

        foreach ($_order->getAllItems() as $_items)
        {
            $_orderItmes[] = array(
                array(
                    'Product' => $_items->getName(),
                    'Name'    => $_items->getProductId()
                ),
                'CreatedAt' => $_order->getCreatedAt(),
                'PayTime'   => $_order->getUpdatedAt(),
                'FinishTime'=> $_order->getUpdatedAt(),
                'Status'    => $_order->getStatus(),
                'TotalMoney'=> $_items->getBaseRowTotal(),
                'Quantity'  => $_items->getQtyOrdered(),
            );
        }

        $_customer = $this->getCustomerInfo($_order);

        $_data = array(
            'OrderNo' => $_order->getIncrementId(),
            'Customer'=> array(
                'Name'    => $_customer['Name'],
                'Address' => $_customer['Address'],
                'City'    => $_customer['City'],
                'Country' => $_customer['Country'],
                'Phone'   => $_customer['telephone'],
                'PostCode'=> $_customer['PostCode'],
                'BuyIP'   => $_customer['BuyIP'],
                'Email'   => $_customer['Email']
            ),
            'WebId'         => $this->getSendorderWebId(),
            'Language'      => $this->storeManager->getStore()->getCode(),
            'TransactionId' => $_order->getPayment()->getLastTransId(),
            'PayMode'       => $this->getOrderPaymentdSyncOaId($_order),
            'Currency'      => $_order->getOrderCurrencyCode(),
            'OrderArray'    => $_orderItmes
        );

        return $_data;
    }

    /**
     * getBillingAddress Form Order
     * @param \Magento\Sales\Model\Order $_order
     * @return array
     */
    public function getCustomerInfo($_order)
    {
        $_orderBilling = $_order->getBillingAddress();

        return array(
            'telephone' => $_orderBilling->getTelephone(),
            'Name'      => $_orderBilling->getFirstname() .' '.$_orderBilling->getLastname(),
            'City'      => $_orderBilling->getCity(),
            'Country'   => $_orderBilling->getCountryId(),
            'PostCode'  => $_orderBilling->getPostcode(),
            'BuyIP'     => $_order->getRemoteIp(),
            'Email'     => $_order->getCustomerEmail(),
            'Address'   => $_orderBilling->getStreet(),
        );
    }

    /**
     * get order pay method
     * @param \Magento\Sales\Model\Order $_order
     * @return String
     */
    protected function getOrderPaymentdSyncOaId($_order)
    {
        $_payment = $_order->getPayment();
        $_payCode = $_payment->getMethod();
        $_payInfo = $_payment->getData('additional_information');

        if ($_payCode == 'paypal_express' && $_order->getBusinessAccount() == 'Gloryprofit@outlook.com')
        {
            return $this->_getPayCode('pp-lc');
        } elseif ($_payCode == 'globalpay' && isset($_payInfo['globalpay_method_id']))
        {
            return $this->_getPayCode('globalpay-' . $_payInfo['globalpay_method_id']);
        } else {
            return $this->_getPayCode($_payCode);
        }
    }

    /**
     * Check transaction_id Exsits
     * @param \Magento\Sales\Model\Order $_order
     * @return array
     */
    protected function checkTransactionId($_order)
    {
        $_orderComments = $_order->getStatusHistoryCollection();

        if (!is_array($_orderComments)) return false;

        $_data = array();

        foreach ($_orderComments as $_comments)
        {
            if ($comment_info = $_comments->getComment())
            {
                if (preg_match('/Smart2Pay/i', $comment_info) and preg_match('/(MethodID=\d+)/i', $comment_info, $pay_ids) and preg_match('/(MerchantTransactionID=\d+)/i', $comment_info, $pay_trans) and preg_match('/(Amount=\d+)/i', $comment_info, $pay_amouts) and preg_match('/(MerchantTransactionID=\d+)/i', $comment_info, $pay_curencys))
                {
                    $_data['pay_method']     = $this->_getPayCode('globalpay-' . str_replace('MethodID=', '', $pay_ids[0]));
                    $_data['total_money']    = round((float)(str_replace('Amount=', '', $pay_amouts[0]) / 100),2);
                    $_data['transaction_id'] = str_replace('MerchantTransactionID=', '', $pay_trans[0]);
                    $_data['currency']       = str_replace('Currency=', '', $pay_curencys[0]);

                    break;
                }
            }
        }

        if (count($_data) < 1)
            return false;

        return$_data;
    }

    /**
     * pay code
     * @parm var
     * @return array()
     *
     */
    public function _getPayCode($pay_method = null)
    {
        if($pay_method == null) return false;

        $all_code = $this->allPayCode();

        if(isset($all_code[$pay_method]))
        {
            return $all_code[$pay_method];
        }

        return false;
    }

    /**
     * all pay code
     * @parm
     * @return var
     */
    public function allPayCode()
    {
        return array(
            "ebank2pay"        =>4,
            "free"             =>14,
            "moneybookers_pwy" =>8,
            "moneybookers_csi" =>8,
            "moneybookers_gcb" =>8,
            "moneybookers_acc" =>8,
            "moneybookers_dnk" =>8,
            "moneybookers_npy" =>8,
            "moneybookers_gir" =>8,
            "moneybookers_lsr" =>8,
            "moneybookers_did" =>8,
            "moneybookers_mae" =>8,
            "moneybookers_wlt" =>8,
            "moneybookers_ebt" =>8,
            "moneybookers_so2" =>8,
            "moneybookers_obt" =>8,
            "moneybookers_pli" =>8,
            "moneybookers_psp" =>8,
            "moneybookers_sft" =>8,
            "moneybookers_ent" =>8,
            "moneybookers_idl" =>8,
            "paypal_standard" =>2,
            "payflow_advanced" =>2,
            "payflow_link" =>2,
            "paypal_billing_agreement" =>2,
            "paypal_express" =>2,
            "paypaluk_express" =>2,
            "paypal_direct" =>2,
            "paypaluk_direct" =>2,
            "verisign" =>2,
            "hosted_pro" =>2,
            "paypal_express_bml" =>2,
            "paypaluk_express_bml" =>2,
            "pbridge_paypal_direct" =>2,
            "pbridge_paypaluk_direct" =>2,
            "paysafecard" =>3,
            "globalpay-1" =>21,
            "globalpay-2" =>22,
            "globalpay-3" =>24,
            "globalpay-4" =>16,
            "globalpay-5" =>23,
            "globalpay-8" =>22,
            "globalpay-9" =>17,
            "globalpay-12" =>22,
            "globalpay-13" =>43,
            "globalpay-14" =>20,
            "globalpay-18" =>22,
            "globalpay-19" =>22,
            "globalpay-20" =>22,
            "globalpay-22" =>22,
            "globalpay-23" =>25,
            "globalpay-24" =>22,
            "globalpay-25" =>22,
            "globalpay-27" =>22,
            "globalpay-28" =>19,
            "globalpay-29" =>22,
            "globalpay-32" =>22,
            "globalpay-33" =>22,
            "globalpay-34" =>22,
            "globalpay-35" =>22,
            "globalpay-36" =>22,
            "globalpay-37" =>22,
            "globalpay-40" =>18,
            "globalpay-42" =>22,
            "globalpay-43" =>22,
            "globalpay-44" =>22,
            "globalpay-46" =>22,
            "globalpay-47" =>22,
            "globalpay-49" =>22,
            "globalpay-58" =>22,
            "globalpay-62" =>22,
            "globalpay-63" =>22,
            "globalpay-64" =>22,
            "globalpay-65" =>22,
            "globalpay-66" =>22,
            "globalpay-67" =>22,
            "globalpay-69" =>22,
            "globalpay-72" =>22,
            "globalpay-73" =>22,
            "globalpay-74" =>22,
            "globalpay-76" =>22,
            "globalpay-1000" =>22,
            "globalpay-1001" =>22,
            "globalpay-1002" =>22,
            "globalpay-1003" =>22,
            "globalpay-1004" =>22,
            "globalpay-1005" =>22,
            "globalpay-1006" =>22,
            "globalpay-1007" =>22,
            "globalpay-1008" =>22,
            "globalpay-1009" =>22,
            "globalpay-1010" =>22,
            "globalpay-1011" =>22,
            "globalpay-1012" =>22,
            "globalpay-1013" =>22,
            "globalpay-1014" =>22,
            "globalpay-1015" =>22,
            "globalpay-1016" =>22,
            "globalpay-1017" =>22,
            "globalpay-1018" =>22,
            "globalpay-1019" =>22,
            "globalpay-1020" =>22,
            "globalpay-1021" =>22,
            "globalpay-1022" =>22,
            "globalpay-1023" =>22,
            "globalpay-1024" =>22,
            "globalpay-1025" =>22,
            "globalpay-1026" =>22,
            "globalpay-1027" =>22,
            "globalpay-1028" =>22,
            "globalpay-1029" =>22,
            "globalpay-1030" =>22,
            "globalpay-1031" =>22,
            "globalpay-1033" =>22,
            "globalpay-1034" =>22,
            "globalpay-1035" =>22,
            "globalpay-1036" =>22,
            "globalpay-1037" =>22,
            "globalpay-1038" =>22,
            "globalpay-1039" =>22,
            "globalpay-1040" =>22,
            "globalpay-1041" =>22,
            "globalpay-1042" =>22,
            "globalpay-1043" =>22,
            "zong" =>13,
            "alipay_payment" =>29,
            "customercredit" =>12,
            "barzahlen" =>26,
            "pp-lc" =>28,
            "adyen_hpp_giropay"=>35,
            "adyen_hpp_paysafecard"=>32,
            "adyen_hpp_bankTransfer_DE"=>36,
            "adyen_hpp_directEbanking"=>34,
            "adyen_hpp_bankTransfer_IBAN"=>36,
            "adyen_hpp_bankTransfer_BE"=>36,
            "adyen_hpp_bankTransfer_NL"=>36,
            "bitpay" => 31,
            "adyen_hpp_mc"=>40,
            "adyen_hpp_amex"=>41,
            "adyen_hpp_visa"=>42
        );
    }
}