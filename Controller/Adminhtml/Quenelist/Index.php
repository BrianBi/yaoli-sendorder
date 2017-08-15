<?php
namespace Yaoli\Sendorder\Controller\Adminhtml\Quenelist;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/18
 * Time: 17:58
 */
use Magento\Backend\App\Action;

class Index extends Action
{
    /* @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory = false;

    /* @var \Magento\Backend\Model\View\Result\Page */
    protected $_resultPage;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        // Call page factory to render layout and page content
        $this->_setPageData();
        return $this->getResultPage();
    }

    /**
     * get page result
     * @return object
     */
    protected function getResultPage()
    {
        if (is_null($this->_resultPage))
        {
            $this->_resultPage = $this->_resultPageFactory->create();
        }

        return $this->_resultPage;
    }

    /**
     * Set Page Data
     * @return $this
     */
    protected function _setPageData()
    {
        $resultPage = $this->getResultPage();
        //$resultPage->setActiveMenu('Yaoli_Sendorder::quenelist');
        $resultPage->getConfig()->getTitle()->prepend((__('SendOrder Quene List')));

        //Add bread crumb
        $resultPage->addBreadcrumb(__('Yaoli'), __('Yaoli'));
        $resultPage->addBreadcrumb(__('Sendorder'), __('SendOrder Quene Lists'));

        return $this;
    }
}