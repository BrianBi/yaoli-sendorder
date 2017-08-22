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
     * get sendorder push amqpLink
     * @return string
     */
    public function getSendorderPushLink()
    {
        return $this->scopeConfig->getValue('sendorder/connection/amqp_links');
    }

    /**
     * push data by phplibamqp
     */
    public function pushOrderdataByLib($_data)
    {
        $amqp = RabbitMQ::create($this->getSendorderQuenueName(), $this->getSendorderPushLink());

        if (is_array($_data))
            $result = $amqp->publish($_data);
        else
            $result = $amqp->publish(unserialize($_data));

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
        $_payinfo = $_payment->getAdditionalInformation();

        $_data = array();

        /** Order base info */
        $_data['web_id']       = $this->getSendorderWebId();
        $_data['increment_id'] = $_order->getIncrementId();
        $_data['ordertype']    = '';
        $_data['action']       = $_order->getStatus() == \Magento\Sales\Model\Order::STATE_COMPLETE ? 1 : 0;
        $_data['status']       = $_order->getStatus();
        $_data['pay_account']  = isset($_payinfo['paypal_payer_email']) ? $_payinfo['paypal_payer_email'] : '';
        $_data['order_add_webid'] = $this->getSendorderWebId();
        $_data['grand_total']     = $_order->getGrandTotal();
        $_data['subtotal']        = $_order->getGrandTotal();
        $_data['subtotal_incl_tax'] = $_order->getGrandTotal();
        $_data['shipping_amount']   = 0;
        $_data['order_currency_code'] = $_order->getOrderCurrencyCode();
        $_data['lancin_fee_amount']      = '';
        $_data['payment_percentage_fee'] = '';
        $_data['express_fee_amount']     = '';

        $_data['Language'] = $this->storeManager->getStore()->getCode();
        $_data['x_forwarded_for'] = null;

        $_data['is_mobile'] = $this->isMobile();
        $_data['buy_ip']    = $_order->getRemoteIp();
        $_data['browser']   = $_SERVER['HTTP_USER_AGENT'];
        $_data['http_referer'] = $this->_objectManager->get('Magento\Framework\Session\Storage')->getData('user_http_referer_log');

        /** Order payment info */
        $_data['payment']['method']  = $this->getOrderPaymentdSyncOaId($_order);
        /* @var $_data['transaction_id'] 根据不同支付方式获取 */
        if (!$_payment->getData('adyen_psp_reference'))
            $_data['payment']['transaction_id'] = $_payment->getData('last_trans_id');
        else
            $_data['payment']['transaction_id'] = $_payment->getData('adyen_psp_reference');

        if (!$_data['payment']['transaction_id'])
        {
            $_orderComments = $this->checkTransactionId($_order);
            if (is_array($_orderComments))
            {
                $_data['payment']['method']     = $_orderComments['method'];
                $_data['payment']['transaction_id'] = $_orderComments['transaction_id'];
            }
        }

        $_data['payment']['additional_data']        = '';
        $_data['payment']['additional_information'] = $_payinfo;
        $_data['payment']['paypal_payer_email']     = isset($_payinfo['paypal_payer_email']) ? $_payinfo['paypal_payer_email'] : '';

        /** @var Order Discount Info */
        $_data['discount']['coupon_code'] = $_order->getCouponCode();
        $_data['discount']['discount_description']   = $_order->getDiscountDescription();
        $_data['discount']['discount_amount']        = $_order->getDiscountAmount();
        $_data['discount']['reward_currency_amount'] = null;

        /** @var Order Customer Info */
        $_customerInfo = $this->getCustomerInfo($_order);
        $_data['customer']['Name'] = $_order->getCustomerFirstname() . ' ' . $_order->getCustomerLastname();
        $_data['customer']['shipping_address'] = $_customerInfo['Address'];
        $_data['customer']['billing_address']  = $_customerInfo['Address'];
        $_data['customer']['telephone'] = $_customerInfo['telephone'];
        $_data['customer']['Country']   = $_customerInfo['Country'];
        $_data['customer']['City']      = $_customerInfo['City'];
        $_data['customer']['Email']     = $_order->getCustomerEmail();

        /** @var Order Items */
        $_data['OrderArray'] = $this->getOrderAllItems($_order);

        return $_data;
    }

    /**
     * Get Order Items
     * @param \Magento\Sales\Model\Order $_order
     * @return array
     */
    protected function getOrderAllItems($_order)
    {
        $_orderItmes = array();

        foreach ($_order->getAllItems() as $_items)
        {
            $_product = $this->_objectManager->create('Magento\Catalog\Model\ProductRepository')->getById($_items->getProductId());

            $_orderItmes[] = array(
                'type' => $_items->getProductType(),
                'sku'  => $_items->getSku(),
                'product_name'=> $_items->getName(),
                'soft_sort'   => $_product->getSoftSort(),
                'softsort_gd' => null,
                'productbundlesku'=> $_product->getProductbundlesku(),
                'qty_ordered'     => $_items->getQtyOrdered(),
                'price_incl_tax'  => $_product->getSpecialPrice() ? $_product->getSpecialPrice() : $_items->getPriceInclTax(),
                'row_total_incl_tax' => null,
                'product_options'    => $_items->getProductOptions(),
                'stock_info' => array(
                        'qty'        => null,
                        'stock_code' => null,
                        'stock_name' => null
                    )
            );
        }

        return $_orderItmes;
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
     * Check Is Mobile
     * @return bool 
     */
    public function isMobile() {
        if ($_SERVER && isset ($_SERVER['HTTP_USER_AGENT'])){
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$userAgent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($userAgent,0,4))) {
                return 1;
            }
        }
        return 0;
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