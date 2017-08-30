<?php 

namespace Yaoli\Sendorder\Observer;
use Magento\Framework\Event\ObserverInterface;

class LogHttpRefererObserver implements ObserverInterface
{
	protected $_objectManager;

	public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
	{
		$this->_objectManager = $objectManager;
	}

	/**
     * @param Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$session = $this->_objectManager->get('Magento\Framework\Session\Storage');

    	if (!$session->getHttpRefferLogFlags() && isset($_SERVER['HTTP_REFERER']))
    		$session->setHttpRefferLogFlags($_SERVER['HTTP_REFERER']);
    	
    	return $this;
    }
}